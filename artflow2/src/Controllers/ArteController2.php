<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ArteService;
use App\Services\TagService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * ARTE CONTROLLER — MELHORIA 1 (Paginação)
 * ============================================
 * 
 * FASE 1 (15/02/2026): B8 workaround, B9 limparDados, conversão int, $statusList
 * MELHORIA 1 (16/02/2026): index() agora usa listarPaginado() com paginação
 * 
 * ALTERAÇÕES M1:
 * - index(): $filtros expandido com pagina/ordenar/direcao
 * - index(): Usa ArteService::listarPaginado() ao invés de listar()
 * - index(): Passa $paginacao e $filtros para a view
 * - Demais métodos: INALTERADOS
 */
class ArteController extends BaseController
{
    private ArteService $arteService;
    private TagService $tagService;
    
    public function __construct(ArteService $arteService, TagService $tagService)
    {
        $this->arteService = $arteService;
        $this->tagService = $tagService;
    }
    
    // ==========================================
    // LISTAGEM (MELHORIA 1 — PAGINAÇÃO)
    // ==========================================
    
    /**
     * Lista artes com paginação e filtros
     * GET /artes
     * GET /artes?pagina=2&status=disponivel&tag_id=3&termo=retrato
     * 
     * MELHORIA 1: Paginação via ?pagina=X (12 artes por página)
     * Filtros preservados entre páginas via URL params.
     * 
     * NOTA: Parâmetro 'ordenar' e 'direcao' já são capturados para
     * compatibilidade futura com Melhoria 2 (ordenação dinâmica).
     */
    public function index(Request $request): Response
    {
        // ── [B9 Workaround] Limpa dados residuais de formulários anteriores ──
        $this->limparDadosFormulario();
        
        // ── Captura TODOS os filtros/params da URL ──
        // [MELHORIA 1] Adicionados: pagina, ordenar, direcao
        // [FASE 1 mantidos] termo, status, tag_id
        $filtros = [
            'termo'   => $request->get('termo'),
            'status'  => $request->get('status'),
            'tag_id'  => $request->get('tag_id'),
            'pagina'  => (int) ($request->get('pagina') ?? 1), // Router passa string
            'ordenar' => $request->get('ordenar') ?? 'created_at',
            'direcao' => $request->get('direcao') ?? 'DESC',
        ];
        
        // ── [MELHORIA 1] Busca paginada com filtros combinados ──
        // Retorna: ['artes' => [...], 'paginacao' => [...]]
        $resultado = $this->arteService->listarPaginado($filtros);
        
        // Tags para o dropdown de filtro (usa TagService existente)
        $tags = $this->tagService->listar();
        
        // Estatísticas para os cards (contagem por status)
        $estatisticas = $this->arteService->getEstatisticas();
        
        // Resposta AJAX (mantida para compatibilidade)
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(fn($a) => $a->toArray(), $resultado['artes']),
                'total' => $resultado['paginacao']['total']
            ]);
        }
        
        // ── Renderiza view com dados de paginação ──
        return $this->view('artes/index', [
            'titulo'       => 'Minhas Artes',
            'artes'        => $resultado['artes'],       // Artes da página atual
            'paginacao'    => $resultado['paginacao'],    // [MELHORIA 1] Metadados de paginação
            'tags'         => $tags,                       // Para dropdown de filtro
            'estatisticas' => $estatisticas,               // Para cards de status
            'filtros'      => $filtros,                    // [MELHORIA 1] Filtros ativos (para preservar na URL)
        ]);
    }
    
    // ==========================================
    // CRIAÇÃO
    // ==========================================
    
    /**
     * Exibe formulário de criação
     * GET /artes/criar
     */
    public function create(Request $request): Response
    {
        $tags = $this->tagService->listar();
        
        return $this->view('artes/create', [
            'titulo'        => 'Nova Arte',
            'tags'          => $tags,
            'complexidades' => $this->getComplexidades(),
            'statusList'    => $this->getStatusList()
        ]);
    }
    
    /**
     * Salva nova arte
     * POST /artes
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $this->limparDados($request->all());
            $arte = $this->arteService->criar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Arte criada com sucesso!', [
                    'id' => $arte->getId()
                ]);
            }
            
            $this->flashSuccess('Arte criada com sucesso!');
            return $this->redirectTo('/artes/' . $arte->getId());
            
        } catch (ValidationException $e) {
            // ── [B8 Workaround] Grava erros diretamente em $_SESSION['_errors'] ──
            // O framework grava em $_SESSION['_flash'] mas helpers lêem de $_SESSION['_errors']
            $_SESSION['_errors'] = $e->getErrors();
            
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            return $this->back();
        }
    }
    
    // ==========================================
    // DETALHES
    // ==========================================
    
    /**
     * Exibe detalhes da arte
     * GET /artes/{id}
     */
    public function show(Request $request, int $id): Response
    {
        // ── [B9 Workaround] Limpa dados residuais ──
        $this->limparDadosFormulario();
        
        $id = (int) $id; // Router pode passar string
        
        try {
            $arte = $this->arteService->buscar($id);
            $tags = $this->arteService->getTags($id);
            $custoPorHora = $this->arteService->calcularCustoPorHora($arte);
            $precoSugerido = $this->arteService->calcularPrecoSugerido($arte);
            
            return $this->view('artes/show', [
                'titulo'        => $arte->getNome(),
                'arte'          => $arte,
                'tags'          => $tags,
                'custoPorHora'  => $custoPorHora,
                'precoSugerido' => $precoSugerido,
                'statusList'    => $this->getStatusList()
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Arte não encontrada');
        }
    }
    
    // ==========================================
    // EDIÇÃO
    // ==========================================
    
    /**
     * Exibe formulário de edição
     * GET /artes/{id}/editar
     */
    public function edit(Request $request, int $id): Response
    {
        // ── [B9 Workaround] Limpa dados residuais ──
        $this->limparDadosFormulario();
        
        $id = (int) $id;
        
        try {
            $arte = $this->arteService->buscar($id);
            $tags = $this->tagService->listar();
            $tagIds = $this->tagService->getTagIdsArte($id);
            
            return $this->view('artes/edit', [
                'titulo'        => 'Editar: ' . $arte->getNome(),
                'arte'          => $arte,
                'tags'          => $tags,
                'tagIds'        => $tagIds,
                'complexidades' => $this->getComplexidades(),
                'statusList'    => $this->getStatusList()
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Arte não encontrada');
        }
    }
    
    /**
     * Atualiza arte
     * PUT /artes/{id}
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        $id = (int) $id;
        
        try {
            $dados = $this->limparDados($request->all());
            $arte = $this->arteService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Arte atualizada com sucesso!');
            }
            
            $this->flashSuccess('Arte atualizada com sucesso!');
            return $this->redirectTo('/artes/' . $id);
            
        } catch (ValidationException $e) {
            // ── [B8 Workaround] ──
            $_SESSION['_errors'] = $e->getErrors();
            
            // Recarrega dados para o formulário
            try {
                $arte = $this->arteService->buscar($id);
                $tags = $this->tagService->listar();
                $tagIds = $this->tagService->getTagIdsArte($id);
                
                return $this->view('artes/edit', [
                    'titulo'        => 'Editar: ' . $arte->getNome(),
                    'arte'          => $arte,
                    'tags'          => $tags,
                    'tagIds'        => $tagIds,
                    'complexidades' => $this->getComplexidades(),
                    'statusList'    => $this->getStatusList()
                ]);
            } catch (NotFoundException $e2) {
                return $this->notFound('Arte não encontrada');
            }
                
        } catch (NotFoundException $e) {
            return $this->notFound('Arte não encontrada');
        }
    }
    
    // ==========================================
    // REMOÇÃO
    // ==========================================
    
    /**
     * Remove arte
     * DELETE /artes/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        $id = (int) $id;
        
        try {
            $this->arteService->remover($id);
            
            if ($request->wantsJson()) {
                return $this->success('Arte removida com sucesso!');
            }
            
            $this->flashSuccess('Arte removida com sucesso!');
            return $this->redirectTo('/artes');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getFirstError());
            }
            
            $this->flashError($e->getFirstError());
            return $this->back();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Arte não encontrada');
        }
    }
    
    // ==========================================
    // AÇÕES ESPECIAIS
    // ==========================================
    
    /**
     * Altera status da arte (sem editar outros campos)
     * POST /artes/{id}/status
     */
    public function alterarStatus(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        $id = (int) $id;
        
        try {
            $novoStatus = $request->get('status');
            $arte = $this->arteService->alterarStatus($id, $novoStatus);
            
            if ($request->wantsJson()) {
                return $this->success('Status alterado para ' . $novoStatus, [
                    'status' => $arte->getStatus()
                ]);
            }
            
            $this->flashSuccess('Status alterado com sucesso!');
            return $this->redirectTo('/artes/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getFirstError());
            }
            
            $this->flashError($e->getFirstError());
            return $this->back();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Arte não encontrada');
        }
    }
    
    /**
     * Adiciona horas trabalhadas
     * POST /artes/{id}/horas
     */
    public function adicionarHoras(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        $id = (int) $id;
        
        try {
            $horas = (float) $request->get('horas', 0);
            $this->arteService->adicionarHoras($id, $horas);
            
            if ($request->wantsJson()) {
                return $this->success('Horas adicionadas com sucesso!');
            }
            
            $this->flashSuccess('Horas adicionadas com sucesso!');
            return $this->redirectTo('/artes/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getFirstError());
            }
            
            $this->flashError($e->getFirstError());
            return $this->back();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Arte não encontrada');
        }
    }
    
    // ==========================================
    // HELPERS PRIVADOS
    // ==========================================
    
    /**
     * Lista de complexidades para dropdowns
     */
    private function getComplexidades(): array
    {
        return [
            'baixa' => 'Baixa',
            'media' => 'Média',
            'alta'  => 'Alta'
        ];
    }
    
    /**
     * Lista de status para dropdowns
     * Adicionado na Fase 1 para centralizar labels/valores
     */
    private function getStatusList(): array
    {
        return [
            'disponivel'  => 'Disponível',
            'em_producao' => 'Em Produção',
            'reservada'   => 'Reservada',
            'vendida'     => 'Vendida'
        ];
    }
    
    /**
     * Limpa e normaliza dados do formulário
     * [B9 FIX] Previne que campos vazios sejam enviados como ""
     */
    private function limparDados(array $dados): array
    {
        // Campos numéricos: "" → null (evita erro MySQL strict mode)
        $camposNumericos = ['tempo_medio_horas', 'preco_custo', 'horas_trabalhadas'];
        
        foreach ($camposNumericos as $campo) {
            if (isset($dados[$campo]) && $dados[$campo] === '') {
                $dados[$campo] = null;
            }
        }
        
        // Campos de texto: trim
        $camposTexto = ['nome', 'descricao'];
        foreach ($camposTexto as $campo) {
            if (isset($dados[$campo])) {
                $dados[$campo] = trim($dados[$campo]);
            }
        }
        
        return $dados;
    }
    
    /**
     * Limpa dados residuais de formulários anteriores
     * [B9 Workaround] Evita que dados de create contamine edit
     */
    private function limparDadosFormulario(): void
    {
        unset($_SESSION['_old_input']);
    }
}