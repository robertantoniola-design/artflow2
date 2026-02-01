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
 * CORREÇÕES (29/01/2026):
 * - index(): Verificação segura se $vendas são objetos ou arrays
 * - create(): Passa variáveis corretas para a view
 * - relatorio(): Usa métodos existentes do Service
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
     * 
     * CORREÇÃO: Verificação segura ao calcular resumo
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
        
        // Clientes para filtro
        $clientesSelect = $this->clienteService->getParaSelect();
        
        // CORREÇÃO: Verificação segura - $vendas pode conter objetos ou arrays
        $valorTotal = 0;
        $lucroTotal = 0;
        
        foreach ($vendas as $venda) {
            // Verifica se é objeto ou array
            if (is_object($venda)) {
                $valorTotal += $venda->getValor();
                $lucroTotal += $venda->getLucroCalculado() ?? 0;
            } elseif (is_array($venda)) {
                $valorTotal += $venda['valor'] ?? 0;
                $lucroTotal += $venda['lucro_calculado'] ?? 0;
            }
        }
        
        $resumo = [
            'total_vendas' => count($vendas),
            'valor_total' => $valorTotal,
            'lucro_total' => $lucroTotal
        ];
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(function($v) {
                    return is_object($v) ? $v->toArray() : $v;
                }, $vendas),
                'estatisticas' => $estatisticas
            ]);
        }
        
        return $this->view('vendas/index', [
            'titulo' => 'Vendas',
            'vendas' => $vendas,
            'estatisticas' => $estatisticas,
            'clientesSelect' => $clientesSelect,
            'resumo' => $resumo,
            'filtros' => $filtros
        ]);
    }
    
    /**
     * Formulário de nova venda
     * GET /vendas/criar
     */
    public function create(Request $request): Response
    {
        $artesDisponiveis = $this->arteService->getDisponiveisParaVenda();
        $clientesSelect = $this->clienteService->getParaSelect();
        
        $arteSelecionada = $request->get('arte_id');
        $clienteSelecionado = $request->get('cliente_id');
        
        return $this->view('vendas/create', [
            'titulo' => 'Registrar Venda',
            'artesDisponiveis' => $artesDisponiveis,
            'clientesSelect' => $clientesSelect,
            'arteSelecionada' => $arteSelecionada,
            'clienteSelecionado' => $clienteSelecionado
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
            
            $this->flashSuccess('Venda registrada! Lucro: R$ ' . number_format($venda->getLucroCalculado(), 2, ',', '.'));
            return $this->redirectTo('/vendas');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
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
                'titulo' => 'Venda #' . $id,
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
                'clientesSelect' => $clientesSelect
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
            
            $this->flashSuccess('Venda atualizada!');
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
            
            $this->flashSuccess('Venda excluída!');
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
     * 
     * CORREÇÃO: Usa métodos existentes do Service
     */
    public function relatorio(Request $request): Response
    {
        $mes = $request->get('mes', date('Y-m'));
        $ano = $request->get('ano', date('Y'));
        
        $vendasMensais = $this->vendaService->getVendasMensais(12);
        $estatisticas = $this->vendaService->getEstatisticas();
        $rankingRentabilidade = $this->vendaService->getRankingRentabilidade(10);
        
        $relatorio = [
            'vendas_mensais' => $vendasMensais,
            'estatisticas' => $estatisticas,
            'ranking_rentabilidade' => $rankingRentabilidade
        ];
        
        if ($request->wantsJson()) {
            return $this->json(['success' => true, 'data' => $relatorio]);
        }
        
        return $this->view('vendas/relatorio', [
            'titulo' => 'Relatório de Vendas',
            'relatorio' => $relatorio,
            'vendasMensais' => $vendasMensais,
            'estatisticas' => $estatisticas,
            'rankingRentabilidade' => $rankingRentabilidade,
            'filtros' => ['mes' => $mes, 'ano' => $ano]
        ]);
    }
}
