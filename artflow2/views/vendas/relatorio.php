<?php
/**
 * ============================================
 * VIEW: Relatório de Vendas — MELHORIA 4 (Aprimorado)
 * GET /vendas/relatorio
 * ============================================
 * 
 * SUBSTITUIÇÃO COMPLETA da view anterior.
 * 
 * VARIÁVEIS RECEBIDAS DO CONTROLLER:
 * - $estatisticas          : array — stats filtradas (total, faturamento, lucro, ticket, margem)
 * - $vendasMensais         : array — dados mensais para gráfico + tabela comparativa
 * - $vendasDetalhadas      : array — vendas com JOINs (arte, cliente) para tabela
 * - $distribuicaoPgto      : array — formas pagamento para doughnut
 * - $rankingRentabilidade  : array — top 10 vendas mais rentáveis
 * - $anosDisponiveis       : array — anos para dropdown filtro
 * - $periodoAplicado       : array — período efetivamente filtrado
 * - $filtros               : array — filtros da URL (data_inicio, data_fim, ano)
 * 
 * PADRÕES VISUAIS APLICADOS:
 * - Cards border-start-4: Tags M5, Artes M5, Vendas M5
 * - Chart.js 4.4.7 condicional: Tags M6, Artes M6, Vendas M6
 * - Collapse + chart.resize(): Artes M6, Vendas M6
 * - Tabela responsiva com hover: padrão Bootstrap 5
 */
$currentPage = 'vendas';

// ── Extrai estatísticas com defaults seguros ──
$totalVendas       = (int)   ($estatisticas['total_vendas']       ?? 0);
$valorTotal        = (float) ($estatisticas['valor_total']        ?? 0);
$ticketMedio       = (float) ($estatisticas['ticket_medio']       ?? 0);
$lucroTotal        = (float) ($estatisticas['lucro_total']        ?? 0);
$margemMedia       = (float) ($estatisticas['margem_media']       ?? 0);
$rentabilidadeMedia = (float)($estatisticas['rentabilidade_media'] ?? 0);

// ── Período aplicado para label informativo ──
$periodo = $periodoAplicado ?? [];
$temFiltro = !empty($periodo['data_inicio']) || !empty($periodo['data_fim']) || !empty($periodo['ano']);

// ── Labels de forma de pagamento (reutilizado do index/show) ──
$formasPagamento = [
    'dinheiro'       => 'Dinheiro',
    'pix'            => 'PIX',
    'cartao_credito' => 'Cartão de Crédito',
    'cartao_debito'  => 'Cartão de Débito',
    'transferencia'  => 'Transferência',
    'outro'          => 'Outro'
];

// ── Nomes dos meses em PT-BR ──
$nomesMeses = [
    '01' => 'Janeiro',   '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril',     '05' => 'Maio',      '06' => 'Junho',
    '07' => 'Julho',     '08' => 'Agosto',     '09' => 'Setembro',
    '10' => 'Outubro',   '11' => 'Novembro',   '12' => 'Dezembro'
];
?>

<!-- ============================================ -->
<!-- BREADCRUMB                                   -->
<!-- ============================================ -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/vendas') ?>">Vendas</a></li>
        <li class="breadcrumb-item active">Relatório</li>
    </ol>
</nav>

