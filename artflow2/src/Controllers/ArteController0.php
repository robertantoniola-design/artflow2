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
 * ARTE CONTROLLER
 * ============================================
 * 
 * Controller responsável pelas operações de Artes.
 * Segue padrão RESTful para rotas.
 * 
 * Rotas:
 * GET    /artes           -> index()   Lista todas
 * GET    /artes/criar     -> create()  Formulário de criação
 * POST   /artes           -> store()   Salva nova
 * GET    /artes/{id}      -> show()    Exibe detalhes
 * GET    /artes/{id}/editar -> edit()  Formulário de edição
 * PUT    /artes/{id}      -> update()  Atualiza
 * DELETE /artes/{id}      -> destroy() Remove
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
    // LISTAGEM
    // ==========================================
    
    /**
     * Lista todas as artes
     * GET /artes
     */
    public function index(Request $request): Response
    {
        // Filtros da URL
       $filtros = [
        'status' => $request->get('status'),
        'termo' => $request->get('termo'),    // FIX: view envia 'termo', não 'q'
        'tag_id' => $request->get('tag_id')   // FIX: view envia 'tag_id', não 'tag'
        ];
        
        $artes = $this->arteService->listar($filtros);
        $tags = $this->tagService->listar();
        $estatisticas = $this->arteService->getEstatisticas();
        
        // Resposta AJAX
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(fn($a) => $a->toArray(), $artes),
                'total' => count($artes)
            ]);
        }
        
        return $this->view('artes/index', [
            'titulo' => 'Minhas Artes',
            'artes' => $artes,
            'tags' => $tags,
            'estatisticas' => $estatisticas,
            'filtros' => $filtros
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
            'titulo' => 'Nova Arte',
            'tags' => $tags,
            'complexidades' => $this->getComplexidades(),
            'statusList' => $this->getStatusList()
        ]);
    }
    
    /**
     * Salva nova arte
     * POST /artes
     */
    public function store(Request $request): Response
    {
        // Valida CSRF
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only([
                'nome', 'descricao', 'tempo_medio_horas',
                'complexidade', 'preco_custo', 'horas_trabalhadas', 
                'status', 'tags'
            ]);
            
            $arte = $this->arteService->criar($dados);
            
            // Resposta AJAX
            if ($request->wantsJson()) {
                return $this->success('Arte criada com sucesso!', [
                    'id' => $arte->getId(),
                    'redirect' => url('/artes')
                ]);
            }
            
            $this->flashSuccess('Arte "' . $arte->getNome() . '" criada com sucesso!');
            return $this->redirectTo('/artes');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()
                ->withErrors($e->getErrors())
                ->withInput();
        }
    }
    
    // ==========================================
    // VISUALIZAÇÃO
    // ==========================================
    
    /**
     * Exibe detalhes de uma arte
     * GET /artes/{id}
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $arte = $this->arteService->buscar($id);
            $tags = $this->arteService->getTags($id);
            
            // Cálculos auxiliares
            $custoPorHora = $this->arteService->calcularCustoPorHora($arte);
            $precoSugerido = $this->arteService->calcularPrecoSugerido($arte);
            
            // Resposta AJAX
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'data' => $arte->toArray(),
                    'tags' => array_map(fn($t) => $t->toArray(), $tags),
                    'custo_por_hora' => $custoPorHora,
                    'preco_sugerido' => $precoSugerido
                ]);
            }
            
            return $this->view('artes/show', [
                'titulo' => $arte->getNome(),
                'arte' => $arte,
                'tags' => $tags,
                'custoPorHora' => $custoPorHora,
                'precoSugerido' => $precoSugerido
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
        try {
            $arte = $this->arteService->buscar($id);
            $tags = $this->tagService->listar();
            $tagIds = $this->tagService->getTagIdsArte($id);
            
            return $this->view('artes/edit', [
                'titulo' => 'Editar: ' . $arte->getNome(),
                'arte' => $arte,
                'tags' => $tags,
                'tagIds' => $tagIds,
                'complexidades' => $this->getComplexidades(),
                'statusList' => $this->getStatusList()
            ]);
            
        } catch (NotFoundException $e) {
            $this->flashError('Arte não encontrada');
            return $this->redirectTo('/artes');
        }
    }
    
    /**
     * Atualiza arte existente
     * PUT /artes/{id}
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only([
                'nome', 'descricao', 'tempo_medio_horas',
                'complexidade', 'preco_custo', 'horas_trabalhadas', 
                'status', 'tags'
            ]);
            
            $arte = $this->arteService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Arte atualizada com sucesso!', [
                    'id' => $arte->getId()
                ]);
            }
            
            $this->flashSuccess('Arte atualizada com sucesso!');
            return $this->redirectTo('/artes/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()
                ->withErrors($e->getErrors())
                ->withInput();
                
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
     * Altera status da arte
     * POST /artes/{id}/status
     */
    public function alterarStatus(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $novoStatus = $request->get('status');
            $arte = $this->arteService->alterarStatus($id, $novoStatus);
            
            if ($request->wantsJson()) {
                return $this->success('Status alterado para ' . $novoStatus, [
                    'status' => $arte->getStatus()
                ]);
            }
            
            $this->flashSuccess('Status alterado com sucesso!');
            return $this->back();
            
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
        
        try {
            $horas = (float) $request->get('horas');
            $arte = $this->arteService->adicionarHoras($id, $horas);
            
            if ($request->wantsJson()) {
                return $this->success('Horas adicionadas!', [
                    'total_horas' => $arte->getHorasTrabalhadas()
                ]);
            }
            
            $this->flashSuccess($horas . ' horas adicionadas com sucesso!');
            return $this->back();
            
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
    // HELPERS
    // ==========================================
    
    /**
     * Retorna lista de complexidades
     */
    private function getComplexidades(): array
    {
        return [
            'baixa' => 'Baixa',
            'media' => 'Média',
            'alta' => 'Alta'
        ];
    }
    
    /**
     * Retorna lista de status
     */
    private function getStatusList(): array
    {
        return [
            'disponivel' => 'Disponível',
            'em_producao' => 'Em Produção',
            'vendida' => 'Vendida'
        ];
    }
}
