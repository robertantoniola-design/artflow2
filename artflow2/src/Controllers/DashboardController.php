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
 * - FIX CHAVES: adaptarArtesStats() converte formato countByStatus → Dashboard
 * 
 * MELHORIA 4 (06/02/2026):
 * - index() passa $metaEmRisco para alerta de meta em risco
 * 
 * MELHORIA M1 — CARDS APRIMORADOS (05/03/2026):
 * - +Card Faturamento do Mês (separado do card Vendas)
 * - +Card Lucro do Mês (com margem %)
 * - +Card Ticket Médio
 * - Indicadores de tendência (↑↓%) comparando mês atual vs anterior
 * - Subtextos informativos com contexto
 * - Layout: 6 cards em 2 linhas de 3 (antes: 4 cards em 1 linha)
 * - Card "À Venda" absorvido como subtexto de "Total Artes"
 * - Nenhum Service/Repository/Model alterado — dados já existiam
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

    /**
     * M1: Calcula lucro do mês a partir das vendas (objetos Venda)
     * 
     * Itera os objetos Venda do mês atual e soma lucro_calculado.
     * Defensivo: suporta tanto objetos com getLucroCalculado() quanto arrays.
     * 
     * @param array $vendasMes Array de Venda objects (do getVendasMesAtual)
     * @return float Lucro total do mês
     */
    private function calcularLucroMes(array $vendasMes): float
    {
        $lucro = 0;
        foreach ($vendasMes as $v) {
            if (is_object($v)) {
                // Venda object — usa getter
                $lucro += $v->getLucroCalculado() ?? 0;
            } else {
                // Array fallback (segurança)
                $lucro += (float)($v['lucro_calculado'] ?? 0);
            }
        }
        return round($lucro, 2);
    }

    /**
     * M1: Calcula tendências comparando mês atual vs mês anterior
     * 
     * Busca os dados do mês anterior no array $vendasMensais (retornado por
     * getVendasMensais(6)) e compara com os valores atuais calculados diretamente.
     * 
     * Usa dados do mês atual calculados diretamente ($faturamentoMes, $lucroMes, etc.)
     * como fonte autoritativa — não depende de $vendasMensais conter o mês atual.
     * 
     * @param array $vendasMensais Dados dos últimos 6 meses (getVendasMensais)
     * @param float $faturamentoAtual Faturamento do mês atual (getTotalMes)
     * @param float $lucroAtual Lucro do mês atual (calculado de vendasMes)
     * @param int   $qtdAtual Quantidade de vendas do mês atual
     * @param float $ticketAtual Ticket médio atual
     * @return array Tendências com percentual e valor anterior para cada métrica
     */
    private function calcularTendencias(
        array $vendasMensais,
        float $faturamentoAtual,
        float $lucroAtual,
        int   $qtdAtual,
        float $ticketAtual
    ): array {
        // Identifica o mês anterior (YYYY-MM)
        $mesAnteriorStr = date('Y-m', strtotime('first day of last month'));
        
        // Procura dados do mês anterior no array de vendasMensais
        // Cada item tem chave 'mes' no formato 'YYYY-MM'
        $dadosAnterior = null;
        foreach ($vendasMensais as $mes) {
            if (($mes['mes'] ?? '') === $mesAnteriorStr) {
                $dadosAnterior = $mes;
                break;
            }
        }
        
        // Se não encontrou dados do mês anterior, retorna null em todas as tendências
        // (ex: primeiro mês de uso do sistema, ou mês anterior sem vendas)
        if (!$dadosAnterior) {
            return [
                'faturamento' => null,
                'lucro'       => null,
                'quantidade'  => null,
                'ticket'      => null,
            ];
        }
        
        // Extrai valores do mês anterior
        $fatAnt   = (float)($dadosAnterior['total'] ?? 0);
        $lucroAnt = (float)($dadosAnterior['lucro'] ?? 0);
        $qtdAnt   = (int)($dadosAnterior['quantidade'] ?? 0);
        $ticketAnt = $qtdAnt > 0 ? round($fatAnt / $qtdAnt, 2) : 0;
        
        // Calcula variação percentual para cada métrica
        return [
            'faturamento' => $this->calcularVariacao($faturamentoAtual, $fatAnt),
            'lucro'       => $this->calcularVariacao($lucroAtual, $lucroAnt),
            'quantidade'  => $this->calcularVariacao((float)$qtdAtual, (float)$qtdAnt),
            'ticket'      => $this->calcularVariacao($ticketAtual, $ticketAnt),
        ];
    }

    /**
     * M1: Calcula variação percentual entre valor atual e anterior
     * 
     * Retorna array com:
     * - 'percentual': variação em % (positivo = crescimento, negativo = queda)
     * - 'anterior': valor absoluto do mês anterior (para exibir no subtexto)
     * 
     * Caso especial: se anterior = 0 e atual > 0, retorna +100% (novo).
     * Se ambos = 0, retorna 0%.
     * 
     * @param float $atual Valor do mês atual
     * @param float $anterior Valor do mês anterior
     * @return array ['percentual' => float, 'anterior' => float]
     */
    private function calcularVariacao(float $atual, float $anterior): array
    {
        if ($anterior == 0) {
            // Evita divisão por zero
            // Se atual > 0, é 100% de crescimento (veio do zero)
            // Se atual = 0, não houve variação
            return [
                'percentual' => $atual > 0 ? 100.0 : 0.0,
                'anterior'   => 0.0
            ];
        }
        
        $percentual = round((($atual - $anterior) / $anterior) * 100, 1);
        
        return [
            'percentual' => $percentual,
            'anterior'   => $anterior
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
        
        // Vendas dos últimos 6 meses (para gráfico + tendências M1)
        $vendasMensais = $this->vendaService->getVendasMensais(6);
        
        // Artes mais rentáveis
        $maisRentaveis = $this->vendaService->getRankingRentabilidade(5);
        
        // =====================================================
        // M1: CÁLCULOS PARA NOVOS CARDS E TENDÊNCIAS
        // Nenhuma query extra — usa dados já buscados acima.
        // =====================================================
        
        // Quantidade de vendas do mês (defensivo com is_array)
        $qtdVendasMes = is_array($vendasMes) ? count($vendasMes) : 0;
        
        // Lucro do mês: soma lucro_calculado de todos os objetos Venda
        $lucroMes = is_array($vendasMes) ? $this->calcularLucroMes($vendasMes) : 0;
        
        // Ticket médio: faturamento / quantidade (evita divisão por zero)
        $ticketMedio = $qtdVendasMes > 0 ? round($faturamentoMes / $qtdVendasMes, 2) : 0;
        
        // Margem de lucro %: (lucro / faturamento) * 100
        $margemMes = $faturamentoMes > 0 ? round(($lucroMes / $faturamentoMes) * 100, 1) : 0;
        
        // Tendências: compara mês atual vs mês anterior (usa $vendasMensais)
        $tendencias = $this->calcularTendencias(
            $vendasMensais,
            $faturamentoMes,
            $lucroMes,
            $qtdVendasMes,
            $ticketMedio
        );
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => [
                    'artes' => $artesStats,
                    'vendas_mes' => $qtdVendasMes,
                    'faturamento_mes' => $faturamentoMes,
                    'lucro_mes' => $lucroMes,           // M1: novo
                    'ticket_medio' => $ticketMedio,      // M1: novo
                    'margem_mes' => $margemMes,          // M1: novo
                    'tendencias' => $tendencias,         // M1: novo
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
            'titulo'           => 'Dashboard',
            'artesStats'       => $artesStats,
            'vendasMes'        => $vendasMes,
            'faturamentoMes'   => $faturamentoMes,
            'metaAtual'        => $metaAtual,
            'metaEmRisco'      => $metaEmRisco,
            'topClientes'      => $topClientes,
            'artesDisponiveis' => $artesDisponiveis,
            'vendasMensais'    => $vendasMensais,
            'maisRentaveis'    => $maisRentaveis,
            // M1: Novos dados para cards aprimorados
            'lucroMes'         => $lucroMes,
            'ticketMedio'      => $ticketMedio,
            'margemMes'        => $margemMes,
            'tendencias'       => $tendencias,
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
     * M1: Inclui novos cards (faturamento, lucro, ticket) no refresh.
     */
    public function refresh(Request $request): Response
    {
        // Busca dados UMA vez e reutiliza (FIX D8)
        $artesStats = $this->adaptarArtesStats(
            $this->arteService->getEstatisticas()
        );
        $metaResumo = $this->metaService->getResumoDashboard();
        
        // M1: Busca vendas do mês para calcular lucro e ticket
        $vendasMes = $this->vendaService->getVendasMesAtual();
        $faturamentoMes = $this->vendaService->getTotalMes();
        $qtdVendasMes = count($vendasMes);
        $lucroMes = $this->calcularLucroMes($vendasMes);
        $ticketMedio = $qtdVendasMes > 0 ? round($faturamentoMes / $qtdVendasMes, 2) : 0;
        $margemMes = $faturamentoMes > 0 ? round(($lucroMes / $faturamentoMes) * 100, 1) : 0;
        
        return $this->json([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => [
                // Cards principais (chaves = data-stat attributes na view)
                'cards' => [
                    'total_artes'       => $artesStats['total'],
                    'artes_disponiveis' => $artesStats['disponiveis'],
                    'qtd_vendas_mes'    => $qtdVendasMes,
                    'faturamento_mes'   => $faturamentoMes,
                    'lucro_mes'         => $lucroMes,          // M1: novo
                    'ticket_medio'      => $ticketMedio,        // M1: novo
                    'margem_mes'        => $margemMes,          // M1: novo
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