<!-- ============================================ -->
<!-- HEADER                                       -->
<!-- ============================================ -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1">
            <i class="bi bi-graph-up text-primary"></i> Relatório de Vendas
        </h1>
        <p class="text-muted mb-0">
            <?php if ($temFiltro): ?>
                <?php if (!empty($periodo['ano']) && empty($filtros['data_inicio'])): ?>
                    Ano: <strong><?= e($periodo['ano']) ?></strong>
                <?php else: ?>
                    Período: <strong><?= date('d/m/Y', strtotime($periodo['data_inicio'])) ?></strong>
                    a <strong><?= date('d/m/Y', strtotime($periodo['data_fim'])) ?></strong>
                <?php endif; ?>
                — <?= $totalVendas ?> venda<?= $totalVendas != 1 ? 's' : '' ?>
            <?php else: ?>
                Visão geral de todas as vendas — <?= $totalVendas ?> venda<?= $totalVendas != 1 ? 's' : '' ?> registrada<?= $totalVendas != 1 ? 's' : '' ?>
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- ============================================ -->
<!-- FILTROS                                      -->
<!-- ============================================ -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/vendas/relatorio') ?>" method="GET" class="row g-3 align-items-end">
            
            <!-- Filtro: Ano -->
            <div class="col-md-2">
                <label class="form-label">
                    <i class="bi bi-calendar-event"></i> Ano
                </label>
                <select name="ano" class="form-select" id="filtroAno">
                    <option value="">Todos</option>
                    <?php foreach ($anosDisponiveis ?? [] as $ano): ?>
                        <option value="<?= $ano ?>" <?= ($filtros['ano'] ?? '') == $ano ? 'selected' : '' ?>>
                            <?= $ano ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro: Data Início -->
            <div class="col-md-3">
                <label class="form-label">
                    <i class="bi bi-calendar-plus"></i> Data Início
                </label>
                <input type="date" name="data_inicio" class="form-control" id="filtroDataInicio"
                       value="<?= e($filtros['data_inicio'] ?? '') ?>">
            </div>
            
            <!-- Filtro: Data Fim -->
            <div class="col-md-3">
                <label class="form-label">
                    <i class="bi bi-calendar-minus"></i> Data Fim
                </label>
                <input type="date" name="data_fim" class="form-control" id="filtroDataFim"
                       value="<?= e($filtros['data_fim'] ?? '') ?>">
            </div>
            
            <!-- Botões -->
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="<?= url('/vendas/relatorio') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- 5 CARDS DE RESUMO                            -->
<!-- ============================================ -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total de Vendas -->
    <div class="col">
        <div class="card h-100 border-start border-start-4 border-primary">
            <div class="card-body py-3 text-center">
                <small class="text-muted d-block"><i class="bi bi-cart-check"></i> Total Vendas</small>
                <span class="fs-3 fw-bold text-primary"><?= $totalVendas ?></span>
            </div>
        </div>
    </div>
    
    <!-- Card 2: Faturamento -->
    <div class="col">
        <div class="card h-100 border-start border-start-4 border-success">
            <div class="card-body py-3 text-center">
                <small class="text-muted d-block"><i class="bi bi-currency-dollar"></i> Faturamento</small>
                <span class="fs-4 fw-bold text-success"><?= money($valorTotal) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Card 3: Lucro -->
    <div class="col">
        <div class="card h-100 border-start border-start-4 border-<?= $lucroTotal >= 0 ? 'info' : 'danger' ?>">
            <div class="card-body py-3 text-center">
                <small class="text-muted d-block"><i class="bi bi-piggy-bank"></i> Lucro Total</small>
                <span class="fs-4 fw-bold text-<?= $lucroTotal >= 0 ? 'info' : 'danger' ?>"><?= money($lucroTotal) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Card 4: Ticket Médio -->
    <div class="col">
        <div class="card h-100 border-start border-start-4 border-warning">
            <div class="card-body py-3 text-center">
                <small class="text-muted d-block"><i class="bi bi-receipt"></i> Ticket Médio</small>
                <span class="fs-4 fw-bold text-warning"><?= money($ticketMedio) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Card 5: Margem Média -->
    <div class="col">
        <?php $corMargem = $margemMedia >= 30 ? 'success' : ($margemMedia >= 15 ? 'warning' : 'danger'); ?>
        <div class="card h-100 border-start border-start-4 border-<?= $corMargem ?>">
            <div class="card-body py-3 text-center">
                <small class="text-muted d-block"><i class="bi bi-percent"></i> Margem Média</small>
                <span class="fs-4 fw-bold text-<?= $corMargem ?>"><?= number_format($margemMedia, 1, ',', '.') ?>%</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- GRÁFICOS CHART.JS (collapse)                 -->
