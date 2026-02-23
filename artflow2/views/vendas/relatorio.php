<?php
/**
 * VIEW: Relatório de Vendas
 * GET /vendas/relatorio
 * 
 * Variáveis:
 * - $vendasMensais: Array com vendas por mês
 * - $estatisticas: Estatísticas gerais
 * - $rankingRentabilidade: Top artes mais rentáveis
 * - $filtros: Filtros aplicados
 */
$currentPage = 'vendas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-graph-up text-primary"></i> Relatório de Vendas
        </h2>
        <p class="text-muted mb-0">Análise de performance e rentabilidade</p>
    </div>
    <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- Cards de Resumo -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Total Vendas</h6>
                        <h3 class="mb-0"><?= $estatisticas['total_vendas'] ?? $estatisticas['total'] ?? 0 ?></h3>
                    </div>
                    <i class="bi bi-cart-check display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Faturamento</h6>
                        <h3 class="mb-0"><?= money($estatisticas['valor_total'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Lucro Total</h6>
                        <h3 class="mb-0"><?= money($estatisticas['lucro_total'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-piggy-bank display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Ticket Médio</h6>
                        <h3 class="mb-0"><?= money($estatisticas['ticket_medio'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-receipt display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Vendas Mensais -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-bar-chart"></i> Vendas por Mês
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($vendasMensais)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Mês</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-end">Lucro</th>
                                    <th style="width: 40%">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxValor = max(array_column($vendasMensais, 'total') ?: [1]);
                                foreach ($vendasMensais as $mes): 
                                    $percentual = $maxValor > 0 ? ($mes['total'] / $maxValor) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?= $mes['mes_nome'] ?? $mes['mes'] ?? '-' ?></td>
                                        <td class="text-center"><?= $mes['quantidade'] ?? 0 ?></td>
                                        <td class="text-end"><?= money($mes['total'] ?? 0) ?></td>
                                        <td class="text-end"><?= money($mes['lucro'] ?? 0) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?= $percentual ?>%">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-5">Nenhum dado de vendas disponível</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Ranking de Rentabilidade -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-trophy"></i> Top Artes Rentáveis
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($rankingRentabilidade)): ?>
                    <ol class="list-group list-group-numbered">
                        <?php foreach ($rankingRentabilidade as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">
                                        <?= e($item['arte_nome'] ?? $item['nome'] ?? 'Arte') ?>
                                    </div>
                                    <small class="text-muted">
                                        Vendida por <?= money($item['valor'] ?? 0) ?>
                                    </small>
                                </div>
                                <span class="badge bg-success rounded-pill">
                                    <?= money($item['rentabilidade_hora'] ?? 0) ?>/h
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="text-muted text-center py-3">Nenhuma venda registrada</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dicas -->
<div class="card">
    <div class="card-body">
        <h6><i class="bi bi-lightbulb text-warning"></i> Dicas de Análise</h6>
        <ul class="mb-0">
            <li><strong>Rentabilidade/Hora</strong>: Indica quanto você ganha por hora trabalhada. Artes com alta rentabilidade são mais lucrativas.</li>
            <li><strong>Ticket Médio</strong>: Valor médio por venda. Tente aumentá-lo oferecendo artes de maior valor.</li>
            <li><strong>Lucro</strong>: Considere seus custos de material ao definir preços.</li>
        </ul>
    </div>
</div>
