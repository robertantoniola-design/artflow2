<?php
/**
 * VIEW: Listagem de Tags (Melhoria 3 — + Ícones e Descrição)
 * GET /tags
 * 
 * Variáveis recebidas do Controller:
 * - $tags: array<Tag> (paginadas, com artesCount)
 * - $paginacao: array com dados de paginação
 * - $tagsMaisUsadas: array<Tag> (top 5 — sidebar)
 * - $filtros: ['ordenar', 'direcao', 'termo']
 * 
 * Fases implementadas:
 * - Fase 2: Paginação + Ordenação dinâmica
 * - Melhoria 3: Ícones nos badges + descrição resumida nos cards
 */
$currentPage = 'tags';

// Extrai filtros com valores padrão seguros
$ordenar = $filtros['ordenar'] ?? 'nome';
$direcao = $filtros['direcao'] ?? 'ASC';
$termo = $filtros['termo'] ?? '';

// Helper: monta URL preservando todos os parâmetros
function tagUrl(array $overrides = []): string {
    global $ordenar, $direcao, $termo;
    
    $params = array_merge([
        'ordenar' => $ordenar,
        'direcao' => $direcao,
        'termo' => $termo,
        'page' => 1,
    ], $overrides);
    
    // Remove parâmetros vazios
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    
    return url('/tags') . '?' . http_build_query($params);
}
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

<!-- Tags Mais Usadas (Melhoria 3: agora com ícones) -->
<?php if (!empty($tagsMaisUsadas)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-star"></i> Tags Mais Usadas</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($tagsMaisUsadas as $tagPopular): ?>
                    <a href="<?= url("/tags/{$tagPopular->getId()}") ?>" 
                       class="badge fs-6 text-decoration-none"
                       style="<?= $tagPopular->getStyleInline() ?>">
                        <?php if ($tagPopular->hasIcone()): ?>
                            <i class="<?= e($tagPopular->getIcone()) ?> me-1"></i>
                        <?php endif; ?>
                        <?= e($tagPopular->getNome()) ?>
                        <span class="badge bg-dark ms-1"><?= $tagPopular->getArtesCount() ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Barra de Busca + Ordenação (Fase 2) -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/tags') ?>" method="GET" class="row g-3 align-items-end">
            <!-- Campo de busca -->
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="termo" 
                           class="form-control" 
                           placeholder="Buscar tags..."
                           value="<?= e($termo) ?>">
                </div>
            </div>
            
            <!-- Preserva ordenação no submit da busca -->
            <input type="hidden" name="ordenar" value="<?= e($ordenar) ?>">
            <input type="hidden" name="direcao" value="<?= e($direcao) ?>">
            
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <?php if (!empty($termo)): ?>
                    <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Botões de Ordenação (Fase 2) -->
        <div class="mt-3 d-flex gap-2 align-items-center">
            <small class="text-muted">Ordenar por:</small>
            
            <?php
            // Helper local: gera botão de ordenação com toggle de direção
            $sortButtons = [
                'nome' => ['label' => 'Nome', 'icon' => 'bi-sort-alpha'],
                'data' => ['label' => 'Data', 'icon' => 'bi-calendar'],
                'contagem' => ['label' => 'Artes', 'icon' => 'bi-collection'],
            ];
            
            foreach ($sortButtons as $campo => $config):
                $isActive = ($ordenar === $campo);
                $nextDir = ($isActive && $direcao === 'ASC') ? 'DESC' : 'ASC';
                $btnClass = $isActive ? 'btn-primary' : 'btn-outline-secondary';
                $arrow = $isActive ? ($direcao === 'ASC' ? '↑' : '↓') : '';
            ?>
                <a href="<?= tagUrl(['ordenar' => $campo, 'direcao' => $nextDir]) ?>" 
                   class="btn btn-sm <?= $btnClass ?>">
                    <i class="bi <?= $config['icon'] ?>"></i> 
                    <?= $config['label'] ?> <?= $arrow ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Resultado da busca -->
<?php if (!empty($termo)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <?= $paginacao['total_registros'] ?? 0 ?> resultado(s) para "<strong><?= e($termo) ?></strong>"
    </div>
<?php endif; ?>

<!-- Lista de Tags (Cards com ícone e descrição) -->
<?php if (empty($tags)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-tags text-muted fs-1"></i>
            <p class="text-muted mt-2">Nenhuma tag encontrada</p>
            <a href="<?= url('/tags/criar') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Criar Primeira Tag
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($tags as $tag): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <!-- Badge com cor, ícone e nome (Melhoria 3: +ícone) -->
                        <a href="<?= url('/tags/' . $tag->getId()) ?>" 
                           class="text-decoration-none">
                            <span class="badge fs-5 px-3 py-2" style="<?= $tag->getStyleInline() ?>">
                                <?php if ($tag->hasIcone()): ?>
                                    <i class="<?= e($tag->getIcone()) ?> me-1"></i>
                                <?php endif; ?>
                                <?= e($tag->getNome()) ?>
                            </span>
                        </a>
                        
                        <!-- Contagem de artes -->
                        <p class="text-muted mt-2 mb-1">
                            <i class="bi bi-images"></i> 
                            <?= $tag->getArtesCount() ?> arte(s)
                        </p>
                        
                        <!-- Descrição resumida (Melhoria 3) -->
                        <?php if ($tag->hasDescricao()): ?>
                            <p class="text-muted small mb-0" title="<?= e($tag->getDescricao()) ?>">
                                <?= e($tag->getDescricaoResumida(60)) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent text-center">
                        <a href="<?= url('/tags/' . $tag->getId()) ?>" 
                           class="btn btn-sm btn-outline-primary me-1" title="Detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= url('/tags/' . $tag->getId() . '/editar') ?>" 
                           class="btn btn-sm btn-outline-secondary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- ==========================================
         PAGINAÇÃO (Fase 2)
         ========================================== -->
    <?php if (($paginacao['total_paginas'] ?? 1) > 1): ?>
        <nav aria-label="Paginação de tags" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Anterior -->
                <li class="page-item <?= !$paginacao['tem_anterior'] ? 'disabled' : '' ?>">
                    <a class="page-link" 
                       href="<?= tagUrl(['page' => $paginacao['pagina_atual'] - 1]) ?>">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </a>
                </li>
                
                <!-- Números de página -->
                <?php for ($i = 1; $i <= $paginacao['total_paginas']; $i++): ?>
                    <li class="page-item <?= $i === $paginacao['pagina_atual'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= tagUrl(['page' => $i]) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <!-- Próxima -->
                <li class="page-item <?= !$paginacao['tem_proxima'] ? 'disabled' : '' ?>">
                    <a class="page-link" 
                       href="<?= tagUrl(['page' => $paginacao['pagina_atual'] + 1]) ?>">
                        Próxima <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
            
            <p class="text-center text-muted small">
                Exibindo página <?= $paginacao['pagina_atual'] ?> de <?= $paginacao['total_paginas'] ?>
                (<?= $paginacao['total_registros'] ?> tags no total)
            </p>
        </nav>
    <?php endif; ?>
<?php endif; ?>
