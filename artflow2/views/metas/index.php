<?php
/**
 * VIEW: Listagem de Metas
 * GET /metas
 * 
 * MELHORIA 2 (05/02/2026): Cards de Resumo Estatístico por Ano
 * MELHORIA 3 (05/02/2026): Gráfico de Evolução Anual (Chart.js)
 * 
 * Variáveis recebidas do Controller:
 *   $titulo           (string)  — título da página
 *   $metas            (array)   — lista de objetos Meta do ano selecionado
 *   $estatisticas     (array)   — estatísticas gerais (todas as metas)
 *   $anoSelecionado   (int)     — ano atualmente filtrado
 *   $anosDisponiveis  (array)   — anos disponíveis para o select
 *   $estatisticasAno  (array)   — NOVO: totais/médias do ano selecionado (Melhoria 2)
 *   $desempenhoAnual  (array)   — NOVO: 12 meses para gráfico (Melhoria 3)
 */
$currentPage = 'metas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-bullseye text-primary"></i> Metas
        </h2>
        <p class="text-muted mb-0">Acompanhe suas metas mensais</p>
    </div>
    <a href="<?= url('/metas/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Meta
    </a>
</div>

<!-- Meta Atual (Destaque) — só aparece se $metaAtual for passada pelo controller -->
<?php if (isset($metaAtual) && $metaAtual): ?>
    <div class="card border-primary mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-star-fill"></i> Meta do Mês Atual - <?= date('F/Y') ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <!-- Barra de Progresso -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Progresso</span>
                            <span class="fw-bold"><?= number_format($metaAtual['porcentagem'] ?? 0, 1) ?>%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <?php 
                            $porcentagem = min(100, $metaAtual['porcentagem'] ?? 0);
                            $corBarra = $porcentagem >= 100 ? 'success' : ($porcentagem >= 50 ? 'info' : 'warning');
                            ?>
                            <div class="progress-bar bg-<?= $corBarra ?>" 
                                 style="width: <?= $porcentagem ?>%;"
                                 role="progressbar">
                                <?= money($metaAtual['valor_realizado'] ?? 0) ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detalhes -->
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted">Meta</small>
                            <h5><?= money($metaAtual['valor_meta'] ?? 0) ?></h5>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Realizado</small>
                            <h5 class="text-success"><?= money($metaAtual['valor_realizado'] ?? 0) ?></h5>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Falta</small>
                            <h5 class="text-danger">
                                <?= money(max(0, ($metaAtual['valor_meta'] ?? 0) - ($metaAtual['valor_realizado'] ?? 0))) ?>
                            </h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <?php if (($metaAtual['porcentagem'] ?? 0) >= 100): ?>
                        <div class="display-1 text-success">🎉</div>
                        <h5 class="text-success">Meta Batida!</h5>
                    <?php else: ?>
                        <div class="display-4 text-primary">
                            <?= number_format($metaAtual['porcentagem'] ?? 0, 0) ?>%
                        </div>
                        <small class="text-muted">
                            <?php
                            $diasRestantes = (int)date('t') - (int)date('j');
                            $faltaVender = max(0, ($metaAtual['valor_meta'] ?? 0) - ($metaAtual['valor_realizado'] ?? 0));
                            $porDia = $diasRestantes > 0 ? $faltaVender / $diasRestantes : 0;
                            ?>
                            <?= $diasRestantes ?> dias restantes<br>
                            <?= money($porDia) ?>/dia para bater
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/metas') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Ano</label>
                <select name="ano" class="form-select">
                    <option value="">Todos os anos</option>
                    <?php foreach ($anosDisponiveis ?? [] as $ano): ?>
                        <option value="<?= $ano ?>" <?= ($anoSelecionado ?? '') == $ano ? 'selected' : '' ?>>
                            <?= $ano ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
                <a href="<?= url('/metas') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================
     MELHORIA 2: Cards de Estatísticas do Ano
     Exibe 4 cards com totais e médias do ano selecionado.
     Só aparece se existirem metas no ano.
     ===================================================== -->
