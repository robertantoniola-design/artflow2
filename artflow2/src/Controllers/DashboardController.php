<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ArteService;
use App\Services\VendaService;
use App\Services\MetaService;
use App\Services\ClienteService;

/**
 * ============================================
 * DASHBOARD CONTROLLER
 * ============================================
 * 
 * Controller responsável pela página inicial do sistema.
 * Agrega estatísticas de todos os módulos.
 * 
 * FASE 1 — CORREÇÕES APLICADAS (03/03/2026):
 * - D1: limparDadosFormulario() adicionado (privado, padrão dos outros controllers)
 * - D8: refresh() otimizado — variáveis locais evitam queries duplicadas
 * - FIX CHAVES: ArteService::getEstatisticas() retorna formato countByStatus()
 *   com chaves ['disponivel','em_producao','vendida','reservada'].
 *   Dashboard precisa de ['total','disponiveis','em_producao','vendidas','reservadas'].
 *   Adaptação feita via método privado adaptarArtesStats() para não alterar
 *   ArteService/Repository (que já funcionam para o módulo Artes).
 * 
 * MELHORIA 4 (06/02/2026):
 * - index() agora passa $metaEmRisco para exibir alerta
 *   quando projeção indica que meta não será batida
 */
class DashboardController extends BaseController
{
    private ArteService $arteService;
    private VendaService $vendaService;
    private MetaService $metaService;
    private ClienteService $clienteService;
    