<!-- Padrão: Vendas M6 / Artes M6                 -->
<!-- ============================================ -->
<?php
// Prepara dados para os gráficos
$temDadosGrafico = !empty($vendasMensais) || !empty($distribuicaoPgto);

// Labels e cores para doughnut
$labelsPgto = [];
$coresPgto  = [
    'pix'            => '#198754', // verde (success)
    'dinheiro'       => '#0d6efd', // azul (primary)
    'cartao_credito' => '#ffc107', // amarelo (warning)
    'cartao_debito'  => '#0dcaf0', // ciano (info)
    'transferencia'  => '#6c757d', // cinza (secondary)
    'outro'          => '#212529'  // escuro (dark)
];
?>

<?php if ($temDadosGrafico): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center"
         data-bs-toggle="collapse" data-bs-target="#collapseGraficosRelatorio"
         role="button" aria-expanded="true" style="cursor: pointer;">
        <h6 class="mb-0">
            <i class="bi bi-bar-chart"></i> Gráficos
        </h6>
        <i class="bi bi-chevron-down" id="iconCollapseGrafRelatorio"></i>
    </div>
    <div class="collapse show" id="collapseGraficosRelatorio">
        <div class="card-body">
            <div class="row">
                <!-- Gráfico Barras: Faturamento + Lucro Mensal -->
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <h6 class="text-center text-muted mb-2">Faturamento e Lucro Mensal</h6>
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartRelatorioMensal"></canvas>
                    </div>
                </div>
                
                <!-- Gráfico Doughnut: Formas de Pagamento -->
                <div class="col-lg-4">
                    <h6 class="text-center text-muted mb-2">Formas de Pagamento</h6>
                    <div style="height: 220px; position: relative;">
                        <canvas id="chartRelatorioPgto"></canvas>
                    </div>
                    <!-- Legenda manual (padrão Tags M6) -->
                    <?php if (!empty($distribuicaoPgto)): ?>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                        <?php foreach ($distribuicaoPgto as $item): 
                            $forma = $item['forma_pagamento'] ?? 'outro';
                            $label = $formasPagamento[$forma] ?? ucfirst($forma);
                            $cor   = $coresPgto[$forma] ?? '#6c757d';
                        ?>
                        <small class="d-flex align-items-center">
                            <span class="d-inline-block rounded-circle me-1"
                                  style="width:10px; height:10px; background:<?= $cor ?>"></span>
                            <?= $label ?> (<?= $item['total'] ?>)
                        </small>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- TABELA: COMPARATIVO MENSAL                   -->
