<?php
/**
 * ============================================
 * VIEW: Listagem de Tags (Melhoria 1+2)
 * ============================================
 * 
 * GET /tags
 * GET /tags?page=2&ordenar=contagem&direcao=DESC&termo=Aqua
 * 
 * VARIÁVEIS DISPONÍVEIS (via extract no View::renderFile):
 * - $tags (array<Tag>)         — Tags da página atual (objetos Tag com artesCount)
 * - $paginacao (array)         — NOVO: metadados de paginação
 * - $maisUsadas (array<Tag>)   — Top 5 tags mais populares
 * - $filtros (array)           — Filtros ativos: termo, ordenar, direcao, page
 * - $coresPredefinidas (array) — Paleta de cores para referência
 * 
 * MELHORIAS IMPLEMENTADAS:
 * - [Melhoria 1] Paginação com controles Bootstrap 5
 * - [Melhoria 2] Ordenação clicável (Nome, Data, Contagem) com setas visuais
 * 
 * NOTA TÉCNICA: As funções helper (tagUrl, tagSortUrl, tagSortIcon) recebem
 * $filtros como parâmetro porque View::renderFile() usa extract() em escopo
 * local — variáveis NÃO ficam no escopo global, então 'global $filtros'
 * não funcionaria aqui.
 * 
 * INSTRUÇÕES: SUBSTITUA o arquivo views/tags/index.php inteiro por este.
 */
$currentPage = 'tags';

// ══════════════════════════════════════════════════════════════
// FUNÇÕES HELPER PARA URLs DE PAGINAÇÃO E ORDENAÇÃO
// ══════════════════════════════════════════════════════════════
// Recebem $filtros como parâmetro (não usam 'global')
// porque View::renderFile() faz extract($data) em escopo local.

/**
 * Monta URL preservando TODOS os parâmetros atuais.
 * Permite trocar apenas um parâmetro sem perder os outros.
 * 
 * Exemplo: tagUrl($filtros, ['page' => 3])
 *   Se filtros = {termo: 'aqua', ordenar: 'nome', direcao: 'ASC'}
 *   Resultado: /tags?termo=aqua&ordenar=nome&direcao=ASC&page=3
 * 
 * @param array $filtros Filtros atuais vindos do controller
 * @param array $params  Parâmetros a sobrescrever
 * @return string URL completa
 */
function tagUrl(array $filtros, array $params = []): string {
    // Merge: parâmetros passados sobrescrevem os atuais
    $merged = array_merge([
        'termo'   => $filtros['termo'] ?? '',
        'ordenar' => $filtros['ordenar'] ?? 'nome',
        'direcao' => $filtros['direcao'] ?? 'ASC',
        'page'    => $filtros['page'] ?? 1,
    ], $params);
    
    // Remove parâmetros vazios para URL limpa
    $query = array_filter($merged, fn($v) => $v !== '' && $v !== null);
    
    // Remove defaults para não poluir a URL
    // Se page=1, não precisa estar na URL
    if (($query['page'] ?? 1) == 1) unset($query['page']);
    // Se ordenar=nome + direcao=ASC + sem busca = estado padrão, limpa tudo
    if (($query['ordenar'] ?? 'nome') === 'nome' && ($query['direcao'] ?? 'ASC') === 'ASC' && empty($query['termo'])) {
        unset($query['ordenar'], $query['direcao']);
    }
    
    $qs = !empty($query) ? '?' . http_build_query($query) : '';
    return url('/tags') . $qs;
}

/**
 * Gera URL de ordenação com toggle automático de direção.
 * - Clicar na MESMA coluna: inverte ASC↔DESC
 * - Clicar em OUTRA coluna: começa com ASC (exceto contagem → DESC)
 * - Sempre volta para página 1 ao mudar ordenação
 * 
 * @param array $filtros Filtros atuais
 * @param string $coluna Coluna clicada: 'nome'|'data'|'contagem'
 * @return string URL com nova ordenação
 */
function tagSortUrl(array $filtros, string $coluna): string {
    $ordenarAtual  = $filtros['ordenar'] ?? 'nome';
    $direcaoAtual  = $filtros['direcao'] ?? 'ASC';
    
    // Se já está ordenando por esta coluna, inverte a direção
    if ($ordenarAtual === $coluna) {
        $novaDirecao = ($direcaoAtual === 'ASC') ? 'DESC' : 'ASC';
    } else {
        // Coluna diferente: começa com ASC (exceto contagem, que faz mais sentido DESC)
        $novaDirecao = ($coluna === 'contagem') ? 'DESC' : 'ASC';
    }
    
    return tagUrl($filtros, [
        'ordenar' => $coluna,
        'direcao' => $novaDirecao,
        'page'    => 1  // Volta para página 1 ao trocar ordenação
    ]);
}

