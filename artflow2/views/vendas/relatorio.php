<?php
/**
 * VIEW: Relatório de Vendas
 * GET /vendas/relatorio
 * 
 * Variáveis:
 * - $faturamento: Total faturado no período
 * - $vendasMensais: Array com vendas por mês
 * - $ranking: Ranking de rentabilidade
 * - $dataInicio: Data início do filtro
 * - $dataFim: Data fim do filtro
 */
$currentPage = 'vendas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-bar-chart-line text-primary"></i> Relatório de Vendas
        </h2>
        <p class="text-muted mb-0">Análise de performance e rentabilidade</p>
    </div>
    <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- Filtro de Período -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/vendas/relatorio') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" 
                       value="<?= e($dataInicio) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" 
                       value="<?= e($dataFim) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Faturamento</h6>
                        <h3 class="mb-0"><?= money($faturamento['total'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Total de Vendas</h6>
                        <h3 class="mb-0"><?= $faturamento['quantidade'] ?? 0 ?></h3>
                    </div>
                    <i class="bi bi-receipt fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Ticket Médio</h6>
                        <h3 class="mb-0"><?= money($faturamento['ticket_medio'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-calculator fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Gráfico de Vendas Mensais -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up text-primary"></i> Evolução Mensal
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($vendasMensais)): ?>
                    <canvas id="vendasChart" height="300"></canvas>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-bar-chart fs-1"></i>
                        <p class="mt-2">Sem dados para exibir</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Ranking de Rentabilidade -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-trophy text-warning"></i> Mais Rentáveis
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($ranking)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($ranking as $index => $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-<?= $index < 3 ? 'warning' : 'secondary' ?> rounded-pill">
                                        <?= $index + 1 ?>º
                                    </span>
                                    <span><?= e($item['nome'] ?? 'N/A') ?></span>
                                </div>
                                <div class="text-end">
                                    <div class="text-success fw-bold"><?= money($item['lucro'] ?? 0) ?></div>
                                    <small class="text-muted"><?= money($item['rentabilidade_hora'] ?? 0) ?>/h</small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-trophy fs-1"></i>
                        <p class="mt-2 mb-0">Nenhuma venda registrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script do Gráfico -->
<?php if (!empty($vendasMensais)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('vendasChart').getContext('2d');
    const dados = <?= json_encode($vendasMensais) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dados.map(d => d.mes),
            datasets: [{
                label: 'Faturamento',
                data: dados.map(d => d.total),
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgb(13, 110, 253)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>