<!-- ============================================ -->
<?php if (!empty($vendasMensais)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-calendar3"></i> Comparativo Mensal</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mês</th>
                        <th class="text-center">Qtd</th>
                        <th class="text-end">Faturamento</th>
                        <th class="text-end">Lucro</th>
                        <th class="text-end">Ticket Médio</th>
                        <th class="text-end">Margem</th>
                        <th class="text-center">Evolução</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $fatAnterior = null;
                    foreach ($vendasMensais as $mes):
                        // Extrai mês/ano para label PT-BR
                        $partes = explode('-', $mes['mes'] ?? '');
                        $anoLabel = $partes[0] ?? '';
                        $mesNum   = $partes[1] ?? '';
                        $mesLabel = ($nomesMeses[$mesNum] ?? $mesNum) . '/' . $anoLabel;
                        
                        $fat    = (float)($mes['faturamento'] ?? 0);
                        $luc    = (float)($mes['lucro'] ?? 0);
                        $qtd    = (int)($mes['quantidade'] ?? 0);
                        $ticket = (float)($mes['ticket_medio'] ?? 0);
                        $margem = (float)($mes['margem_media'] ?? 0);
                        
                        // Calcula evolução vs mês anterior
                        $evolucao = null;
                        if ($fatAnterior !== null && $fatAnterior > 0) {
                            $evolucao = (($fat - $fatAnterior) / $fatAnterior) * 100;
                        }
                        $fatAnterior = $fat;
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= $mesLabel ?></td>
                        <td class="text-center"><?= $qtd ?></td>
                        <td class="text-end"><?= money($fat) ?></td>
                        <td class="text-end text-<?= $luc >= 0 ? 'success' : 'danger' ?>">
                            <?= money($luc) ?>
                        </td>
                        <td class="text-end"><?= money($ticket) ?></td>
                        <td class="text-end">
                            <?php $corM = $margem >= 30 ? 'success' : ($margem >= 15 ? 'warning' : 'danger'); ?>
                            <span class="text-<?= $corM ?>"><?= number_format($margem, 1, ',', '.') ?>%</span>
                        </td>
                        <td class="text-center">
                            <?php if ($evolucao !== null): ?>
                                <?php $corEv = $evolucao >= 0 ? 'success' : 'danger'; ?>
                                <span class="badge bg-<?= $corEv ?>-subtle text-<?= $corEv ?>">
                                    <i class="bi bi-arrow-<?= $evolucao >= 0 ? 'up' : 'down' ?>"></i>
                                    <?= $evolucao >= 0 ? '+' : '' ?><?= number_format($evolucao, 1, ',', '.') ?>%
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <!-- Totais -->
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>Total</td>
                        <td class="text-center"><?= $totalVendas ?></td>
                        <td class="text-end"><?= money($valorTotal) ?></td>
                        <td class="text-end text-<?= $lucroTotal >= 0 ? 'success' : 'danger' ?>">
                            <?= money($lucroTotal) ?>
                        </td>
                        <td class="text-end"><?= money($ticketMedio) ?></td>
                        <td class="text-end">
                            <?php $corMT = $margemMedia >= 30 ? 'success' : ($margemMedia >= 15 ? 'warning' : 'danger'); ?>
                            <span class="text-<?= $corMT ?>"><?= number_format($margemMedia, 1, ',', '.') ?>%</span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- ============================================ -->
    <!-- TABELA: VENDAS DETALHADAS (8 colunas)        -->
    <!-- ============================================ -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-table"></i> Vendas Detalhadas</h6>
                <span class="badge bg-secondary"><?= count($vendasDetalhadas ?? []) ?> registros</span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($vendasDetalhadas)): ?>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Data</th>
                                <th>Arte</th>
                                <th>Cliente</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end">Lucro</th>
                                <th class="text-end">R$/h</th>
                                <th class="text-center">Pgto</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendasDetalhadas as $v): 
                                $vValor = (float)($v['valor'] ?? 0);
                                $vLucro = (float)($v['lucro_calculado'] ?? 0);
                                $vRent  = (float)($v['rentabilidade_hora'] ?? 0);
                                $vForma = $v['forma_pagamento'] ?? 'outro';
                            ?>
                            <tr>
                                <td class="text-nowrap">
                                    <?= !empty($v['data_venda']) ? date('d/m/Y', strtotime($v['data_venda'])) : '—' ?>
                                </td>
                                <td>
                                    <?= e($v['arte_nome'] ?? 'Arte removida') ?>
                                </td>
                                <td>
                                    <?= e($v['cliente_nome'] ?? '—') ?>
                                </td>
                                <td class="text-end fw-semibold"><?= money($vValor) ?></td>
                                <td class="text-end text-<?= $vLucro >= 0 ? 'success' : 'danger' ?>">
                                    <?= money($vLucro) ?>
                                </td>
                                <td class="text-end">
                                    <?= $vRent > 0 ? money($vRent) . '/h' : '—' ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $badgeClasses = [
                                        'pix'            => 'bg-success',
                                        'dinheiro'       => 'bg-primary',
                                        'cartao_credito' => 'bg-warning text-dark',
                                        'cartao_debito'  => 'bg-info text-dark',
                                        'transferencia'  => 'bg-secondary',
                                        'outro'          => 'bg-dark'
                                    ];
                                    $badgeClass = $badgeClasses[$vForma] ?? 'bg-secondary';
                                    $labelPgto  = $formasPagamento[$vForma] ?? ucfirst($vForma);
                                    ?>
                                    <span class="badge <?= $badgeClass ?>" title="<?= $labelPgto ?>">
                                        <?= $labelPgto ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= url('/vendas/' . $v['id']) ?>" 
                                       class="btn btn-outline-primary btn-sm" title="Ver detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                    <p>Nenhuma venda encontrada no período selecionado</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- RANKING TOP 10 RENTÁVEIS (4 colunas)         -->
    <!-- ============================================ -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-trophy text-warning"></i> Top 10 — Rentabilidade</h6>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($rankingRentabilidade)): ?>
                <ol class="list-group list-group-numbered list-group-flush">
                    <?php foreach ($rankingRentabilidade as $i => $item): 
                        $rValor = (float)($item['valor'] ?? 0);
                        $rRent  = (float)($item['rentabilidade_hora'] ?? 0);
                        // Medalha para top 3
                        $medalha = '';
                        if ($i === 0) $medalha = '🥇';
                        elseif ($i === 1) $medalha = '🥈';
                        elseif ($i === 2) $medalha = '🥉';
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                        <div class="ms-2 me-auto">
                            <div class="fw-semibold">
                                <?= $medalha ?> <?= e($item['arte_nome'] ?? 'Arte') ?>
                            </div>
                            <small class="text-muted">
                                Venda: <?= money($rValor) ?>
                                <?php if (!empty($item['cliente_nome'])): ?>
                                    — <?= e($item['cliente_nome']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <span class="badge bg-success rounded-pill">
                            <?= money($rRent) ?>/h
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-trophy display-4 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">Nenhuma venda com rentabilidade</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- DICAS DE ANÁLISE                             -->
<!-- ============================================ -->
<div class="card">
    <div class="card-body">
        <h6><i class="bi bi-lightbulb text-warning"></i> Dicas de Análise</h6>
        <div class="row">
            <div class="col-md-4">
                <p class="small mb-0">
                    <strong>Margem de Lucro:</strong> Acima de 30% é excelente. 
                    Entre 15-30% é aceitável. Abaixo de 15% requer atenção nos custos.
                </p>
            </div>
            <div class="col-md-4">
                <p class="small mb-0">
                    <strong>Evolução Mensal:</strong> Acompanhe a tendência mês a mês. 
                    Quedas consecutivas indicam necessidade de ação.
                </p>
            </div>
            <div class="col-md-4">
                <p class="small mb-0">
                    <strong>R$/Hora:</strong> Artes com alta rentabilidade por hora 
                    são as mais eficientes. Priorize esse tipo de trabalho.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CHART.JS — Scripts                           -->
<!-- Padrão: CDN condicional + container fixo     -->
<!-- ============================================ -->
<?php if ($temDadosGrafico): ?>
<script>
// ── Carrega Chart.js 4.4.7 se não existir (padrão Tags M6 / Vendas M6) ──
if (typeof Chart === 'undefined') {
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
    s.onload = function() { initRelatorioCharts(); };
    document.head.appendChild(s);
} else {
    document.addEventListener('DOMContentLoaded', initRelatorioCharts);
}

function initRelatorioCharts() {
    // ═══════════════════════════════════════════
    // GRÁFICO 1: Barras — Faturamento + Lucro Mensal
    // ═══════════════════════════════════════════
    <?php if (!empty($vendasMensais)): ?>
    (function() {
        var ctx = document.getElementById('chartRelatorioMensal');
        if (!ctx) return;
        
        // Prepara labels (meses) e dados
        var labels = [<?php 
            echo implode(',', array_map(function($m) use ($nomesMeses) {
                $partes = explode('-', $m['mes'] ?? '');
                $mesNum = $partes[1] ?? '';
                $mesAbrev = mb_substr($nomesMeses[$mesNum] ?? $mesNum, 0, 3);
                $ano = substr($partes[0] ?? '', 2, 2);
                return "'" . $mesAbrev . "/" . $ano . "'";
            }, $vendasMensais));
        ?>];
        
        var faturamento = [<?= implode(',', array_map(fn($m) => round((float)($m['faturamento'] ?? 0), 2), $vendasMensais)) ?>];
        var lucro = [<?= implode(',', array_map(fn($m) => round((float)($m['lucro'] ?? 0), 2), $vendasMensais)) ?>];
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Faturamento (R$)',
                        data: faturamento,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Lucro (R$)',
                        data: lucro,
                        backgroundColor: 'rgba(25, 135, 84, 0.7)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
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
                            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                        }
                    }
                }
            }
        });
    })();
    <?php endif; ?>
    
    // ═══════════════════════════════════════════
    // GRÁFICO 2: Doughnut — Formas de Pagamento
    // ═══════════════════════════════════════════
    <?php if (!empty($distribuicaoPgto)): ?>
    (function() {
        var ctx = document.getElementById('chartRelatorioPgto');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    echo implode(',', array_map(function($d) use ($formasPagamento) {
                        $label = $formasPagamento[$d['forma_pagamento']] ?? ucfirst($d['forma_pagamento']);
                        return "'" . addslashes($label) . "'";
                    }, $distribuicaoPgto));
                ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($distribuicaoPgto, 'total')) ?>],
                    backgroundColor: [<?php 
                        echo implode(',', array_map(function($d) use ($coresPgto) {
                            $cor = $coresPgto[$d['forma_pagamento']] ?? '#6c757d';
                            return "'" . $cor . "'";
                        }, $distribuicaoPgto));
                    ?>]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } // Usa legenda manual HTML
                }
            }
        });
    })();
    <?php endif; ?>
}

