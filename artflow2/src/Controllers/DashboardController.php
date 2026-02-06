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
    
    /**
     * Página inicial do dashboard
     * GET /
     */
    public function index(Request $request): Response
    {
        // Estatísticas de Artes
        $artesStats = $this->arteService->getEstatisticas();
        
        // Estatísticas de Vendas do mês atual
        $vendasMes = $this->vendaService->getVendasMesAtual();
        $faturamentoMes = $this->vendaService->getTotalMes();
        
        // Meta do mês atual
        $metaAtual = $this->metaService->getResumoDashboard();
        
        // =====================================================
        // MELHORIA 4: Verificar se meta está em risco
        // Usa calcularProjecao() para detectar se a projeção
        // do mês corrente indica que a meta não será batida.
        // Retorna ['alerta' => true/false, ...] — seguro para
        // uso na view com verificação simples.
        // =====================================================
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
                    'meta_em_risco' => $metaEmRisco, // MELHORIA 4: inclui no JSON
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
            'metaEmRisco' => $metaEmRisco, // MELHORIA 4: passa para a view
            'topClientes' => $topClientes,
            'artesDisponiveis' => $artesDisponiveis,
            'vendasMensais' => $vendasMensais,
            'maisRentaveis' => $maisRentaveis
        ]);
    }
    
    /**
     * Retorna dados para atualização AJAX
     * GET /dashboard/refresh
     */
    public function refresh(Request $request): Response
    {
        return $this->json([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => [
                // Cards principais
                'cards' => [
                    'total_artes' => $this->arteService->getEstatisticas()['total'] ?? 0,
                    'artes_disponiveis' => $this->arteService->getEstatisticas()['disponiveis'] ?? 0,
                    'vendas_mes' => $this->vendaService->getTotalMes(),
                    'meta_progresso' => $this->metaService->getResumoDashboard()['porcentagem'] ?? 0
                ],
                
                // Meta atual
                'meta' => $this->metaService->getResumoDashboard(),
                
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
        $stats = $this->arteService->getEstatisticas();
        
        $porComplexidade = [
            'baixa' => 0,
            'media' => 0,
            'alta' => 0
        ];
        
        $porStatus = [
            'disponivel' => $stats['disponiveis'] ?? 0,
            'em_producao' => $stats['em_producao'] ?? 0,
            'vendida' => $stats['vendidas'] ?? 0
        ];
        
        return $this->json([
            'success' => true,
            'data' => [
                'total' => $stats['total'] ?? 0,
                'media_horas' => $stats['media_horas'] ?? 0,
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
                // MELHORIA 4: Inclui status de risco
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