<?php if (isset($estatisticasAno) && $estatisticasAno['total_metas'] > 0): ?>
<div class="row mb-4">
    <!-- Card 1: Total de Metas do ano -->
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="text-muted mb-1">
                    <i class="bi bi-bullseye"></i> Total de Metas
                </div>
                <h3 class="card-title mb-1"><?= $estatisticasAno['total_metas'] ?></h3>
                <small class="text-muted">
                    <!-- Mostra quantas foram superadas (>= 120%) -->
                    <?php if ($estatisticasAno['metas_superadas'] > 0): ?>
                        <span class="text-warning">
                            <i class="bi bi-trophy-fill"></i> <?= $estatisticasAno['metas_superadas'] ?> superada(s)
                        </span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Card 2: Metas Atingidas + Taxa de Sucesso -->
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="card text-center h-100 border-success">
            <div class="card-body">
                <div class="text-muted mb-1">
                    <i class="bi bi-check-circle"></i> Atingidas
                </div>
                <h3 class="card-title mb-1 text-success">
                    <?= $estatisticasAno['metas_atingidas'] ?>
                    <small class="fs-6">(<?= $estatisticasAno['taxa_sucesso'] ?>%)</small>
                </h3>
                <small class="text-muted">
                    <?= $estatisticasAno['metas_nao_atingidas'] ?> não atingida(s)
                </small>
            </div>
        </div>
    </div>
    
    <!-- Card 3: Média de Realização (porcentagem média) -->
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="text-muted mb-1">
                    <i class="bi bi-graph-up"></i> Média Realização
                </div>
                <?php 
                // Cor dinâmica baseada na média
                $mediaPct = $estatisticasAno['media_porcentagem'];
                $corMedia = $mediaPct >= 100 ? 'text-success' : ($mediaPct >= 70 ? 'text-primary' : ($mediaPct >= 40 ? 'text-warning' : 'text-danger'));
                ?>
                <h3 class="card-title mb-1 <?= $corMedia ?>">
                    <?= number_format($mediaPct, 1) ?>%
                </h3>
                <small class="text-muted">média do ano</small>
            </div>
        </div>
    </div>
    
    <!-- Card 4: Faturamento Total do Ano (realizado vs meta) -->
    <div class="col-md-3 col-sm-6">
        <div class="card text-center h-100 border-primary">
            <div class="card-body">
                <div class="text-muted mb-1">
                    <i class="bi bi-currency-dollar"></i> Faturamento <?= $anoSelecionado ?>
                </div>
                <h3 class="card-title mb-1 text-primary">
                    R$ <?= number_format($estatisticasAno['soma_realizado'], 2, ',', '.') ?>
                </h3>
                <small class="text-muted">
                    Meta: R$ <?= number_format($estatisticasAno['soma_metas'], 2, ',', '.') ?>
                </small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- =====================================================
     MELHORIA 3: Gráfico de Evolução Anual (Chart.js)
     Barras comparando meta vs realizado mês a mês.
     Só aparece se existirem metas no ano.
     ===================================================== -->
<?php if (isset($desempenhoAnual) && !empty($desempenhoAnual) && isset($estatisticasAno) && $estatisticasAno['total_metas'] > 0): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-bar-chart-fill me-2"></i>Evolução <?= $anoSelecionado ?>
        </h5>
        <small class="text-muted">Meta vs Realizado — mês a mês</small>
    </div>
    <div class="card-body">
        <!-- Canvas do gráfico — height controla a proporção -->
        <canvas id="graficoEvolucao" height="100"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Lista de Metas -->