// ── Collapse handler: resize gráficos ao reabrir (padrão Artes M6) ──
document.addEventListener('DOMContentLoaded', function() {
    var collapseEl = document.getElementById('collapseGraficosRelatorio');
    if (collapseEl) {
        collapseEl.addEventListener('shown.bs.collapse', function() {
            // Força resize de todos os charts na seção
            Chart.helpers.each(Chart.instances, function(instance) {
                instance.resize();
            });
            // Alterna ícone da seta
            var icon = document.getElementById('iconCollapseGrafRelatorio');
            if (icon) icon.className = 'bi bi-chevron-up';
        });
        collapseEl.addEventListener('hidden.bs.collapse', function() {
            var icon = document.getElementById('iconCollapseGrafRelatorio');
            if (icon) icon.className = 'bi bi-chevron-down';
        });
    }
});

// ── Lógica: Ao selecionar ano, limpa campos de data (e vice-versa) ──
document.addEventListener('DOMContentLoaded', function() {
    var filtroAno = document.getElementById('filtroAno');
    var filtroDataInicio = document.getElementById('filtroDataInicio');
    var filtroDataFim = document.getElementById('filtroDataFim');
    
    if (filtroAno && filtroDataInicio && filtroDataFim) {
        // Ao mudar o ano, limpa as datas
        filtroAno.addEventListener('change', function() {
            if (this.value) {
                filtroDataInicio.value = '';
                filtroDataFim.value = '';
            }
        });
        
        // Ao definir data, limpa o ano
        filtroDataInicio.addEventListener('change', function() {
            if (this.value) filtroAno.value = '';
        });
        filtroDataFim.addEventListener('change', function() {
            if (this.value) filtroAno.value = '';
        });
    }
});
</script>
<?php endif; ?>