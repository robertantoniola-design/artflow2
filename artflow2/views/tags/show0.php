<?php
/**
 * View: Detalhes da Tag
 * Exibe informações da tag e lista artes associadas
 * 
 * CORREÇÃO [07/02/2026]:
 * $artes vem como array de arrays associativos (não objetos Arte)
 * porque TagRepository::getArtesByTag() retorna FETCH_ASSOC.
 * Todos os acessos mudados de $arte->getXxx() para $arte['xxx'].
 */

// Variáveis: $tag (objeto Tag), $artes (array de arrays associativos)
$totalArtes = count($artes ?? []);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/tags') ?>">Tags</a></li>
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
        <a href="<?= url('/tags/' . $tag->getId() . '/editar') ?>" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">
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
                <a href="<?= url('/artes/criar?tag=' . $tag->getId()) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus"></i> Nova Arte
                </a>
            </div>
            
            <?php if (empty($artes)): ?>
                <div class="card-body text-center py-5">
                    <i class="bi bi-palette display-4 text-muted"></i>
                    <h5 class="mt-3">Nenhuma arte com esta tag</h5>
                    <p class="text-muted">Associe artes a esta tag para vê-las aqui.</p>
                </div>
            <?php else: ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th class="text-end">Custo</th>
                                    <th class="text-end">Horas</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($artes as $arte): ?>
                                    <?php
                                    // ============================================
                                    // ACESSO VIA ARRAY: $arte['campo']
                                    // ============================================
                                    // $artes vem de TagRepository::getArtesByTag()
                                    // que retorna PDO::FETCH_ASSOC (arrays, não objetos)
                                    
                                    $arteStatus = $arte['status'] ?? 'disponivel';
                                    $statusClass = match($arteStatus) {
                                        'disponivel' => 'success',
                                        'em_producao' => 'warning',
                                        'vendida' => 'info',
                                        'reservada' => 'primary',
                                        default => 'secondary'
                                    };
                                    $statusLabel = match($arteStatus) {
                                        'disponivel' => 'Disponível',
                                        'em_producao' => 'Em Produção',
                                        'vendida' => 'Vendida',
                                        'reservada' => 'Reservada',
                                        default => ucfirst($arteStatus)
                                    };
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/artes/' . $arte['id']) ?>" class="text-decoration-none fw-bold">
                                                <?= e($arte['nome']) ?>
                                            </a>
                                            <?php if (!empty($arte['descricao'])): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?= e(mb_substr($arte['descricao'], 0, 60)) ?><?= mb_strlen($arte['descricao'] ?? '') > 60 ? '...' : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            R$ <?= number_format((float)($arte['preco_custo'] ?? 0), 2, ',', '.') ?>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format((float)($arte['horas_trabalhadas'] ?? 0), 1, ',', '.') ?>h
                                        </td>
                                        <td>
                                            <a href="<?= url('/artes/' . $arte['id']) ?>" 
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
                // ============================================
                // Calcular estatísticas usando acesso por array
                // ============================================
                $disponiveis = 0;
                $emProducao = 0;
                $vendidas = 0;
                $totalHoras = 0;
                $totalCusto = 0;
                
                foreach ($artes as $arte) {
                    // Acesso via array: $arte['campo']
                    switch ($arte['status'] ?? '') {
                        case 'disponivel': $disponiveis++; break;
                        case 'em_producao': $emProducao++; break;
                        case 'vendida': $vendidas++; break;
                    }
                    $totalHoras += (float)($arte['horas_trabalhadas'] ?? 0);
                    $totalCusto += (float)($arte['preco_custo'] ?? 0);
                }
                ?>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Total de Artes</span>
                        <strong><?= $totalArtes ?></strong>
                    </div>
                </div>
                
                <?php if ($totalArtes > 0): ?>
                    <!-- Distribuição por Status -->
                    <hr>
                    <h6 class="text-muted mb-2">Por Status</h6>
                    
                    <?php if ($disponiveis > 0): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><span class="badge bg-success">Disponível</span></span>
                            <span><?= $disponiveis ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($emProducao > 0): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><span class="badge bg-warning">Em Produção</span></span>
                            <span><?= $emProducao ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($vendidas > 0): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><span class="badge bg-info">Vendida</span></span>
                            <span><?= $vendidas ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Totais -->
                    <hr>
                    <h6 class="text-muted mb-2">Totais</h6>
                    
                    <div class="d-flex justify-content-between mb-1">
                        <span>Horas Trabalhadas</span>
                        <strong><?= number_format($totalHoras, 1, ',', '.') ?>h</strong>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-1">
                        <span>Custo Total</span>
                        <strong>R$ <?= number_format($totalCusto, 2, ',', '.') ?></strong>
                    </div>
                    
                    <?php if ($totalHoras > 0): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Custo/Hora Médio</span>
                            <strong>R$ <?= number_format($totalCusto / $totalHoras, 2, ',', '.') ?></strong>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Ações Rápidas -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning"></i> Ações</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="<?= url('/tags/' . $tag->getId() . '/editar') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Editar Tag
                </a>
                <a href="<?= url('/artes?tag_id=' . $tag->getId()) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-funnel me-1"></i> Filtrar Artes por esta Tag
                </a>
                <form action="<?= url('/tags/' . $tag->getId()) ?>" method="POST" 
                      onsubmit="return confirm('Tem certeza que deseja excluir a tag \'<?= e($tag->getNome()) ?>\'? As artes NÃO serão excluídas, apenas a associação.')">
                    <input type="hidden" name="_csrf" value="<?= $_SESSION['_csrf'] ?? '' ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i> Excluir Tag
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>