<?php if (empty($metas)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-bullseye display-4 text-muted"></i>
            <h5 class="mt-3">Nenhuma meta cadastrada</h5>
            <p class="text-muted">Defina metas mensais para acompanhar seu desempenho.</p>
            <a href="<?= url('/metas/criar') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Criar Meta
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($metas as $meta): ?>
            <?php
            $porcentagem = min(100, $meta->getPorcentagemAtingida() ?? 0);
            $corBarra = $porcentagem >= 100 ? 'success' : ($porcentagem >= 50 ? 'info' : ($porcentagem >= 25 ? 'warning' : 'danger'));
            $mesAno = date('m/Y', strtotime($meta->getMesAno()));
            $isAtual = date('Y-m', strtotime($meta->getMesAno())) === date('Y-m');
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 <?= $isAtual ? 'border-primary' : '' ?>">
                    <?php if ($isAtual): ?>
                        <div class="card-header bg-primary text-white py-1 text-center">
                            <small>Mês Atual</small>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0"><?= $mesAno ?></h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="<?= url("/metas/{$meta->getId()}") ?>" class="dropdown-item">
                                            <i class="bi bi-eye"></i> Ver Detalhes
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?= url("/metas/{$meta->getId()}/editar") ?>" class="dropdown-item">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                    </li>
                                    <li>
                                        <form action="<?= url("/metas/{$meta->getId()}/recalcular") ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-arrow-clockwise"></i> Recalcular
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="confirmarExclusao(<?= $meta->getId() ?>)">
                                            <i class="bi bi-trash"></i> Excluir
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Progresso -->
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-<?= $corBarra ?>" 
                                 style="width: <?= $porcentagem ?>%;">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <span><?= number_format($porcentagem, 0) ?>%</span>
                            <span><?= money($meta->getValorRealizado()) ?> / <?= money($meta->getValorMeta()) ?></span>
                        </div>
                        
                        <!-- Status -->
                        <?php if ($porcentagem >= 100): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Meta Batida!
                            </span>
                        <?php elseif ($porcentagem >= 75): ?>
                            <span class="badge bg-info">
                                <i class="bi bi-graph-up-arrow"></i> Quase lá!
                            </span>
                        <?php elseif ($porcentagem >= 50): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-activity"></i> Em progresso
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">
                                <i class="bi bi-hourglass"></i> Iniciando
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta meta?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// =====================================================
// Script: Exclusão de Meta via Modal
// =====================================================
function confirmarExclusao(id) {
    document.getElementById('formExcluir').action = '<?= url('/metas') ?>/' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>

<!-- =====================================================
     MELHORIA 3: Script Chart.js — Gráfico de Evolução Anual
     
     Carrega Chart.js via CDN e renderiza gráfico de barras
     comparando Meta (azul) vs Realizado (verde) mês a mês.
     
     IMPORTANTE: O CDN do Chart.js é carregado APENAS se
     existirem dados para exibir (evita request desnecessário).
     ===================================================== -->
<?php if (isset($desempenhoAnual) && !empty($desempenhoAnual) && isset($estatisticasAno) && $estatisticasAno['total_metas'] > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/**
 * Gráfico de Evolução Anual — Meta vs Realizado
 * 
 * Dados vindos do PHP (MetaRepository::getDesempenhoAnual):
 * Array de 12 objetos com: mes, nome_mes, valor_meta, valor_realizado, porcentagem, status
 * Meses sem meta têm valor null → Chart.js ignora automaticamente barras null
 */
const desempenhoAnual = <?= json_encode($desempenhoAnual) ?>;

const ctx = document.getElementById('graficoEvolucao').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        // Labels: nomes abreviados dos meses (Jan, Fev, Mar, ...)
        labels: desempenhoAnual.map(d => d.nome_mes),
        datasets: [
            {
                // Dataset 1: Valor da Meta (barras azuis)
                label: 'Meta',
                data: desempenhoAnual.map(d => d.valor_meta),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',  // Azul semi-transparente
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                // Dataset 2: Valor Realizado (barras verdes)
                label: 'Realizado',
                data: desempenhoAnual.map(d => d.valor_realizado),
                backgroundColor: 'rgba(75, 192, 92, 0.6)',   // Verde semi-transparente
                borderColor: 'rgba(75, 192, 92, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { 
                position: 'top' 
            },
            tooltip: {
                callbacks: {
                    // Tooltip customizado: exibe "Sem meta" para meses null
                    label: function(context) {
                        let value = context.raw;
                        if (value === null) return 'Sem meta';
                        return context.dataset.label + ': R$ ' + 
                               value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    // Eixo Y formatado em R$
                    callback: function(value) {
                        return 'R$ ' + value.toLocaleString('pt-BR');
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>