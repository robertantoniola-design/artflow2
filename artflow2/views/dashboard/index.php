<?php
/**
 * VIEW: Dashboard Principal
 * GET /
 * 
 * Variáveis:
 * - $artesStats: Estatísticas de artes
 * - $vendasMes: Vendas do mês atual
 * - $faturamentoMes: Total faturado no mês
 * - $metaAtual: Array com informações da meta
 * - $topClientes: Array de ARRAYS (não objetos) com dados de compras
 * - $artesDisponiveis: Artes disponíveis para venda
 * - $vendasMensais: Dados para gráfico (array de arrays com mes, quantidade, total, lucro)
 * - $maisRentaveis: Artes mais rentáveis
 * 
 * CORREÇÃO (31/01/2026):
 * - topClientes agora são arrays (fix no Repository)
 * - Gráficos Chart.js adicionados
 */
$currentPage = 'dashboard';

// ============================================
// PREPARAR DADOS PARA GRÁFICOS (PHP → JS)
// ============================================

// Gráfico Vendas Mensais
$chartLabels = [];
$chartValores = [];
$chartQuantidades = [];
$chartLucros = [];

if (!empty($vendasMensais)) {
    foreach ($vendasMensais as $mes) {
        // Formata label do mês (YYYY-MM → Jan/26)
        $mesData = $mes['mes'] ?? '';
        if (!empty($mesData)) {
            $timestamp = strtotime($mesData . '-01');
            $meses_pt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            $mesNum = (int)date('n', $timestamp) - 1;
            $ano = date('y', $timestamp);
            $chartLabels[] = ($meses_pt[$mesNum] ?? $mesData) . '/' . $ano;
        } else {
            $chartLabels[] = $mes['mes_nome'] ?? '-';
        }
        $chartValores[] = (float)($mes['total'] ?? 0);
        $chartQuantidades[] = (int)($mes['quantidade'] ?? 0);
        $chartLucros[] = (float)($mes['lucro'] ?? 0);
    }
}

// Gráfico Status das Artes
$statusDisponiveis = (int)($artesStats['disponiveis'] ?? 0);
$statusEmProducao = (int)($artesStats['em_producao'] ?? 0);
$statusVendidas = (int)($artesStats['vendidas'] ?? 0);

// Gráfico Meta
$metaValor = (float)($metaAtual['valor_meta'] ?? 0);
$metaRealizado = (float)($metaAtual['valor_realizado'] ?? 0);
$metaFalta = max(0, $metaValor - $metaRealizado);
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Dashboard</h2>
        <p class="text-muted mb-0">Visão geral do seu negócio</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/artes/criar') ?>" class="btn btn-outline-primary">
            <i class="bi bi-plus-lg"></i> Nova Arte
        </a>
        <a href="<?= url('/vendas/criar') ?>" class="btn btn-success">
            <i class="bi bi-cart-plus"></i> Nova Venda
        </a>
    </div>
</div>

<?php include __DIR__ . '/../components/alerta-meta-risco.php'; ?>

<!-- ============================================ -->
<!-- CARDS PRINCIPAIS                             -->
<!-- ============================================ -->
<div class="row g-3 mb-4">
    <!-- Total de Artes -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Total de Artes</h6>
                        <h2 class="mb-0"><?= $artesStats['total'] ?? 0 ?></h2>
                    </div>
                    <i class="bi bi-palette display-6 opacity-50"></i>
                </div>
                <small class="opacity-75">
                    <?= $artesStats['disponiveis'] ?? 0 ?> disponíveis
                </small>
            </div>
        </div>
    </div>
    
    <!-- Vendas do Mês -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Vendas (mês)</h6>
                        <h2 class="mb-0"><?= is_array($vendasMes) ? count($vendasMes) : 0 ?></h2>
                    </div>
                    <i class="bi bi-cart-check display-6 opacity-50"></i>
                </div>
                <small class="opacity-75">
                    <?= money($faturamentoMes ?? 0) ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Artes Disponíveis -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">À Venda</h6>
                        <h2 class="mb-0"><?= is_array($artesDisponiveis) ? count($artesDisponiveis) : 0 ?></h2>
                    </div>
                    <i class="bi bi-tag display-6 opacity-50"></i>
                </div>
                <small class="opacity-75">Prontas para vender</small>
            </div>
        </div>
    </div>
    
    <!-- Meta do Mês -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Meta do Mês</h6>
                        <h2 class="mb-0"><?= number_format($metaAtual['porcentagem'] ?? 0, 0) ?>%</h2>
                    </div>
                    <i class="bi bi-bullseye display-6 opacity-50"></i>
                </div>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-dark" 
                         style="width: <?= min($metaAtual['porcentagem'] ?? 0, 100) ?>%"></div>
                </div>
                <small class="opacity-75 d-block mt-1">
                    <?= money($metaAtual['valor_realizado'] ?? 0) ?> / <?= money($metaAtual['valor_meta'] ?? 0) ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- GRÁFICOS LINHA 1                             -->
