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
 * VENDA CONTROLLER — FASE 1 ESTABILIZAÇÃO
 * ============================================
 * 
 * Controla o fluxo de vendas (módulo mais acoplado do sistema).
 * 
 * CORREÇÕES ANTERIORES (01/02/2026):
 * - store(): Extrai forma_pagamento e observacoes do form
 * - store(): Catch para DatabaseException
 * - store()/update(): Sanitização de cliente_id vazio → null
 * 
 * FASE 1 — CORREÇÕES (22/02/2026):
 * - V1 (B8): Workaround validação — $_SESSION['_errors'] direto no store()
 * - V2 (B9): limparDadosFormulario() em index(), show(), edit() — NUNCA em create()
 * - V3: Conversão (int) $id em show(), edit(), update(), destroy()
 * - V9: buscar() agora usa buscarComRelacionamentos() no show() (arte/cliente hydrated)
 * - Padrão: Consistência com ClienteController/ArteController já estabilizados
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
    // CORREÇÃO V2 (B9): Helper para limpar dados residuais
    // ==========================================
    
    /**
     * Limpa dados residuais de formulários anteriores
     * 
     * IMPORTANTE: Chamado em index(), edit() e show() — NUNCA em create()!
     * 
     * Sem isso, se o usuário submete um form com erro e depois navega
     * para outra página, os dados do form anterior ainda ficam na sessão
     * e podem contaminar outros formulários.
     * 
     * Padrão idêntico ao ClienteController (Fase 1 Clientes, 13/02/2026).
     */
    private function limparDadosFormulario(): void
    {
        unset($_SESSION['_old_input'], $_SESSION['_errors']);
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
        // CORREÇÃO V2 (B9): Limpa dados residuais ao navegar para a lista
        $this->limparDadosFormulario();
        
        // Filtros opcionais
        $filtros = $request->only(['cliente_id', 'data_inicio', 'data_fim', 'mes_ano']);
        
        // Remove filtros vazios
        $filtros = array_filter($filtros, fn($v) => $v !== '' && $v !== null);
        
        $vendas = $this->vendaService->listar($filtros);
        $estatisticas = $this->vendaService->getEstatisticas();
        $clientesSelect = $this->clienteService->getParaSelect();
        
        // Calcula resumo da listagem atual
        // NOTA: $vendas pode conter objetos Venda OU arrays brutos,
        // dependendo do método do Repository que foi chamado.
        // O getRecentes() retorna arrays, findByPeriodo() retorna objetos.
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
                'resumo' => $resumo
            ]);
        }
        
        return $this->view('vendas/index', [
            'titulo'         => 'Vendas',
            'vendas'         => $vendas,
            'estatisticas'   => $estatisticas,
            'clientesSelect' => $clientesSelect,
            'resumo'         => $resumo,
            'filtros'        => $filtros
        ]);
    }
    
    // ==========================================
    // CRIAR
    // ==========================================
    
    /**
     * Formulário de criação
     * GET /vendas/criar
     * 
     * NÃO limpa $_SESSION aqui! (B9)
     * Se o usuário submeteu com erro e foi redirecionado de volta,
     * o create() precisa ler os dados de $_SESSION['_old_input'] e $_SESSION['_errors']
     * para reexibir o formulário preenchido com mensagens de erro.
     */
    public function create(Request $request): Response
    {
        // Busca artes disponíveis (status != 'vendida') para o select
        $artesDisponiveis = $this->arteService->getDisponiveisParaVenda();
        
        // Busca todos os clientes para o select
        $clientesSelect = $this->clienteService->getParaSelect();
        
        // Pré-seleção via URL: /vendas/criar?arte_id=5&cliente_id=3
        // Permite criar venda diretamente da página de uma arte ou cliente
        $arteSelecionada = $request->get('arte_id') ? (int) $request->get('arte_id') : null;
        $clienteSelecionado = $request->get('cliente_id') ? (int) $request->get('cliente_id') : null;
        
        return $this->view('vendas/create', [
            'titulo'              => 'Registrar Venda',
            'artesDisponiveis'    => $artesDisponiveis,
            'clientesSelect'      => $clientesSelect,
            'arteSelecionada'     => $arteSelecionada,
            'clienteSelecionado'  => $clienteSelecionado
        ]);
    }
    
    /**
     * Registra nova venda
     * POST /vendas
     * 
     * Fluxo completo:
     * 1. Valida CSRF
     * 2. Extrai e sanitiza dados do formulário
     * 3. Delega ao Service (que orquestra 3 tabelas: vendas + artes + metas)
     * 4. Redireciona para a venda criada
     * 
     * Em caso de erro de validação:
     * - Salva erros em $_SESSION['_errors'] (WORKAROUND B8)
     * - Salva dados do form em $_SESSION['_old_input']
     * - Redireciona back() para reexibir formulário
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            // Extrai TODOS os campos do formulário
            $dados = $request->only([
                'arte_id', 'cliente_id', 'valor', 
                'data_venda', 'forma_pagamento', 'observacoes'
            ]);
            
            // Sanitização: campos vazios → null (evita erros de FK)
            // Se o select de cliente volta vazio (''), converter para null
            $dados['cliente_id'] = !empty($dados['cliente_id']) ? (int) $dados['cliente_id'] : null;
            
            // Observações vazias → null (campo TEXT não precisa de string vazia)
            if (empty($dados['observacoes'])) {
                $dados['observacoes'] = null;
            }
            
            // Delega ao Service (registra venda + atualiza arte + atualiza meta)
            $venda = $this->vendaService->registrar($dados);
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Venda registrada com sucesso!',
                    'data' => $venda->toArray()
                ], 201);
            }
            
            $this->flashSuccess('Venda registrada com sucesso!');
            return $this->redirectTo('/vendas/' . $venda->getId());
            
        } catch (ValidationException $e) {
            // CORREÇÃO V1 (B8): Grava erros DIRETAMENTE em $_SESSION
            // O Response::withErrors() grava em $_SESSION['_flash'] que 
            // não é lido pelo helper errors() das views.
            // Padrão idêntico ao ClienteController/ArteController.
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', 422, $e->getErrors());
            }
            
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
            
        } catch (DatabaseException $e) {
            // Erro de banco (FK inválida, constraint violation, etc.)
            $mensagemOriginal = $e->getMessage();
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
            // Arte ou cliente não encontrado no banco
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
    public function show(Request $request, $id): Response
    {
        // CORREÇÃO V3: Router passa $id como string — converter para int
        $id = (int) $id;
        
        // CORREÇÃO V2 (B9): Limpa dados residuais
        $this->limparDadosFormulario();
        
        try {
            // CORREÇÃO V9: Usa buscarComRelacionamentos() em vez de buscar()
            // Assim $venda->getArte() e $venda->getCliente() retornam objetos populados
            // em vez de null (findWithRelations vs findOrFail)
            $venda = $this->vendaService->buscarComRelacionamentos($id);
            
            if ($request->wantsJson()) {
                return $this->json(['success' => true, 'data' => $venda->toArray()]);
            }
            
            return $this->view('vendas/show', [
                'titulo' => 'Venda #' . $id,
                'venda'  => $venda
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
    public function edit(Request $request, $id): Response
    {
        // CORREÇÃO V3: Router passa $id como string
        $id = (int) $id;
        
        // CORREÇÃO V2 (B9): Limpa dados residuais — CRÍTICO aqui!
        $this->limparDadosFormulario();
        
        try {
            // Usa buscarComRelacionamentos para ter os dados da arte no form
            $venda = $this->vendaService->buscarComRelacionamentos($id);
            $clientesSelect = $this->clienteService->getParaSelect();
            
            return $this->view('vendas/edit', [
                'titulo'         => 'Editar Venda #' . $id,
                'venda'          => $venda,
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
     * 
     * NOTA: arte_id NÃO pode ser alterado na edição.
     * A arte já foi marcada como 'vendida' no store().
     * Apenas cliente, valor, data, forma de pagamento e observações são editáveis.
     */
    public function update(Request $request, $id): Response
    {
        // CORREÇÃO V3: Router passa $id como string
        $id = (int) $id;
        
        $this->validateCsrf($request);
        
        try {
            // Extrai apenas campos editáveis (arte_id NUNCA é alterado)
            $dados = $request->only([
                'cliente_id', 'valor', 'data_venda', 
                'forma_pagamento', 'observacoes'
            ]);
            
            // Sanitização: mesma lógica do store()
            $dados['cliente_id'] = !empty($dados['cliente_id']) ? (int) $dados['cliente_id'] : null;
            if (empty($dados['observacoes'])) {
                $dados['observacoes'] = null;
            }
            
            $venda = $this->vendaService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Venda atualizada!');
            }
            
            // Limpa dados de formulário após sucesso
            $this->limparDadosFormulario();
            
            $this->flashSuccess('Venda atualizada com sucesso!');
            return $this->redirectTo('/vendas/' . $id);
            
        } catch (ValidationException $e) {
            // CORREÇÃO V1 (B8): $_SESSION direto
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
            error_log("[VendaController::update] DatabaseException: " . $e->getMessage());
            
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
     * 
     * FASE 1: Agora também reverte o status da arte para 'disponivel'
     * e recalcula a meta do mês (via VendaService::excluir)
     */
    public function destroy(Request $request, $id): Response
    {
        // CORREÇÃO V3: Router passa $id como string
        $id = (int) $id;
        
        $this->validateCsrf($request);
        
        try {
            $this->vendaService->excluir($id);
            
            if ($request->wantsJson()) {
                return $this->success('Venda excluída!');
            }
            
            $this->flashSuccess('Venda excluída com sucesso! Arte voltou para disponível.');
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
     * 
     * REGRA CRÍTICA: Esta rota DEVE estar declarada ANTES de $router->resource('/vendas')
     * no routes.php, senão "relatorio" é interpretado como {id} pelo Router (Bug V8).
     */
    public function relatorio(Request $request): Response
    {
        // CORREÇÃO V2 (B9): Limpa dados residuais
        $this->limparDadosFormulario();
        
        try {
            // Filtros de período para o relatório
            $mes = $request->get('mes');
            $ano = $request->get('ano') ?? date('Y');
            
            // Dados para os gráficos e tabelas
            $vendasMensais = $this->vendaService->getVendasMensais(12);
            $estatisticas = $this->vendaService->getEstatisticas();
            $rankingRentabilidade = $this->vendaService->getRankingRentabilidade(10);
            
            // Relatório do período (se filtro aplicado)
            $relatorio = [];
            if ($mes && $ano) {
                $relatorio = $this->vendaService->listar([
                    'mes_ano' => "{$ano}-{$mes}"
                ]);
            }
            
            return $this->view('vendas/relatorio', [
                'titulo'                => 'Relatório de Vendas',
                'relatorio'             => $relatorio,
                'vendasMensais'         => $vendasMensais,
                'estatisticas'          => $estatisticas,
                'rankingRentabilidade'  => $rankingRentabilidade,
                'filtros'               => ['mes' => $mes, 'ano' => $ano]
            ]);
            
        } catch (\Exception $e) {
            error_log("[VendaController::relatorio] Erro: " . $e->getMessage());
            
            // Fallback seguro: renderiza página com dados vazios
            return $this->view('vendas/relatorio', [
                'titulo'                => 'Relatório de Vendas',
                'relatorio'             => [],
                'vendasMensais'         => [],
                'estatisticas'          => ['total_vendas' => 0, 'valor_total' => 0, 'lucro_total' => 0],
                'rankingRentabilidade'  => [],
                'filtros'               => ['mes' => null, 'ano' => date('Y')]
            ]);
        }
    }
}