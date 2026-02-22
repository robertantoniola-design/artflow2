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
 * ARTE CONTROLLER — MELHORIA 4 (Upload de Imagem)
 * ============================================
 * 
 * HISTÓRICO:
 * ─────────────────────
 * Fase 1:     B8/B9 workarounds, conversão int, $statusList
 * Melhoria 1: index() usa listarPaginado() + passa $paginacao + filtros
 * Melhoria 4: store() e update() agora processam upload de imagem
 *             - store(): passa $request->file('imagem') para Service
 *             - update(): passa arquivo + flag $removerImagem
 *             - Formulários precisam de enctype="multipart/form-data"
 * 
 * Rotas:
 * GET    /artes              -> index()          Lista com filtros + ordenação
 * GET    /artes/criar        -> create()         Formulário de criação
 * POST   /artes              -> store()          Salva nova (+ upload imagem)
 * GET    /artes/{id}         -> show()           Detalhes + tags + cálculos + imagem
 * GET    /artes/{id}/editar  -> edit()           Formulário de edição (+ imagem atual)
 * PUT    /artes/{id}         -> update()         Atualiza + sync tags (+ upload/remove imagem)
 * DELETE /artes/{id}         -> destroy()        Remove (CASCADE em arte_tags + limpa imagem)
 * POST   /artes/{id}/status  -> alterarStatus()  Muda status sem editar tudo
 * POST   /artes/{id}/horas   -> adicionarHoras() Incrementa horas_trabalhadas
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
    // MÉTODOS AUXILIARES
    // ==========================================
    
    /**
     * CORREÇÃO B9: Limpa dados residuais de formulários anteriores
     * Chamado em index(), edit() e show() — NUNCA em create()!
     */
    private function limparDadosFormulario(): void
    {
        unset($_SESSION['_old_input'], $_SESSION['_errors']);
    }
    
    /**
     * Lista de complexidades para selects nos formulários
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
     * Lista de status para selects nos formulários
     * CORREÇÃO A1: Inclui 'reservada' (existia no ENUM do banco)
     */
    private function getStatusList(): array
    {
        return [
            'disponivel'  => 'Disponível',
            'em_producao' => 'Em Produção',
            'vendida'     => 'Vendida',
            'reservada'   => 'Reservada'
        ];
    }
    
    /**
     * Limpa dados do formulário removendo campos não pertinentes ao banco
     * 
     * Remove campos que NÃO devem ir para o Repository:
     * - _token (CSRF)
     * - _method (PUT/DELETE override)
     * - imagem (tratado separadamente via $_FILES)
     * - remover_imagem (flag de controle, não é coluna do banco)
     * 
     * @param array $dados Dados brutos do request
     * @return array Dados limpos para o Service/Repository
     */
    private function limparDados(array $dados): array
    {
        // Remove campos de controle que não são colunas do banco
        unset(
            $dados['_token'],
            $dados['_method'],
            $dados['imagem'],          // [M4] Tratado via $_FILES, não via POST
            $dados['remover_imagem']   // [M4] Flag de controle, não é coluna
        );
        
        return $dados;
    }
    
    // ==========================================
    // LISTAGEM
    // ==========================================
    
    /**
     * Lista todas as artes
     * GET /artes
     * 
     * [M1] Paginação via ?pagina=X (12 artes/página)
     * [M2] Ordenação via ?ordenar=coluna&direcao=ASC|DESC
     * [M6] Dados para gráficos Chart.js e cards de resumo
     */
    public function index(Request $request): Response
    {
        // CORREÇÃO B9: Limpa dados residuais
        $this->limparDadosFormulario();
        
        // Filtros da URL (M1: paginação, M2: ordenação)
        $filtros = [
            'status'  => $request->get('status'),
            'termo'   => $request->get('termo'),
            'tag_id'  => $request->get('tag_id'),
            'pagina'  => (int) ($request->get('pagina') ?? 1),
            'ordenar' => $request->get('ordenar') ?? 'created_at',
            'direcao' => $request->get('direcao') ?? 'DESC'
        ];
        
        // M1: Busca paginada
        $resultado = $this->arteService->listarPaginado($filtros);
        
        // Tags para dropdown de filtro
        $tags = $this->tagService->listar();
        
        // Estatísticas por status (já existia — usado nos cards de status E no gráfico M6)
        $estatisticas = $this->arteService->getEstatisticas();
        
        // ── [MELHORIA 6] Dados para gráficos e cards de resumo ──
        $distribuicaoComplexidade = $this->arteService->getDistribuicaoComplexidade();
        $resumoCards = $this->arteService->getResumoCards();
        
        // Proteção: só exibe gráficos se houver artes no banco
        // (evita Canvas vazio / gráfico com 0 em todos os valores)
        $temDadosGrafico = ($resumoCards['total'] ?? 0) > 0;
        
        return $this->view('artes/index', [
            'titulo'      => 'Minhas Artes',
            'artes'       => $resultado['artes'],
            'paginacao'   => $resultado['paginacao'],
            'filtros'     => $filtros,
            'tags'        => $tags,
            'estatisticas' => $estatisticas,
            
            // ── [M6] Novos dados para gráficos e cards ──
            'distribuicaoComplexidade' => $distribuicaoComplexidade,
            'resumoCards'              => $resumoCards,
            'temDadosGrafico'          => $temDadosGrafico,
        ]);
    }
    
    // ==========================================
    // CRIAÇÃO
    // ==========================================
    
    /**
     * Exibe formulário de criação
     * GET /artes/criar
     * 
     * ⚠️ NÃO chamar limparDadosFormulario() aqui!
     * Os erros de validação do store() precisam chegar ao form.
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
     * 
     * [MELHORIA 4] — Agora processa upload de imagem via $request->file('imagem')
     * 
     * IMPORTANTE: O formulário DEVE ter enctype="multipart/form-data"
     * para que $_FILES seja populado corretamente.
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            // Limpa dados do formulário (remove _token, _method, imagem, etc.)
            $dados = $this->limparDados($request->all());
            
            // [MELHORIA 4] Obtém arquivo de imagem (ou null se nenhum foi enviado)
            $arquivo = $request->hasFile('imagem') ? $request->file('imagem') : null;
            
            // Delega para o Service (que cuida de validar, criar, upload e sync tags)
            $arte = $this->arteService->criar($dados, $arquivo);
            
            if ($request->wantsJson()) {
                return $this->success('Arte criada com sucesso!', [
                    'id' => $arte->getId()
                ]);
            }
            
            $this->limparDadosFormulario();
            $this->flashSuccess('Arte "' . $arte->getNome() . '" criada com sucesso!');
            return $this->redirectTo('/artes/' . $arte->getId());
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            // CORREÇÃO B8: Grava erros direto na sessão
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
        }
    }
    
    // ==========================================
    // VISUALIZAÇÃO
    // ==========================================
    
    /**
     * Exibe detalhes da arte
     * GET /artes/{id}
     * 
     * [Fase 1] Tags, cálculos, status list
     * [M4] Imagem ampliada
     * [M5] Métricas completas (custo/hora, preço sugerido, progresso)
     */
    public function show(Request $request, int $id): Response
    {
        $this->limparDadosFormulario();
        $id = (int) $id;
        
        try {
            $arte = $this->arteService->buscar($id);
            $tags = $this->arteService->getTags($id);
            
            // Cálculos individuais (mantidos para retrocompatibilidade com show.php existente)
            $custoPorHora  = $this->arteService->calcularCustoPorHora($arte);
            $precoSugerido = $this->arteService->calcularPrecoSugerido($arte);
            
            // ── [MELHORIA 5] Métricas completas para cards de estatísticas ──
            // Retorna: custo_por_hora, preco_sugerido, progresso
            // Progresso inclui: percentual, valor_real, horas_faltam (ou null)
            $metricas = $this->arteService->getMetricasArte($arte);
            
            return $this->view('artes/show', [
                'titulo'        => $arte->getNome(),
                'arte'          => $arte,
                'tags'          => $tags,
                'custoPorHora'  => $custoPorHora,   // Retrocompatibilidade
                'precoSugerido' => $precoSugerido,  // Retrocompatibilidade
                'statusList'    => $this->getStatusList(),
                'metricas'      => $metricas,       // [M5] Array completo de métricas
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
            $this->flashError('Arte não encontrada');
            return $this->redirectTo('/artes');
        }
    }
    
    /**
     * Atualiza arte existente
     * PUT /artes/{id}
     * 
     * [MELHORIA 4] — Agora processa upload/remoção de imagem
     * 
     * Três cenários de imagem:
     * 1. checkbox "remover_imagem" marcado → remove imagem sem substituir
     * 2. novo arquivo enviado              → substitui imagem anterior
     * 3. nenhum dos dois                   → mantém imagem atual
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        $id = (int) $id;
        
        try {
            $dados = $this->limparDados($request->all());
            
            // [MELHORIA 4] Captura arquivo e flag de remoção
            $arquivo = $request->hasFile('imagem') ? $request->file('imagem') : null;
            $removerImagem = !empty($request->get('remover_imagem'));
            
            // Delega para o Service com os novos parâmetros
            $arte = $this->arteService->atualizar($id, $dados, $arquivo, $removerImagem);
            
            if ($request->wantsJson()) {
                return $this->success('Arte atualizada com sucesso!');
            }
            
            $this->limparDadosFormulario();
            $this->flashSuccess('Arte atualizada com sucesso!');
            return $this->redirectTo('/artes/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            // CORREÇÃO B8: Grava direto na sessão
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            // CORREÇÃO A6: Re-renderiza view diretamente
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
     * 
     * [MELHORIA 4] — Service agora também remove arquivo de imagem do disco
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
            $arte = $this->arteService->adicionarHoras($id, $horas);
            
            if ($request->wantsJson()) {
                return $this->success('Horas adicionadas!', [
                    'horas_trabalhadas' => $arte->getHorasTrabalhadas()
                ]);
            }
            
            $this->flashSuccess("{$horas}h adicionadas com sucesso!");
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
}
