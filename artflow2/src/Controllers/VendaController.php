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
     */
    public function create(Request $request): Response
    {
        // Artes disponíveis para venda
        $artes = $this->arteService->getDisponiveisParaVenda();
        $clientes = $this->clienteService->getParaSelect();
        
        // Se veio de uma arte específica
        $arteId = $request->get('arte_id');
        
        return $this->view('vendas/create', [
            'titulo' => 'Registrar Venda',
            'artes' => $artes,
            'clientes' => $clientes,
            'arteIdSelecionada' => $arteId
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
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
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
            $clientes = $this->clienteService->getParaSelect();
            
            return $this->view('vendas/edit', [
                'titulo' => 'Editar Venda #' . $id,
                'venda' => $venda,
                'clientes' => $clientes
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
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Venda não encontrada');
        }
    }
    
    /**
     * Remove venda
     * DELETE /vendas/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $this->vendaService->remover($id);
            
            if ($request->wantsJson()) {
                return $this->success('Venda removida!');
            }
            
            $this->flashSuccess('Venda removida com sucesso!');
            return $this->redirectTo('/vendas');
            
        } catch (NotFoundException $e) {
            return $this->notFound('Venda não encontrada');
        }
    }
    
    /**
     * Relatório de vendas
     * GET /vendas/relatorio
     */
    public function relatorio(Request $request): Response
    {
        $dataInicio = $request->get('data_inicio', date('Y-m-01'));
        $dataFim = $request->get('data_fim', date('Y-m-d'));
        
        $faturamento = $this->vendaService->getFaturamentoPeriodo($dataInicio, $dataFim);
        $vendasMensais = $this->vendaService->getVendasMensais(12);
        $ranking = $this->vendaService->getRankingRentabilidade(10);
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'faturamento' => $faturamento,
                'vendas_mensais' => $vendasMensais,
                'ranking' => $ranking
            ]);
        }
        
        return $this->view('vendas/relatorio', [
            'titulo' => 'Relatório de Vendas',
            'faturamento' => $faturamento,
            'vendasMensais' => $vendasMensais,
            'ranking' => $ranking,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim
        ]);
    }
}
