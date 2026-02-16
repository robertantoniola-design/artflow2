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
 * ARTE CONTROLLER — FASE 1 CORRIGIDO
 * ============================================
 * 
 * CORREÇÕES APLICADAS (14/02/2026):
 * - A1: Status 'reservada' incluído nos selects (corrigido no ArteValidator)
 * - A2/B8: Erros de validação gravados direto em $_SESSION['_errors']
 * - A3/B9: limparDadosFormulario() em index(), edit(), show()
 * - A4: Conversão explícita string→int para parâmetros do Router
 * - A5: getComplexidades() e getStatusList() centralizados
 * - A6: update() re-renderiza view em vez de back() após erro de validação
 * 
 * Rotas:
 * GET    /artes              -> index()          Lista com filtros
 * GET    /artes/criar        -> create()         Formulário de criação
 * POST   /artes              -> store()          Salva nova
 * GET    /artes/{id}         -> show()           Detalhes + tags + cálculos
 * GET    /artes/{id}/editar  -> edit()           Formulário de edição
 * PUT    /artes/{id}         -> update()         Atualiza + sync tags
 * DELETE /artes/{id}         -> destroy()        Remove (CASCADE em arte_tags)
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
     * 
     * Chamado em index(), edit() e show() — NUNCA em create()!
     * Motivo: Após validação falhar no store(), dados ficam em $_SESSION.
     * Se o usuário navegar para edit() de outra arte, o form mostraria
     * dados do create falho em vez dos dados reais da arte.
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
     * CORREÇÃO A1: Inclui 'reservada' (existia no ENUM do banco mas faltava aqui)
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
    
    // ==========================================
    // LISTAGEM
    // ==========================================
    
    /**
     * Lista todas as artes
     * GET /artes
     */
    public function index(Request $request): Response
    {
        // CORREÇÃO B9: Limpa dados residuais ao navegar para a lista
        $this->limparDadosFormulario();
        
        // Filtros da URL — nomes conferidos com os inputs do HTML da view
        $filtros = [
            'status' => $request->get('status'),
            'termo'  => $request->get('termo'),
            'tag_id' => $request->get('tag_id')
        ];
        
        $artes = $this->arteService->listar($filtros);
        $tags = $this->tagService->listar();
        $estatisticas = $this->arteService->getEstatisticas();
        
        // Resposta AJAX
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data'    => array_map(fn($a) => $a->toArray(), $artes),
                'total'   => count($artes)
            ]);
        }
        
        return $this->view('artes/index', [
            'titulo'       => 'Minhas Artes',
            'artes'        => $artes,
            'tags'         => $tags,
            'estatisticas' => $estatisticas,
            'filtros'      => $filtros
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
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only([
                'nome', 'descricao', 'tempo_medio_horas',
                'complexidade', 'preco_custo', 'horas_trabalhadas',
                'status', 'tags'
            ]);
            
            $arte = $this->arteService->criar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Arte criada com sucesso!', [
                    'id'       => $arte->getId(),
                    'redirect' => url('/artes')
                ]);
            }
            
            // Sucesso: limpa resíduos
            $this->limparDadosFormulario();
            
            $this->flashSuccess('Arte "' . $arte->getNome() . '" criada com sucesso!');
            return $this->redirectTo('/artes');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            // ============================================
            // CORREÇÃO B8: Grava direto na sessão
            // ============================================
            // Motivo: Response::withErrors() salva em $_SESSION['_flash']['errors']
            // mas os helpers has_error()/errors() leem de $_SESSION['_errors'].
            // Resultado sem esta correção: erros de validação são silenciosamente
            // ignorados e o formulário "aceita" dados inválidos sem feedback.
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
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
        // CORREÇÃO B9: Limpa dados residuais
        $this->limparDadosFormulario();
        
        // CORREÇÃO A4: Garante conversão string→int (Router passa string)
        $id = (int) $id;
        
        try {
            $arte = $this->arteService->buscar($id);
            
            // getTags() existe no ArteService ✅ — delega para TagRepository::getByArte()
            $tags = $this->arteService->getTags($id);
            
            // Cálculos auxiliares — ambos existem no ArteService ✅
            $custoPorHora  = $this->arteService->calcularCustoPorHora($arte);
            $precoSugerido = $this->arteService->calcularPrecoSugerido($arte);
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success'        => true,
                    'data'           => $arte->toArray(),
                    'tags'           => array_map(fn($t) => $t->toArray(), $tags),
                    'custo_por_hora' => $custoPorHora,
                    'preco_sugerido' => $precoSugerido
                ]);
            }
            
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
        // CORREÇÃO B9: Limpa dados residuais do create()
        $this->limparDadosFormulario();
        
        // CORREÇÃO A4: Conversão string→int
        $id = (int) $id;
        
        try {
            $arte = $this->arteService->buscar($id);
            $tags = $this->tagService->listar();
            
            // getTagIdsArte() existe no TagService ✅ — delega para TagRepository::getIdsByArte()
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
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        $id = (int) $id;
        
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
            
            // CORREÇÃO A6: Re-renderiza a view diretamente em vez de back()
            // Motivo: back() depende do HTTP_REFERER que pode ser impreciso.
            // Padrão do ClienteController corrigido: renderiza a view com dados frescos.
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
     * Adiciona horas trabalhadas (incrementa, não substitui)
     * POST /artes/{id}/horas
     */
    public function adicionarHoras(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        $id = (int) $id;
        
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
}