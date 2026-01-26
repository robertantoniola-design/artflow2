<?php
/**
 * Dashboard - Página Inicial
 * 
 * Variáveis disponíveis:
 * - $artesStats: Estatísticas de artes
 * - $vendasMes: Vendas do mês atual
 * - $faturamentoMes: Total faturado no mês
 * - $metaAtual: Meta do mês com progresso
 * - $topClientes: Melhores clientes
 * - $artesDisponiveis: Artes prontas para venda
 * - $vendasMensais: Dados para gráfico
 * - $maisRentaveis: Ranking de rentabilidade
 */
$currentPage = 'dashboard';
?>

<!-- Cards de Estatísticas Principais -->
<div class="row g-3 mb-4">
    <!-- Total de Artes -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Total de Artes</p>
                    <h3 class="stat-value" data-stat="total_artes">
                        <?= $artesStats['total'] ?? 0 ?>
                    </h3>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-brush"></i>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-success">
                    <i class="bi bi-check-circle"></i>
                    <?= $artesStats['disponiveis'] ?? 0 ?> disponíveis
                </small>
            </div>
        </div>
    </div>
    
    <!-- Artes em Produção -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Em Produção</p>
                    <h3 class="stat-value text-warning">
                        <?= $artesStats['em_producao'] ?? 0 ?>
                    </h3>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-hourglass-split"></i>
                    Média: <?= number_format($artesStats['media_horas'] ?? 0, 1) ?>h por arte
                </small>
            </div>
        </div>
    </div>
    
    <!-- Vendas do Mês -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Vendas do Mês</p>
                    <h3 class="stat-value text-success" data-stat="vendas_mes">
                        <?= money($faturamentoMes ?? 0) ?>
                    </h3>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-receipt"></i>
                    <?= count($vendasMes ?? []) ?> vendas realizadas
                </small>
            </div>
        </div>
    </div>
    
    <!-- Meta do Mês -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Meta do Mês</p>
                    <h3 class="stat-value">
                        <?= number_format($metaAtual['porcentagem'] ?? 0, 0) ?>%
                    </h3>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-bullseye"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar meta-progress" 
                         role="progressbar" 
                         style="width: <?= min($metaAtual['porcentagem'] ?? 0, 100) ?>%"
                         aria-valuenow="<?= $metaAtual['porcentagem'] ?? 0 ?>">
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    <?= money($metaAtual['valor_realizado'] ?? 0) ?> / <?= money($metaAtual['valor_meta'] ?? 0) ?>
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Gráfico de Vendas Mensais -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>
                    Vendas Mensais
                </h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary active">6 meses</button>
                    <button class="btn btn-outline-secondary">12 meses</button>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="vendasChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Clientes -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-trophy me-2 text-warning"></i>
                    Top Clientes
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($topClientes)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topClientes as $index => $cliente): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-<?= $index < 3 ? 'warning' : 'secondary' ?> rounded-pill">
                                        <?= $index + 1 ?>º
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?= e($cliente['nome']) ?></div>
                                        <small class="text-muted">
                                            <?= $cliente['total_compras'] ?? 0 ?> compras
                                        </small>
                                    </div>
                                </div>
                                <span class="text-success fw-bold">
                                    <?= money($cliente['total_gasto'] ?? 0) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-people"></i>
                        <p class="mb-0">Nenhuma venda ainda</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Artes Disponíveis para Venda -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-bag-check me-2 text-success"></i>
                    Prontas para Venda
                </h5>
                <a href="<?= url('/artes?status=disponivel') ?>" class="btn btn-sm btn-outline-primary">
                    Ver todas
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($artesDisponiveis)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Arte</th>
                                    <th>Complexidade</th>
                                    <th>Horas</th>
                                    <th>Custo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($artesDisponiveis, 0, 5) as $arte): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/artes/' . $arte->getId()) ?>" class="text-decoration-none">
                                                <?= e($arte->getNome()) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $arte->getComplexidade() === 'alta' ? 'danger' : ($arte->getComplexidade() === 'media' ? 'warning' : 'success') ?>-subtle text-<?= $arte->getComplexidade() === 'alta' ? 'danger' : ($arte->getComplexidade() === 'media' ? 'warning' : 'success') ?>">
                                                <?= ucfirst($arte->getComplexidade()) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($arte->getHorasTrabalhadas(), 1) ?>h</td>
                                        <td><?= money($arte->getPrecoCusto()) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-brush"></i>
                        <p class="mb-0">Nenhuma arte disponível</p>
                        <a href="<?= url('/artes/criar') ?>" class="btn btn-primary btn-sm mt-2">
                            <i class="bi bi-plus"></i> Criar Arte
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Ranking de Rentabilidade -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-currency-dollar me-2 text-success"></i>
                    Mais Rentáveis
                </h5>
                <a href="<?= url('/vendas/relatorio') ?>" class="btn btn-sm btn-outline-primary">
                    Relatório
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($maisRentaveis)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Arte</th>
                                    <th>Lucro</th>
                                    <th>R$/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maisRentaveis as $item): ?>
                                    <tr>
                                        <td><?= e($item['nome'] ?? 'N/A') ?></td>
                                        <td class="text-success fw-bold">
                                            <?= money($item['lucro'] ?? 0) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <?= money($item['rentabilidade_hora'] ?? 0) ?>/h
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-graph-up"></i>
                        <p class="mb-0">Nenhuma venda registrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script para inicializar gráfico -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados do gráfico
    const vendasMensais = <?= json_encode($vendasMensais ?? []) ?>;
    
    if (vendasMensais.length > 0) {
        Dashboard.initVendasChart('vendasChart', vendasMensais);
    }
    
    // Auto-refresh a cada 5 minutos
    setInterval(function() {
        Dashboard.refresh();
    }, 300000);
});
</script>
