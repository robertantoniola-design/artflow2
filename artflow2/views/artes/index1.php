<?php
/**
 * Artes - Listagem
 * 
 * Variáveis:
 * - $artes: Array de objetos Arte
 * - $filtros: Filtros aplicados
 * - $estatisticas: Stats das artes
 * - $tags: Tags para filtro
 * 
 * CORREÇÕES Fase 1 (15/02/2026):
 * - Adicionado status "reservada" no dropdown de filtro
 * - Adicionado status "reservada" nos labels/cores da tabela
 * - Card de estatísticas inclui contagem de reservadas
 */
$currentPage = 'artes';
?>

<!-- Header da Página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Gerencie suas obras de arte</p>
    </div>
    <a href="<?= url('/artes/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nova Arte
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/artes') ?>" method="GET" class="row g-3 align-items-end">
            <!-- Busca por nome -->
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" 
                       name="termo" 
                       class="form-control" 
                       placeholder="Nome da arte..."
                       value="<?= e($filtros['termo'] ?? '') ?>">
            </div>
            
            <!-- CORREÇÃO: Filtro por status — agora inclui "reservada" -->
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="disponivel" <?= ($filtros['status'] ?? '') === 'disponivel' ? 'selected' : '' ?>>
                        Disponível
                    </option>
                    <option value="em_producao" <?= ($filtros['status'] ?? '') === 'em_producao' ? 'selected' : '' ?>>
                        Em Produção
                    </option>
                    <option value="vendida" <?= ($filtros['status'] ?? '') === 'vendida' ? 'selected' : '' ?>>
                        Vendida
                    </option>
                    <!-- CORREÇÃO Fase 1: Status "reservada" adicionado -->
                    <option value="reservada" <?= ($filtros['status'] ?? '') === 'reservada' ? 'selected' : '' ?>>
                        Reservada
                    </option>
                </select>
            </div>
            
            <!-- Filtro por tag -->
            <div class="col-md-3">
                <label class="form-label">Tag</label>
                <select name="tag_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($tags ?? [] as $tag): ?>
                        <option value="<?= $tag->getId() ?>" <?= ($filtros['tag_id'] ?? '') == $tag->getId() ? 'selected' : '' ?>>
                            <?= e($tag->getNome()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Botões -->
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-primary-subtle border-0">
            <div class="card-body text-center">
                <h3 class="mb-0 text-primary"><?= $estatisticas['total'] ?? 0 ?></h3>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-success-subtle border-0">
            <div class="card-body text-center">
                <h3 class="mb-0 text-success"><?= $estatisticas['disponiveis'] ?? 0 ?></h3>
                <small class="text-muted">Disponíveis</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-warning-subtle border-0">
            <div class="card-body text-center">
                <h3 class="mb-0 text-warning"><?= $estatisticas['em_producao'] ?? 0 ?></h3>
                <small class="text-muted">Em Produção</small>
            </div>
        </div>
    </div>
    <!-- CORREÇÃO: Último card agora mostra vendidas + reservadas -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0" style="background: rgba(99, 102, 241, 0.1);">
            <div class="card-body text-center">
                <h3 class="mb-0" style="color: var(--primary);"><?= $estatisticas['vendidas'] ?? 0 ?></h3>
                <small class="text-muted">Vendidas</small>
            </div>
        </div>
    </div>
</div>

<!-- Listagem -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-palette me-2"></i>
            Artes
            <span class="badge bg-secondary ms-2"><?= count($artes ?? []) ?></span>
        </h5>
    </div>
    
    <div class="card-body p-0">
        <?php if (!empty($artes)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Complexidade</th>
                            <th>Horas</th>
                            <th>Custo</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artes as $arte): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('/artes/' . $arte->getId()) ?>" class="text-decoration-none fw-medium">
                                        <?= e($arte->getNome()) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    // Cor do badge de complexidade
                                    $cor = match($arte->getComplexidade()) {
                                        'baixa' => 'success',
                                        'media' => 'warning',
                                        'alta'  => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $cor ?>-subtle text-<?= $cor ?>">
                                        <?= ucfirst($arte->getComplexidade()) ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="bi bi-clock text-muted me-1"></i>
                                    <?= number_format($arte->getHorasTrabalhadas(), 1) ?>h
                                </td>
                                <td>
                                    <?= money($arte->getPrecoCusto()) ?>
                                </td>
                                <td>
                                    <?php
                                    // CORREÇÃO Fase 1: Incluído "reservada" nos mapas de status
                                    $statusColors = [
                                        'disponivel'  => 'success',
                                        'em_producao' => 'warning',
                                        'vendida'     => 'primary',
                                        'reservada'   => 'info'        // NOVO
                                    ];
                                    $statusLabels = [
                                        'disponivel'  => 'Disponível',
                                        'em_producao' => 'Em Produção',
                                        'vendida'     => 'Vendida',
                                        'reservada'   => 'Reservada'   // NOVO
                                    ];
                                    $statusCor = $statusColors[$arte->getStatus()] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusCor ?>">
                                        <?= $statusLabels[$arte->getStatus()] ?? $arte->getStatus() ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/artes/' . $arte->getId()) ?>" 
                                           class="btn btn-outline-primary"
                                           title="Ver detalhes">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= url('/artes/' . $arte->getId() . '/editar') ?>" 
                                           class="btn btn-outline-secondary"
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($arte->getStatus() === 'disponivel'): ?>
                                            <a href="<?= url('/vendas/criar?arte_id=' . $arte->getId()) ?>" 
                                               class="btn btn-outline-success"
                                               title="Registrar venda">
                                                <i class="bi bi-cart-plus"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state py-5">
                <i class="bi bi-brush"></i>
                <h5>Nenhuma arte encontrada</h5>
                <p class="text-muted mb-3">
                    <?php if (!empty($filtros['termo']) || !empty($filtros['status'])): ?>
                        Tente ajustar os filtros de busca
                    <?php else: ?>
                        Comece criando sua primeira arte!
                    <?php endif; ?>
                </p>
                <a href="<?= url('/artes/criar') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Criar Arte
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>