<?php
/**
 * VIEW: Detalhes da Tag (Melhoria 3 — + Descrição e Ícone)
 * GET /tags/{id}
 * 
 * Variáveis:
 * - $tag: Objeto Tag (com métodos getDescricao(), getIcone(), hasDescricao(), hasIcone())
 * - $artes: Array de arrays associativos (FETCH_ASSOC — usar $arte['campo'])
 * 
 * LEMBRETE: $artes vem como arrays, NÃO objetos Arte.
 * Sempre usar $arte['nome'], nunca $arte->getNome().
 */
$currentPage = 'tags';
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

<!-- Header com badge (agora com ícone) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <!-- Badge grande com ícone (Melhoria 3) -->
        <span class="badge fs-3 py-2 px-3" style="<?= $tag->getStyleInline() ?>">
            <?php if ($tag->hasIcone()): ?>
                <i class="<?= e($tag->getIcone()) ?> me-1"></i>
            <?php endif; ?>
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
    <!-- Coluna principal: Artes Associadas -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-palette"></i> Artes com esta Tag
                </h5>
                <a href="<?= url('/artes?tag_id=' . $tag->getId()) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Ver no módulo Artes
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($artes)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-image text-muted fs-1"></i>
                        <p class="text-muted mt-2">Nenhuma arte com esta tag</p>
                        <a href="<?= url('/artes/criar') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Criar Arte
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th>Preço Custo</th>
                                    <th>Horas</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($artes as $arte): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/artes/' . $arte['id']) ?>" class="text-decoration-none">
                                                <?= e($arte['nome']) ?>
                                            </a>
                                            <?php if (!empty($arte['descricao'])): ?>
                                                <br><small class="text-muted"><?= e(mb_substr($arte['descricao'] ?? '', 0, 60)) ?><?= mb_strlen($arte['descricao'] ?? '') > 60 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusLabels = [
                                                'disponivel' => '<span class="badge bg-success">Disponível</span>',
                                                'em_producao' => '<span class="badge bg-warning text-dark">Em Produção</span>',
                                                'vendida' => '<span class="badge bg-info">Vendida</span>',
                                                'reservada' => '<span class="badge bg-secondary">Reservada</span>',
                                            ];
                                            echo $statusLabels[$arte['status']] ?? '<span class="badge bg-light text-dark">' . e($arte['status']) . '</span>';
                                            ?>
                                        </td>
                                        <td><?= 'R$ ' . number_format((float)($arte['preco_custo'] ?? 0), 2, ',', '.') ?></td>
                                        <td><?= number_format((float)($arte['horas_trabalhadas'] ?? 0), 1, ',', '.') ?>h</td>
                                        <td class="text-end">
                                            <a href="<?= url('/artes/' . $arte['id']) ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Coluna lateral: Informações da Tag -->
    <div class="col-lg-4">
        <!-- Card de Informações -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> Informações
                </h5>
            </div>
            <div class="card-body">
                <!-- Cor -->
                <div class="mb-3">
                    <small class="text-muted d-block">Cor</small>
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded" 
                              style="width: 24px; height: 24px; display: inline-block; background-color: <?= e($tag->getCor()) ?>;">
                        </span>
                        <code><?= e($tag->getCor()) ?></code>
                    </div>
                </div>
                
                <!-- Ícone (Melhoria 3) -->
                <div class="mb-3">
                    <small class="text-muted d-block">Ícone</small>
                    <?php if ($tag->hasIcone()): ?>
                        <div class="d-flex align-items-center gap-2">
                            <i class="<?= e($tag->getIcone()) ?> fs-5"></i>
                            <code><?= e($tag->getIcone()) ?></code>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic">Sem ícone</span>
                    <?php endif; ?>
                </div>
                
                <!-- Total de Artes -->
                <div class="mb-3">
                    <small class="text-muted d-block">Total de Artes</small>
                    <span class="fs-5 fw-bold"><?= $totalArtes ?></span>
                </div>
                
                <!-- Datas -->
                <div class="mb-3">
                    <small class="text-muted d-block">Criada em</small>
                    <span><?= $tag->getCreatedAt() ? date_br($tag->getCreatedAt()) : '—' ?></span>
                </div>
                
                <?php if ($tag->getUpdatedAt()): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Última atualização</small>
                        <span><?= datetime_br($tag->getUpdatedAt()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Card de Descrição (Melhoria 3) -->
        <?php if ($tag->hasDescricao()): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-text-paragraph"></i> Descrição
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(e($tag->getDescricao())) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Ações Rápidas -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning"></i> Ações
                </h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="<?= url('/tags/' . $tag->getId() . '/editar') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil"></i> Editar Tag
                </a>
                <a href="<?= url('/artes?tag_id=' . $tag->getId()) ?>" class="btn btn-outline-info">
                    <i class="bi bi-images"></i> Ver Artes com esta Tag
                </a>
                <form action="<?= url('/tags/' . $tag->getId()) ?>" method="POST"
                      onsubmit="return confirm('Excluir a tag \'<?= e($tag->getNome()) ?>\'? Associações com artes serão removidas.');">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Excluir Tag
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