<!-- ============================================ -->
<div class="row g-4 mb-4">
    
    <!-- Gráfico: Faturamento Mensal (Barras) -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>
                    Faturamento Mensal
                </h5>
                <span class="badge bg-primary">Últimos 6 meses</span>
            </div>
            <div class="card-body">
                <?php if (!empty($vendasMensais)): ?>
                    <div style="position:relative; height:280px;">
                        <canvas id="chartFaturamento"></canvas>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-bar-chart display-4 d-block mb-2"></i>
                        Sem dados de vendas para exibir
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Gráfico: Status das Artes (Doughnut) -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart me-2 text-info"></i>
                    Status das Artes
                </h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <?php if (($artesStats['total'] ?? 0) > 0): ?>
                    <div style="position:relative; height:220px;">
                        <canvas id="chartStatus"></canvas>
                    </div>
                    <!-- Legenda manual abaixo do gráfico -->
                    <div class="d-flex gap-3 mt-3 flex-wrap justify-content-center">
                        <small><span class="badge bg-success">&nbsp;</span> Disponíveis (<?= $statusDisponiveis ?>)</small>
                        <small><span class="badge bg-warning">&nbsp;</span> Produção (<?= $statusEmProducao ?>)</small>
                        <small><span class="badge" style="background:#6366f1">&nbsp;</span> Vendidas (<?= $statusVendidas ?>)</small>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-palette display-4 d-block mb-2"></i>
                        Nenhuma arte cadastrada
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- GRÁFICOS LINHA 2                             -->
<!-- ============================================ -->
<div class="row g-4 mb-4">
    
    <!-- Gráfico: Meta vs Realizado -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bullseye me-2 text-warning"></i>
                    Meta do Mês
                </h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <?php if ($metaValor > 0): ?>
                    <div style="position:relative; height:220px;">
                        <canvas id="chartMeta"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <div class="d-flex gap-3 justify-content-center">
                            <small><span class="badge bg-success">&nbsp;</span> Realizado: <?= money($metaRealizado) ?></small>
                            <small><span class="badge bg-danger opacity-50">&nbsp;</span> Falta: <?= money($metaFalta) ?></small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-bullseye display-4 d-block mb-2"></i>
                        Nenhuma meta definida
                        <br><a href="<?= url('/metas/criar') ?>" class="btn btn-sm btn-warning mt-2">Criar Meta</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Gráfico: Quantidade de Vendas por Mês (Linha) -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2 text-success"></i>
                    Evolução de Vendas
                </h5>
                <span class="badge bg-success">Quantidade e Lucro</span>
            </div>
            <div class="card-body">
                <?php if (!empty($vendasMensais)): ?>
                    <div style="position:relative; height:280px;">
                        <canvas id="chartEvolucao"></canvas>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-graph-up display-4 d-block mb-2"></i>
                        Sem dados para exibir
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- TOP CLIENTES + MAIS RENTÁVEIS                -->
<!-- ============================================ -->
<div class="row g-4 mb-4">
    
    <!-- Top Clientes -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-trophy me-2 text-warning"></i>
                    Top Clientes
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($topClientes)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Cliente</th>
                                    <th class="text-center">Compras</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topClientes as $index => $cliente): ?>
                                    <?php
                                    // CORREÇÃO: topClientes agora retorna ARRAYS
                                    // Suporte para ambos formatos (segurança)
                                    if (is_object($cliente)) {
                                        $nomeCliente = $cliente->getNome();
                                        $cidadeCliente = $cliente->getCidade() ?? '';
                                        $totalCompras = $cliente->total_compras ?? 0;
                                        $valorTotal = $cliente->valor_total_compras ?? 0;
                                        $clienteId = $cliente->getId();
                                    } else {
                                        $nomeCliente = $cliente['nome'] ?? '';
                                        $cidadeCliente = $cliente['cidade'] ?? '';
                                        $totalCompras = $cliente['total_compras'] ?? 0;
                                        $valorTotal = $cliente['valor_total_compras'] ?? 0;
                                        $clienteId = $cliente['id'] ?? 0;
                                    }
                                    
                                    // Cor do ranking
                                    $badgeClass = match($index) {
                                        0 => 'bg-warning text-dark',
                                        1 => 'bg-secondary',
                                        2 => 'bg-danger bg-opacity-75',
                                        default => 'bg-light text-dark'
                                    };
                                    
                                    // Ícone do ranking
                                    $medalha = match($index) {
                                        0 => '🥇',
                                        1 => '🥈',
                                        2 => '🥉',
                                        default => ($index + 1) . 'º'
                                    };
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $badgeClass ?> rounded-pill">
                                                <?= $medalha ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= url('/clientes/' . $clienteId) ?>" class="text-decoration-none">
                                                <strong><?= e($nomeCliente) ?></strong>
                                            </a>
                                            <?php if ($cidadeCliente): ?>
                                                <br><small class="text-muted"><?= e($cidadeCliente) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill"><?= $totalCompras ?></span>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success"><?= money($valorTotal) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-people display-4 d-block mb-2"></i>
                        Nenhuma venda registrada
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Artes Mais Rentáveis -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning me-2 text-danger"></i>
                    Artes Mais Rentáveis
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($maisRentaveis)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Arte</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-end">R$/hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maisRentaveis as $index => $venda): ?>
                                    <?php
                                    // maisRentaveis vem como arrays do VendaRepository
                                    $arteNome = is_array($venda) 
                                        ? ($venda['arte_nome'] ?? 'Arte') 
                                        : ($venda->arte_nome ?? 'Arte');
                                    $valor = is_array($venda) 
                                        ? ($venda['valor'] ?? 0) 
                                        : ($venda->getValor() ?? 0);
                                    $rentabilidade = is_array($venda) 
                                        ? ($venda['rentabilidade_hora'] ?? 0) 
                                        : ($venda->getRentabilidadeHora() ?? 0);
                                    $clienteNome = is_array($venda) 
                                        ? ($venda['cliente_nome'] ?? '') 
                                        : '';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-danger bg-opacity-75 rounded-pill"><?= $index + 1 ?>º</span>
                                        </td>
                                        <td>
                                            <strong><?= e($arteNome) ?></strong>
                                            <?php if ($clienteNome): ?>
                                                <br><small class="text-muted">→ <?= e($clienteNome) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= money($valor) ?></td>
                                        <td class="text-end">
                                            <strong class="text-danger"><?= money($rentabilidade) ?>/h</strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-lightning display-4 d-block mb-2"></i>
                        Sem dados de rentabilidade
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- VENDAS RECENTES                              -->
<!-- ============================================ -->
<div class="row g-4">
    <!-- Tabela Vendas Mensais (dados brutos) -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-table me-2 text-primary"></i>
                    Resumo Mensal
                </h5>
                <a href="<?= url('/vendas/relatorio') ?>" class="btn btn-sm btn-outline-primary">
                    Ver Relatório
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($vendasMensais)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mês</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-end">Faturamento</th>
                                    <th class="text-end">Lucro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($vendasMensais) as $mes): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $mesData = $mes['mes'] ?? '';
                                            if (!empty($mesData)) {
                                                $ts = strtotime($mesData . '-01');
                                                $nomesMes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                                                echo ($nomesMes[(int)date('n', $ts) - 1] ?? '') . '/' . date('Y', $ts);
                                            } else {
                                                echo $mes['mes_nome'] ?? '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill"><?= $mes['quantidade'] ?? 0 ?></span>
                                        </td>
                                        <td class="text-end"><?= money($mes['total'] ?? 0) ?></td>
                                        <td class="text-end text-success"><?= money($mes['lucro'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">Sem dados</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Artes Disponíveis para Venda -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-tag me-2 text-success"></i>
                    Disponíveis para Venda
                </h5>
                <a href="<?= url('/artes') ?>" class="btn btn-sm btn-outline-success">
                    Ver Todas
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($artesDisponiveis)): ?>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light" style="position: sticky; top: 0;">
                                <tr>
                                    <th>Arte</th>
                                    <th class="text-end">Custo</th>
                                    <th class="text-end">Horas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($artesDisponiveis, 0, 10) as $arte): ?>
                                    <?php
                                    $arteNome = is_object($arte) ? $arte->getNome() : ($arte['nome'] ?? '');
                                    $arteCusto = is_object($arte) ? $arte->getPrecoCusto() : ($arte['preco_custo'] ?? 0);
                                    $arteHoras = is_object($arte) ? $arte->getHorasTrabalhadas() : ($arte['horas_trabalhadas'] ?? 0);
                                    $arteId = is_object($arte) ? $arte->getId() : ($arte['id'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/artes/' . $arteId) ?>" class="text-decoration-none">
                                                <?= e($arteNome) ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?= money($arteCusto) ?></td>
                                        <td class="text-end"><?= number_format($arteHoras, 1) ?>h</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        Nenhuma arte disponível
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CHART.JS + SCRIPTS DOS GRÁFICOS              -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Dados do PHP → JS
const chartData = {
    labels: <?= json_encode($chartLabels) ?>,
    valores: <?= json_encode($chartValores) ?>,
    quantidades: <?= json_encode($chartQuantidades) ?>,
    lucros: <?= json_encode($chartLucros) ?>,
    status: {
        disponiveis: <?= $statusDisponiveis ?>,
        emProducao: <?= $statusEmProducao ?>,
        vendidas: <?= $statusVendidas ?>
    },
    meta: {
        valor: <?= $metaValor ?>,
        realizado: <?= $metaRealizado ?>,
        falta: <?= $metaFalta ?>
    }
};

// Cores padrão
const cores = {
    primary: '#0d6efd',
    success: '#198754',
    warning: '#ffc107',
    danger: '#dc3545',
    info: '#0dcaf0',
    purple: '#6366f1',
    primaryBg: 'rgba(13, 110, 253, 0.15)',
    successBg: 'rgba(25, 135, 84, 0.15)',
    dangerBg: 'rgba(220, 53, 69, 0.15)'
};

// Configuração global
Chart.defaults.font.family = "'Segoe UI', system-ui, -apple-system, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';

// ============================================
// 1. GRÁFICO FATURAMENTO MENSAL (Barras)
// ============================================
<?php if (!empty($vendasMensais)): ?>
(function() {
    const ctx = document.getElementById('chartFaturamento');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Faturamento',
                    data: chartData.valores,
                    backgroundColor: cores.primaryBg,
                    borderColor: cores.primary,
                    borderWidth: 2,
                    borderRadius: 6,
                    order: 2
                },
                {
                    label: 'Lucro',
                    data: chartData.lucros,
                    backgroundColor: cores.successBg,
                    borderColor: cores.success,
                    borderWidth: 2,
                    borderRadius: 6,
                    order: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': R$ ' + 
                                   ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: val => 'R$ ' + val.toLocaleString('pt-BR')
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
})();
<?php endif; ?>

// ============================================
// 2. GRÁFICO STATUS DAS ARTES (Doughnut)
// ============================================
<?php if (($artesStats['total'] ?? 0) > 0): ?>
(function() {
    const ctx = document.getElementById('chartStatus');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Disponíveis', 'Em Produção', 'Vendidas'],
            datasets: [{
                data: [
                    chartData.status.disponiveis,
                    chartData.status.emProducao,
                    chartData.status.vendidas
                ],
                backgroundColor: [cores.success, cores.warning, cores.purple],
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((ctx.parsed / total) * 100).toFixed(1);
                            return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
})();
<?php endif; ?>

// ============================================
// 3. GRÁFICO META (Doughnut semi)
// ============================================
<?php if ($metaValor > 0): ?>
(function() {
    const ctx = document.getElementById('chartMeta');
    if (!ctx) return;
    
    const pct = Math.min((chartData.meta.realizado / chartData.meta.valor) * 100, 100);
    const corMeta = pct >= 100 ? cores.success : (pct >= 50 ? cores.warning : cores.danger);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Realizado', 'Faltante'],
            datasets: [{
                data: [chartData.meta.realizado, chartData.meta.falta],
                backgroundColor: [corMeta, 'rgba(0,0,0,0.08)'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            rotation: -90,
            circumference: 180,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.label + ': R$ ' + 
                                   ctx.parsed.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        },
        plugins: [{
            // Plugin: texto no centro
            id: 'centerText',
            afterDraw: function(chart) {
                const {ctx, width, height} = chart;
                ctx.save();
                ctx.font = 'bold 28px "Segoe UI"';
                ctx.textAlign = 'center';
                ctx.fillStyle = corMeta;
                ctx.fillText(pct.toFixed(0) + '%', width / 2, height - 30);
                ctx.font = '13px "Segoe UI"';
                ctx.fillStyle = '#6c757d';
                ctx.fillText('da meta', width / 2, height - 10);
                ctx.restore();
            }
        }]
    });
})();
<?php endif; ?>

// ============================================
// 4. GRÁFICO EVOLUÇÃO (Linha)
// ============================================
<?php if (!empty($vendasMensais)): ?>
(function() {
    const ctx = document.getElementById('chartEvolucao');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Faturamento (R$)',
                    data: chartData.valores,
                    borderColor: cores.primary,
                    backgroundColor: cores.primaryBg,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    yAxisID: 'y'
                },
                {
                    label: 'Qtd Vendas',
                    data: chartData.quantidades,
                    borderColor: cores.success,
                    backgroundColor: 'rgba(25, 135, 84, 0.5)',
                    type: 'bar',
                    borderRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.dataset.yAxisID === 'y') {
                                return ctx.dataset.label + ': R$ ' + 
                                       ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                            return ctx.dataset.label + ': ' + ctx.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: val => 'R$ ' + val.toLocaleString('pt-BR')
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { display: false }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
})();
<?php endif; ?>
</script>