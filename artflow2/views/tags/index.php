<?php
/**
 * VIEW: Listagem de Tags
 * GET /tags
 */
$currentPage = 'tags';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-tags text-primary"></i> Tags
        </h2>
        <p class="text-muted mb-0">Organize suas artes com tags</p>
    </div>
    <a href="<?= url('/tags/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Tag
    </a>
</div>

<!-- Tags Mais Usadas -->
<?php if (!empty($maisUsadas)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-star"></i> Tags Mais Usadas</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($maisUsadas as $tag): ?>
                    <a href="<?= url("/tags/{$tag['id']}") ?>" 
                       class="badge fs-6 text-decoration-none"
                       style="background-color: <?= e($tag['cor']) ?>;">
                        <?= e($tag['nome']) ?>
                        <span class="badge bg-dark ms-1"><?= $tag['total_artes'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Busca -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/tags') ?>" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="termo" 
                           class="form-control" 
                           placeholder="Buscar tags..."
                           value="<?= e($filtros['termo'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary">Buscar</button>
                <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Tags -->
<?php if (empty($tags)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-tags display-4 text-muted"></i>
            <h5 class="mt-3">Nenhuma tag encontrada</h5>
            <p class="text-muted">Crie tags para organizar suas artes.</p>
            <a href="<?= url('/tags/criar') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Criar Tag
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($tags as $tag): ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge fs-5" style="background-color: <?= e($tag['cor']) ?>;">
                                <?= e($tag['nome']) ?>
                            </span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="<?= url("/tags/{$tag['id']}") ?>" class="dropdown-item">
                                            <i class="bi bi-eye"></i> Ver Artes
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?= url("/tags/{$tag['id']}/editar") ?>" class="dropdown-item">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" 
                                                onclick="confirmarExclusao(<?= $tag['id'] ?>, '<?= e($tag['nome']) ?>')">
                                            <i class="bi bi-trash"></i> Excluir
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center text-muted">
                            <i class="bi bi-brush me-2"></i>
                            <span><?= $tag['total_artes'] ?? 0 ?> arte(s)</span>
                        </div>
                        
                        <!-- Preview da cor -->
                        <div class="mt-3">
                            <small class="text-muted">Cor: <?= e($tag['cor']) ?></small>
                            <div class="progress mt-1" style="height: 5px;">
                                <div class="progress-bar" style="width: 100%; background-color: <?= e($tag['cor']) ?>;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="<?= url("/artes?tag_id={$tag['id']}") ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-filter"></i> Ver Artes com esta Tag
                        </a>
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
                <p>Tem certeza que deseja excluir a tag <strong id="nomeTagExcluir"></strong>?</p>
                <p class="text-muted small">
                    <i class="bi bi-info-circle"></i>
                    As artes associadas não serão excluídas, apenas perderão esta tag.
                </p>
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
function confirmarExclusao(id, nome) {
    document.getElementById('nomeTagExcluir').textContent = nome;
    document.getElementById('formExcluir').action = '<?= url('/tags') ?>/' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>