    public function __construct(
        ArteService $arteService,
        VendaService $vendaService,
        MetaService $metaService,
        ClienteService $clienteService
    ) {
        $this->arteService = $arteService;
        $this->vendaService = $vendaService;
        $this->metaService = $metaService;
        $this->clienteService = $clienteService;
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    /**
     * FIX D1: Limpa dados residuais de formulários anteriores
     * 
     * Dashboard não tem forms, mas funciona como "ponto de limpeza":
     * se o usuário navegou Criar Venda → Erro validação → Dashboard,
     * os dados de $_SESSION persistiriam e contaminariam outro módulo.
     * 
     * Padrão dos outros Controllers (ArteController, ClienteController, etc.)
     * onde é definido como private em cada controller individualmente.
     */
    private function limparDadosFormulario(): void
    {
        unset($_SESSION['_old_input'], $_SESSION['_errors']);
    }
    
    /**
     * Adapta formato de ArteService::getEstatisticas() para o Dashboard
     * 
     * ArteService retorna formato countByStatus():
     *   ['disponivel' => N, 'em_producao' => N, 'vendida' => N, 'reservada' => N]
     * 
     * Dashboard precisa de:
     *   ['total' => N, 'disponiveis' => N, 'em_producao' => N, 'vendidas' => N, 'reservadas' => N]
     * 
     * Diferenças:
     *   - Falta campo 'total' (soma de todos)
     *   - 'disponivel' → 'disponiveis' (plural)
     *   - 'vendida' → 'vendidas' (plural)
     *   - 'reservada' → 'reservadas' (plural)
     * 
     * Centralizado aqui para evitar duplicação em index(), refresh() e estatisticasArtes().
     * NÃO alteramos ArteService/Repository para não afetar o módulo Artes que está estável.
     * 
     * @param array $raw Dados crus do ArteService::getEstatisticas()
     * @return array Dados adaptados para uso no Dashboard
     */
    private function adaptarArtesStats(array $raw): array
    {
        return [
            'total'       => array_sum($raw),
            'disponiveis' => $raw['disponivel'] ?? 0,
            'em_producao' => $raw['em_producao'] ?? 0,
            'vendidas'    => $raw['vendida'] ?? 0,
            'reservadas'  => $raw['reservada'] ?? 0,
        ];
    }

    // ==========================================
    // PÁGINA PRINCIPAL
    // ==========================================

    /**
     * Página inicial do dashboard
     * GET /
     */
    public function index(Request $request): Response
    {
        // FIX D1: Limpa dados residuais de formulários
        $this->limparDadosFormulario();

        // Estatísticas de Artes (adapta chaves para formato Dashboard)
        $artesStats = $this->adaptarArtesStats(
            $this->arteService->getEstatisticas()
        );
        
        // Estatísticas de Vendas do mês atual
        $vendasMes = $this->vendaService->getVendasMesAtual();
        $faturamentoMes = $this->vendaService->getTotalMes();
        
        // Meta do mês atual
        $metaAtual = $this->metaService->getResumoDashboard();
        
        // MELHORIA 4: Verificar se meta está em risco
        $metaEmRisco = $this->metaService->getMetasEmRisco();
        
        // Top clientes
        $topClientes = $this->clienteService->getTopClientes(5);
        
        // Artes disponíveis para venda
        $artesDisponiveis = $this->arteService->getDisponiveisParaVenda();
        
        // Vendas dos últimos 6 meses (para gráfico)
        $vendasMensais = $this->vendaService->getVendasMensais(6);
        
        // Artes mais rentáveis
        $maisRentaveis = $this->vendaService->getRankingRentabilidade(5);
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => [
                    'artes' => $artesStats,
                    'vendas_mes' => count($vendasMes),
                    'faturamento_mes' => $faturamentoMes,
                    'meta' => $metaAtual,
                    'meta_em_risco' => $metaEmRisco,
                    'top_clientes' => $topClientes,
                    'artes_disponiveis' => count($artesDisponiveis),
                    'vendas_mensais' => $vendasMensais,
                    'mais_rentaveis' => $maisRentaveis
                ]
            ]);
        }
        
        return $this->view('dashboard/index', [
            'titulo' => 'Dashboard',
            'artesStats' => $artesStats,
            'vendasMes' => $vendasMes,
            'faturamentoMes' => $faturamentoMes,
            'metaAtual' => $metaAtual,
            'metaEmRisco' => $metaEmRisco,
            'topClientes' => $topClientes,
            'artesDisponiveis' => $artesDisponiveis,
            'vendasMensais' => $vendasMensais,
            'maisRentaveis' => $maisRentaveis
        ]);
    }
    
    // ==========================================
    // ENDPOINTS AJAX
    // ==========================================
    
    /**
     * Retorna dados para atualização AJAX
     * GET /dashboard/refresh
     * 
     * FIX D8: Variáveis locais evitam chamadas duplicadas aos Services.
     * Antes: getEstatisticas() chamado 2x, getResumoDashboard() chamado 2x
     *        = 4 queries SQL desnecessárias a cada refresh AJAX.
     * Depois: 1 chamada cada = 4 queries eliminadas por request.
     */
    public function refresh(Request $request): Response
    {
        // Busca dados UMA vez e reutiliza (FIX D8)
        $artesStats = $this->adaptarArtesStats(
            $this->arteService->getEstatisticas()
        );
        $metaResumo = $this->metaService->getResumoDashboard();
        
        return $this->json([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => [
                // Cards principais
                'cards' => [
                    'total_artes'       => $artesStats['total'],
                    'artes_disponiveis' => $artesStats['disponiveis'],
                    'vendas_mes'        => $this->vendaService->getTotalMes(),
                    'meta_progresso'    => $metaResumo['porcentagem'] ?? 0
                ],
                
                // Meta atual (reutiliza variável local)
                'meta' => $metaResumo,
                
                // MELHORIA 4: Inclui status de risco no refresh
                'meta_em_risco' => $this->metaService->getMetasEmRisco(),
                
                // Gráfico de vendas
                'vendas_mensais' => $this->vendaService->getVendasMensais(6)
            ]
        ]);
    }

    /**
     * Estatísticas detalhadas de artes
     * GET /dashboard/artes
     */
    public function estatisticasArtes(Request $request): Response
    {
        // Usa adaptador para ter chaves corretas
        $stats = $this->adaptarArtesStats(
            $this->arteService->getEstatisticas()
        );
        
        $porComplexidade = [
            'baixa' => 0,
            'media' => 0,
            'alta' => 0
        ];
        
        // Agora as chaves batem: 'disponiveis', 'vendidas' (plural)
        $porStatus = [
            'disponivel'  => $stats['disponiveis'],
            'em_producao' => $stats['em_producao'],
            'vendida'     => $stats['vendidas']
        ];
        
        return $this->json([
            'success' => true,
            'data' => [
                'total' => $stats['total'],
                'media_horas' => 0, // TODO: implementar na fase de melhorias
                'por_status' => $porStatus,
                'por_complexidade' => $porComplexidade
            ]
        ]);
    }
    
    /**
     * Estatísticas detalhadas de vendas
     * GET /dashboard/vendas
     */
    public function estatisticasVendas(Request $request): Response
    {
        $periodo = $request->get('periodo', '6m'); // 6m, 1a, total
        
        $meses = match($periodo) {
            '1a' => 12,
            'total' => 24,
            default => 6
        };
        
        $vendasMensais = $this->vendaService->getVendasMensais($meses);
        $ranking = $this->vendaService->getRankingRentabilidade(10);
        
        $totalFaturamento = array_sum(array_column($vendasMensais, 'total'));
        $totalVendas = array_sum(array_column($vendasMensais, 'quantidade'));
        $ticketMedio = $totalVendas > 0 ? $totalFaturamento / $totalVendas : 0;
        
        return $this->json([
            'success' => true,
            'data' => [
                'periodo' => $periodo,
                'vendas_mensais' => $vendasMensais,
                'ranking_rentabilidade' => $ranking,
                'resumo' => [
                    'total_faturamento' => $totalFaturamento,
                    'total_vendas' => $totalVendas,
                    'ticket_medio' => $ticketMedio
                ]
            ]
        ]);
    }
    
    /**
     * Progresso da meta atual
     * GET /dashboard/meta
     */
    public function progressoMeta(Request $request): Response
    {
        $resumo = $this->metaService->getResumoDashboard();
        
        $hoje = new \DateTime();
        $fimMes = new \DateTime('last day of this month');
        $diasRestantes = $hoje->diff($fimMes)->days + 1;
        
        $faltaVender = max(0, ($resumo['valor_meta'] ?? 0) - ($resumo['valor_realizado'] ?? 0));
        $faltaPorDia = $diasRestantes > 0 ? $faltaVender / $diasRestantes : 0;
        
        return $this->json([
            'success' => true,
            'data' => array_merge($resumo, [
                'dias_restantes' => $diasRestantes,
                'falta_vender' => $faltaVender,
                'falta_por_dia' => $faltaPorDia,
                'em_risco' => $this->metaService->getMetasEmRisco()
            ])
        ]);
    }
    
    /**
     * Atividades recentes
     * GET /dashboard/atividades
     */
    public function atividadesRecentes(Request $request): Response
    {
        $limite = (int) $request->get('limite', 10);
        
        $ultimasVendas = $this->vendaService->getVendasMesAtual();
        $ultimasVendas = array_slice($ultimasVendas, 0, $limite);
        
        $atividades = [];
        
        foreach ($ultimasVendas as $venda) {
            $atividades[] = [
                'tipo' => 'venda',
                'icone' => 'shopping-cart',
                'cor' => 'success',
                'titulo' => 'Nova venda registrada',
                'descricao' => "Arte vendida por R$ " . number_format($venda->getValor(), 2, ',', '.'),
                'data' => $venda->getDataVenda()
            ];
        }
        
        return $this->json([
            'success' => true,
            'data' => $atividades
        ]);
    }
    
    /**
     * Busca global
     * GET /dashboard/busca
     */
    public function busca(Request $request): Response
    {
        $termo = $request->get('q', '');
        
        if (strlen($termo) < 2) {
            return $this->json([
                'success' => false,
                'message' => 'Termo de busca deve ter pelo menos 2 caracteres'
            ]);
        }
        
        // TODO: Implementar busca global
        return $this->json([
            'success' => true,
            'data' => [
                'termo' => $termo,
                'resultados' => []
            ]
        ]);
    }
}