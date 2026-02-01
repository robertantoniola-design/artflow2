<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\VendaService;
use App\Services\ArteService;
use App\Services\ClienteService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\DatabaseException;

/**
 * ============================================
 * VENDA CONTROLLER
 * ============================================
 * 
 * Controla o fluxo de vendas.
 * 
 * CORREÇÕES (01/02/2026):
 * - store(): Agora extrai forma_pagamento e observacoes do form
 * - store(): Adicionado catch para DatabaseException (antes propagava erro 500 genérico)
 * - store(): Sanitização de campos vazios antes de enviar ao Service
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
    
    // ==========================================
    // LISTAGEM
    // ==========================================
    
    /**
     * Lista vendas com filtros
     * GET /vendas
     */
    public function index(Request $request): Response
    {
        // Filtros opcionais
        $filtros = $request->only(['cliente_id', 'data_inicio', 'data_fim', 'mes_ano']);
        
        // Remove filtros vazios
        $filtros = array_filter($filtros, fn($v) => $v !== '' && $v !== null);
        
        $vendas = $this->vendaService->listar($filtros);
        $estatisticas = $this->vendaService->getEstatisticas();
        $clientesSelect = $this->clienteService->getParaSelect();
        
        // Calcula resumo
        $valorTotal = 0;
        $lucroTotal = 0;
        foreach ($vendas as $venda) {
            if (is_object($venda)) {
                $valorTotal += $venda->getValor() ?? 0;
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
    
    // ==========================================
    // CRIAR
    // ==========================================
    
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
     * 
     * CORREÇÃO (01/02/2026):
     * - Agora extrai TODOS os campos do form, incluindo forma_pagamento e observacoes
     * - Converte strings vazias para null em campos opcionais (evita FK violation)
     * - Catch para DatabaseException com mensagem útil ao invés de erro 500 genérico
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            // CORREÇÃO: Extrair TODOS os campos do formulário
            // Antes só extraía: ['arte_id', 'cliente_id', 'valor', 'data_venda']
            // Faltavam: forma_pagamento, observacoes
            $dados = $request->only([
                'arte_id', 
                'cliente_id', 
                'valor', 
                'data_venda',
                'forma_pagamento',  // ADICIONADO - campo do form
                'observacoes'       // ADICIONADO - campo do form
            ]);
            
            // CORREÇÃO: Converte strings vazias para null em campos opcionais
            // O form envia cliente_id="" quando nenhum cliente é selecionado
            // O operador ?? só trata null, não converte "" para null
            // MySQL strict mode rejeita "" em coluna INT UNSIGNED (FK violation)
            $dados['cliente_id'] = !empty($dados['cliente_id']) ? $dados['cliente_id'] : null;
            
            // Data padrão = hoje se não informada
            if (empty($dados['data_venda'])) {
                $dados['data_venda'] = date('Y-m-d');
            }
            
            // Forma de pagamento padrão = pix se não informada
            if (empty($dados['forma_pagamento'])) {
                $dados['forma_pagamento'] = 'pix';
            }
            
            // Observações: string vazia → null
            if (empty($dados['observacoes'])) {
                $dados['observacoes'] = null;
            }
            
            $venda = $this->vendaService->registrar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Venda registrada!', [
                    'id' => $venda->getId(),
                    'lucro' => $venda->getLucroCalculado(),
                    'rentabilidade' => $venda->getRentabilidadeHora()
                ]);
            }
            
            $this->flashSuccess(
                'Venda registrada! Lucro: R$ ' . 
                number_format($venda->getLucroCalculado(), 2, ',', '.')
            );
            return $this->redirectTo('/vendas');
            
        } catch (ValidationException $e) {
            // Erro de validação → volta ao form com erros
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
            
        } catch (DatabaseException $e) {
            // ADICIONADO: Catch para erro de banco de dados
            // Antes, DatabaseException não era capturada aqui e propagava 
            // até Application::handleException() com mensagem genérica "Erro ao criar registro"
            
            // Monta mensagem útil para o usuário
            $mensagemOriginal = $e->getPrevious() ? $e->getPrevious()->getMessage() : $e->getMessage();
            
            // Log do erro completo para debug
            error_log("[VendaController::store] DatabaseException: {$mensagemOriginal}");
            error_log("[VendaController::store] Query: " . ($e->getQuery() ?? 'N/A'));
            error_log("[VendaController::store] Params: " . json_encode($e->getParams()));
            
            if ($request->wantsJson()) {
                return $this->error('Erro ao registrar venda: ' . $mensagemOriginal, 500);
            }
            
            $this->flashError('Erro ao registrar venda. Verifique os dados e tente novamente.');
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
            
        } catch (NotFoundException $e) {
            // ADICIONADO: Catch para arte/cliente não encontrado
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 404);
            }
            
            $this->flashError($e->getMessage());
            return $this->back();
        }
    }
    
    // ==========================================
    // VISUALIZAR
    // ==========================================
    
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
    
    // ==========================================
    // EDITAR
    // ==========================================
    
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
            $dados = $request->only(['cliente_id', 'valor', 'data_venda', 'forma_pagamento', 'observacoes']);
            
            // CORREÇÃO: Mesma sanitização do store()
            $dados['cliente_id'] = !empty($dados['cliente_id']) ? $dados['cliente_id'] : null;
            if (empty($dados['observacoes'])) {
                $dados['observacoes'] = null;
            }
            
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
            
        } catch (DatabaseException $e) {
            $this->flashError('Erro ao atualizar venda. Verifique os dados.');
            $_SESSION['_old_input'] = $request->all();
            return $this->back();
        }
    }
    
    // ==========================================
    // EXCLUIR
    // ==========================================
    
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
    
    // ==========================================
    // RELATÓRIO
    // ==========================================
    
    /**
     * Relatório de vendas
     * GET /vendas/relatorio
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