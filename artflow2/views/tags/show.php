<?php
/**
 * View: Detalhes da Tag
 * Exibe informações da tag e lista artes associadas
 */

// Variáveis: $tag, $artes
$totalArtes = count($artes ?? []);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/tags">Tags</a></li>
        <li class="breadcrumb-item active"><?= e($tag->getNome()) ?></li>
    </ol>
</nav>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <span class="badge fs-3 py-2 px-3" style="background-color: <?= e($tag->getCor()) ?>">
            <?= e($tag->getNome()) ?>
        </span>
        <span class="text-muted"><?= $totalArtes ?> arte(s) associada(s)</span>
    </div>
    
    <div class="btn-group">
        <a href="/tags/<?= $tag->getId() ?>/editar" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="/tags" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="row">
    <!-- Artes Associadas -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-palette"></i> Artes com esta Tag
                </h5>
                <a href="/artes/criar?tag=<?= $tag->getId() ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus"></i> Nova Arte
                </a>
            </div>
            
            <?php if (empty($artes)): ?>
                <div class="card-body text-center py-5">
                    <i class="bi bi-palette text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">Nenhuma arte com esta tag ainda.</p>
                    <a href="/artes/criar?tag=<?= $tag->getId() ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-plus"></i> Criar Primeira Arte
                    </a>
                </div>
            <?php else: ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th>Complexidade</th>
                                    <th class="text-end">Custo</th>
                                    <th class="text-end">Horas</th>
                                    <th width="80"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($artes as $arte): ?>
                                    <?php
                                    $status = $arte->getStatus();
                                    $statusClass = match($status) {
                                        'disponivel' => 'success',
                                        'em_producao' => 'warning',
                                        'vendida' => 'info',
                                        default => 'secondary'
                                    };
                                    $statusLabel = match($status) {
                                        'disponivel' => 'Disponível',
                                        'em_producao' => 'Em Produção',
                                        'vendida' => 'Vendida',
                                        default => $status
                                    };
                                    
                                    $complexidade = $arte->getComplexidade();
                                    $compClass = match($complexidade) {
                                        'baixa' => 'success',
                                        'media' => 'warning',
                                        'alta' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="/artes/<?= $arte->getId() ?>" class="text-decoration-none fw-medium">
                                                <?= e($arte->getNome()) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $compClass ?>">
                                                <?= ucfirst($complexidade ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            R$ <?= number_format($arte->getPrecoCusto(), 2, ',', '.') ?>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format($arte->getHorasTrabalhadas(), 1, ',', '.') ?>h
                                        </td>
                                        <td>
                                            <a href="/artes/<?= $arte->getId() ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar Estatísticas -->
    <div class="col-lg-4">
        <!-- Card Tag -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <span class="badge fs-1 py-3 px-4" style="background-color: <?= e($tag->getCor()) ?>">
                        <?= e($tag->getNome()) ?>
                    </span>
                </div>
                <p class="text-muted mb-0">Cor: <?= e($tag->getCor()) ?></p>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Estatísticas</h6>
            </div>
            <div class="card-body">
                <?php
                // Calcular estatísticas
                $totalArtes = count($artes);
                $disponiveis = 0;
                $emProducao = 0;
                $vendidas = 0;
                $totalHoras = 0;
                $totalCusto = 0;
                
                foreach ($artes as $arte) {
                    switch ($arte->getStatus()) {
                        case 'disponivel': $disponiveis++; break;
                        case 'em_producao': $emProducao++; break;
                        case 'vendida': $vendidas++; break;
                    }
                    $totalHoras += $arte->getHorasTrabalhadas();
                    $totalCusto += $arte->getPrecoCusto();
                }
                ?>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Total de Artes</span>
                        <strong><?= $totalArtes ?></strong>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between small">
                        <span class="text-success">● Disponíveis</span>
                        <span><?= $disponiveis ?></span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-warning">● Em Produção</span>
                        <span><?= $emProducao ?></span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-info">● Vendidas</span>
                        <span><?= $vendidas ?></span>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Total Horas</span>
                        <strong><?= number_format($totalHoras, 1, ',', '.') ?>h</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total Custo</span>
                        <strong>R$ <?= number_format($totalCusto, 2, ',', '.') ?></strong>
                    </div>
                    <?php if ($totalArtes > 0): ?>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Média por arte</span>
                        <span>R$ <?= number_format($totalCusto / $totalArtes, 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Informações -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informações</h6>
            </div>
            <div class="card-body small">
                <p class="mb-1">
                    <strong>Criada em:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($tag->getCreatedAt())) ?>
                </p>
                <?php if ($tag->getUpdatedAt()): ?>
                <p class="mb-0">
                    <strong>Última alteração:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($tag->getUpdatedAt())) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