/**
 * Retorna ícone HTML de seta para indicar direção de ordenação.
 * - Coluna ativa: seta colorida na direção atual (↓ ou ↑)
 * - Coluna inativa: seta cinza neutra (↕)
 * - Ícones específicos: alfa para nome, numérico para contagem
 * 
 * @param array $filtros Filtros atuais
 * @param string $coluna Coluna a verificar
 * @return string HTML do ícone Bootstrap
 */
function tagSortIcon(array $filtros, string $coluna): string {
    $ordenarAtual = $filtros['ordenar'] ?? 'nome';
    $direcaoAtual = $filtros['direcao'] ?? 'ASC';
    
    if ($ordenarAtual !== $coluna) {
        // Coluna inativa: seta cinza neutra
        return '<i class="bi bi-arrow-down-up text-muted opacity-50"></i>';
    }
    
    // Coluna ativa: ícone específico por tipo de coluna
    if ($coluna === 'contagem') {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up';
    } elseif ($coluna === 'data') {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-down' : 'bi-sort-up';
    } else {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
    }
    
    return '<i class="bi ' . $icone . ' text-primary"></i>';
}

// ── Extrai dados de paginação para uso no template ──
$pag = $paginacao ?? [];
$paginaAtual    = $pag['pagina_atual'] ?? 1;
$totalPaginas   = $pag['total_paginas'] ?? 1;
$totalRegistros = $pag['total_registros'] ?? 0;
$temAnterior    = $pag['tem_anterior'] ?? false;
$temProxima     = $pag['tem_proxima'] ?? false;
?>

