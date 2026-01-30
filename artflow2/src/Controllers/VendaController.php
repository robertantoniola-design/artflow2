<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\VendaService;
use App\Services\ArteService;
use App\Services\ClienteService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * VENDA CONTROLLER
 * ============================================
 * 
 * Controller responsável pelas operações de Vendas.
 * 
 * CORREÇÃO (29/01/2026):
 * - Método create() agora passa variáveis com nomes corretos
 *   para a view (artesDisponiveis, clientesSelect)
 */
class VendaController extends BaseController
{
    private VendaService $vendaService;
    private ArteService $arteService;
    private ClienteService $clienteService;
    
    public function __construct(
        VendaService $vendaService,
        ArteService $arteService,
        ClienteService $clienteService
    ) {
        $this->vendaService = $vendaService;
        $this->arteService = $arteService;
        $this->clienteService = $clienteService;
    }
    
    /**
     * Lista todas as vendas
     * GET /vendas
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'mes_ano' => $request->get('mes'),
            'data_inicio' => $request->get('data_inicio'),
            'data_fim' => $request->get('data_fim'),
            'cliente_id' => $request->get('cliente_id')
        ];
        
        $vendas = $this->vendaService->listar($filtros);
        $estatisticas = $this->vendaService->getEstatisticas();
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(fn($v) => $v->toArray(), $vendas),
                'estatisticas' => $estatisticas
            ]);
        }
        
        return $this->view('vendas/index', [
            'titulo' => 'Vendas',
            'vendas' => $vendas,
            'estatisticas' => $estatisticas,
            'filtros' => $filtros
        ]);
    }
    
    /**
     * Formulário de nova venda
     * GET /vendas/criar
     * 
     * CORREÇÃO: Variáveis passadas com nomes que a view espera:
     * - artesDisponiveis (antes: artes)
     * - clientesSelect (antes: clientes)
     */
    public function create(Request $request): Response
    {
        // Artes disponíveis para venda (status = disponivel)
        $artesDisponiveis = $this->arteService->getDisponiveisParaVenda();
        
        // Clientes para o select (formato id => nome)
        $clientesSelect = $this->clienteService->getParaSelect();
        
        // Se veio de uma arte específica (link "vender" na lista de artes)
        $arteSelecionada = $request->get('arte_id');
        
        return $this->view('vendas/create', [
            'titulo' => 'Registrar Venda',
            // CORREÇÃO: Nomes das variáveis conforme a view espera
            'artesDisponiveis' => $artesDisponiveis,
            'clientesSelect' => $clientesSelect,
            'arteSelecionada' => $arteSelecionada,
            // Também mantém os nomes antigos para compatibilidade
            'artes' => $artesDisponiveis,
            'clientes' => $clientesSelect,
            'arteIdSelecionada' => $arteSelecionada
        ]);
    }
    
    /**
     * Registra nova venda
     * POST /vendas
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only(['arte_id', 'cliente_id', 'valor', 'data_venda']);
            
            // Data padrão = hoje
            if (empty($dados['data_venda'])) {
                $dados['data_venda'] = date('Y-m-d');
            }
            
            $venda = $this->vendaService->registrar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Venda registrada!', [
                    'id' => $venda->getId(),
                    'lucro' => $venda->getLucroCalculado(),
                    'rentabilidade' => $venda->getRentabilidadeHora()
                ]);
            }
            
            $this->flashSuccess('Venda registrada com sucesso! Lucro: R$ ' . number_format($venda->getLucroCalculado(), 2, ',', '.'));
            return $this->redirectTo('/vendas');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            // Salva erros e input antigo na sessão
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
        }
    }
    
    /**
     * Exibe detalhes da venda
     * GET /vendas/{id}
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $venda = $this->vendaService->buscar($id);
            
            if ($request->wantsJson()) {
                return $this->json(['success' => true, 'data' => $venda->toArray()]);
            }
            
            return $this->view('vendas/show', [
                'titulo' => 'Detalhes da Venda #' . $id,
                'venda' => $venda
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Venda não encontrada');
        }
    }
    
    /**
     * Formulário de edição
     * GET /vendas/{id}/editar
     */
    public function edit(Request $request, int $id): Response
    {
        try {
            $venda = $this->vendaService->buscar($id);
            $clientesSelect = $this->clienteService->getParaSelect();
            
            return $this->view('vendas/edit', [
                'titulo' => 'Editar Venda #' . $id,
                'venda' => $venda,
                'clientesSelect' => $clientesSelect,
                'clientes' => $clientesSelect // compatibilidade
            ]);
            
        } catch (NotFoundException $e) {
            $this->flashError('Venda não encontrada');
            return $this->redirectTo('/vendas');
        }
    }
    
    /**
     * Atualiza venda
     * PUT /vendas/{id}
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only(['cliente_id', 'valor', 'data_venda']);
            $venda = $this->vendaService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Venda atualizada!');
            }
            
            $this->flashSuccess('Venda atualizada com sucesso!');
            return $this->redirectTo('/vendas/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
            
        } catch (NotFoundException $e) {
            $this->flashError('Venda não encontrada');
            return $this->redirectTo('/vendas');
        }
    }
    
    /**
     * Exclui venda
     * DELETE /vendas/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $this->vendaService->excluir($id);
            
            if ($request->wantsJson()) {
                return $this->success('Venda excluída!');
            }
            
            $this->flashSuccess('Venda excluída com sucesso!');
            return $this->redirectTo('/vendas');
            
        } catch (NotFoundException $e) {
            if ($request->wantsJson()) {
                return $this->error('Venda não encontrada', 404);
            }
            
            $this->flashError('Venda não encontrada');
            return $this->redirectTo('/vendas');
        }
    }
    
    /**
     * Relatório de vendas
     * GET /vendas/relatorio
     */
    public function relatorio(Request $request): Response
    {
        $filtros = [
            'mes' => $request->get('mes', date('Y-m')),
            'ano' => $request->get('ano', date('Y'))
        ];
        
        $relatorio = $this->vendaService->gerarRelatorio($filtros);
        
        if ($request->wantsJson()) {
            return $this->json(['success' => true, 'data' => $relatorio]);
        }
        
        return $this->view('vendas/relatorio', [
            'titulo' => 'Relatório de Vendas',
            'relatorio' => $relatorio,
            'filtros' => $filtros
        ]);
    }
}