<!-- ═══════════════════════════════════════════════ -->
<!-- HEADER: Título + Botão Nova Tag                -->
<!-- ═══════════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-tags text-primary"></i> Tags
        </h2>
        <p class="text-muted mb-0">
            Organize suas artes com tags
            <?php if ($totalRegistros > 0): ?>
                <span class="badge bg-secondary ms-2"><?= $totalRegistros ?> tag(s)</span>
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= url('/tags/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Tag
    </a>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- TAGS MAIS USADAS: Badges clicáveis             -->
<!-- ═══════════════════════════════════════════════ -->
<?php if (!empty($maisUsadas)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-star"></i> Tags Mais Usadas</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($maisUsadas as $tagPopular): ?>
                    <a href="<?= url("/tags/{$tagPopular->getId()}") ?>" 
                       class="badge fs-6 text-decoration-none"
                       style="background-color: <?= e($tagPopular->getCor()) ?>; color: <?= e($tagPopular->getCorTexto()) ?>;">
                        <?= e($tagPopular->getNome()) ?>
                        <span class="badge bg-dark ms-1"><?= $tagPopular->getArtesCount() ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- BUSCA + CONTROLES DE ORDENAÇÃO                 -->
<!-- ═══════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-body">
        <!-- Linha 1: Campo de busca -->
        <form action="<?= url('/tags') ?>" method="GET" class="row g-3 mb-3">
            <!-- Preserva ordenação atual durante busca (campos hidden) -->
            <input type="hidden" name="ordenar" value="<?= e($filtros['ordenar'] ?? 'nome') ?>">
            <input type="hidden" name="direcao" value="<?= e($filtros['direcao'] ?? 'ASC') ?>">
            
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
        
        <!-- Linha 2: Controles de ordenação (MELHORIA 2) -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <small class="text-muted me-1"><i class="bi bi-funnel"></i> Ordenar por:</small>
            
            <!-- Botão: Nome (ativo quando ordenar=nome) -->
            <a href="<?= tagSortUrl($filtros, 'nome') ?>" 
               class="btn btn-sm <?= ($filtros['ordenar'] ?? 'nome') === 'nome' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= tagSortIcon($filtros, 'nome') ?> Nome
            </a>
            
            <!-- Botão: Data de Criação (ativo quando ordenar=data) -->
            <a href="<?= tagSortUrl($filtros, 'data') ?>" 
               class="btn btn-sm <?= ($filtros['ordenar'] ?? '') === 'data' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= tagSortIcon($filtros, 'data') ?> Data
            </a>
            
            <!-- Botão: Contagem de Artes (ativo quando ordenar=contagem) -->
            <a href="<?= tagSortUrl($filtros, 'contagem') ?>" 
               class="btn btn-sm <?= ($filtros['ordenar'] ?? '') === 'contagem' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= tagSortIcon($filtros, 'contagem') ?> Artes
            </a>
            
            <!-- Badge de filtro ativo (se buscando por termo) -->
            <?php if (!empty($filtros['termo'])): ?>
                <span class="badge bg-info ms-2">
                    Filtro: "<?= e($filtros['termo']) ?>"
                    <a href="<?= tagUrl($filtros, ['termo' => '', 'page' => 1]) ?>" class="text-white ms-1">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- LISTA DE TAGS (Cards com paginação)            -->
<!-- ═══════════════════════════════════════════════ -->
<?php if (empty($tags)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-tags display-4 text-muted"></i>
            <h5 class="mt-3">Nenhuma tag encontrada</h5>
            <?php if (!empty($filtros['termo'])): ?>
                <p class="text-muted">Nenhuma tag corresponde a "<?= e($filtros['termo']) ?>".</p>
                <a href="<?= url('/tags') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Ver Todas
                </a>
            <?php else: ?>
                <p class="text-muted">Crie tags para organizar suas artes.</p>
                <a href="<?= url('/tags/criar') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Criar Tag
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Grid de Cards (4 colunas em XL, 3 em LG, 2 em MD) -->
    <div class="row g-4">
        <?php foreach ($tags as $tag): ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <!-- Nome da tag (clicável) + Menu de ações -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <a href="<?= url("/tags/{$tag->getId()}") ?>" class="text-decoration-none">
                                <span class="badge fs-5" 
                                      style="background-color: <?= e($tag->getCor()) ?>; color: <?= e($tag->getCorTexto()) ?>;">
                                    <?= e($tag->getNome()) ?>
                                </span>
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="<?= url("/tags/{$tag->getId()}") ?>" class="dropdown-item">
                                            <i class="bi bi-eye"></i> Ver Artes
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?= url("/tags/{$tag->getId()}/editar") ?>" class="dropdown-item">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" 
                                                onclick="confirmarExclusao(<?= $tag->getId() ?>, '<?= e($tag->getNome()) ?>')">
                                            <i class="bi bi-trash"></i> Excluir
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Contagem de artes -->
                        <div class="d-flex align-items-center text-muted">
                            <i class="bi bi-brush me-2"></i>
                            <span><?= $tag->getArtesCount() ?> arte(s)</span>
                        </div>
                        
                        <!-- Preview da cor -->
                        <div class="mt-3">
                            <small class="text-muted">Cor: <?= e($tag->getCor()) ?></small>
                            <div class="progress mt-1" style="height: 5px;">
                                <div class="progress-bar" style="width: 100%; background-color: <?= e($tag->getCor()) ?>;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="<?= url("/artes?tag_id={$tag->getId()}") ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-filter"></i> Ver Artes com esta Tag
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- PAGINAÇÃO (MELHORIA 1)                         -->
    <!-- Bootstrap 5 pagination com info de registros   -->
    <!-- Só aparece se existir mais de 1 página         -->
    <!-- ═══════════════════════════════════════════════ -->
    <?php if ($totalPaginas > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <!-- Info: "Página X de Y (Z tags no total)" -->
            <small class="text-muted">
                Página <?= $paginaAtual ?> de <?= $totalPaginas ?>
                (<?= $totalRegistros ?> tag<?= $totalRegistros !== 1 ? 's' : '' ?> no total)
            </small>
            
            <!-- Controles de paginação -->
            <nav aria-label="Paginação de tags">
                <ul class="pagination mb-0">
                    <!-- Botão Anterior (desabilitado na primeira página) -->
                    <li class="page-item <?= !$temAnterior ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= $temAnterior ? tagUrl($filtros, ['page' => $paginaAtual - 1]) : '#' ?>"
                           <?= !$temAnterior ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // ── Lógica de janela de páginas ──
                    // Mostra no máximo 5 números ao redor da página atual
                    // Ex: Para página 7 de 20: [5] [6] [7*] [8] [9]
                    $inicio = max(1, $paginaAtual - 2);
                    $fim    = min($totalPaginas, $paginaAtual + 2);
                    
                    // Ajusta janela se perto do início ou fim
                    if ($paginaAtual <= 2) {
                        $fim = min($totalPaginas, 5);
                    }
                    if ($paginaAtual >= $totalPaginas - 1) {
                        $inicio = max(1, $totalPaginas - 4);
                    }
                    ?>
                    
                    <!-- Primeira página + reticências (se janela não começa em 1) -->
                    <?php if ($inicio > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= tagUrl($filtros, ['page' => 1]) ?>">1</a>
                        </li>
                        <?php if ($inicio > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Números de página (janela de 5) -->
                    <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                        <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">
                            <?php if ($i === $paginaAtual): ?>
                                <span class="page-link"><?= $i ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= tagUrl($filtros, ['page' => $i]) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Última página + reticências (se janela não termina no total) -->
                    <?php if ($fim < $totalPaginas): ?>
                        <?php if ($fim < $totalPaginas - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= tagUrl($filtros, ['page' => $totalPaginas]) ?>"><?= $totalPaginas ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Botão Próxima (desabilitado na última página) -->
                    <li class="page-item <?= !$temProxima ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= $temProxima ? tagUrl($filtros, ['page' => $paginaAtual + 1]) : '#' ?>"
                           <?= !$temProxima ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- MODAL DE EXCLUSÃO (mantido do original)        -->
<!-- ═══════════════════════════════════════════════ -->
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
/**
 * Abre modal de confirmação de exclusão
 * Mantido do original sem alterações
 */
function confirmarExclusao(id, nome) {
    document.getElementById('nomeTagExcluir').textContent = nome;
    document.getElementById('formExcluir').action = '<?= url('/tags') ?>/' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>