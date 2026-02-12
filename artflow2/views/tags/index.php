<?php
/**
 * ============================================
 * VIEW: Listagem de Tags (Melhoria 3 — CORRIGIDA)
 * ============================================
 * 
 * GET /tags
 * GET /tags?page=2&ordenar=contagem&direcao=DESC&termo=Aqua
 * 
 * VARIÁVEIS DISPONÍVEIS (via extract no View::renderFile):
 * - $tags (array<Tag>)            — Tags da página atual (objetos Tag com artesCount)
 * - $paginacao (array)            — Metadados de paginação
 * - $tagsMaisUsadas (array<Tag>)  — Top 5 tags mais populares
 * - $filtros (array)              — Filtros ativos: termo, ordenar, direcao, page
 * 
 * MELHORIAS IMPLEMENTADAS:
 * - [Melhoria 1] Paginação com controles Bootstrap 5 (12 tags/página)
 * - [Melhoria 2] Ordenação clicável (Nome, Data, Contagem) com setas visuais
 * - [Melhoria 3] Ícones nos badges + descrição resumida nos cards
 * 
 * CORREÇÕES APLICADAS (11/02/2026):
 * - [R1] Restaurado dropdown three-dots (...) com menu de ações
 * - [R2] Restaurado link "Ver Artes com esta Tag" no card-footer
 * - [R3] Restaurado botão "Excluir" com modal de confirmação
 * - [FIX] Função tagUrl() corrigida: recebe $filtros como parâmetro
 *         (não usa 'global' — compatível com extract() do View::renderFile)
 * 
 * NOTA TÉCNICA: As funções helper recebem $filtros como parâmetro
 * porque View::renderFile() usa extract() em escopo local —
 * variáveis NÃO ficam no escopo global.
 * 
 * ARQUIVO: views/tags/index.php
 * SUBSTITUI: versão da Melhoria 3 que tinha regressões de UI
 */
$currentPage = 'tags';

// ══════════════════════════════════════════════════════════════
// FUNÇÕES HELPER PARA URLs DE PAGINAÇÃO E ORDENAÇÃO
// ══════════════════════════════════════════════════════════════

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
    if (($query['page'] ?? 1) == 1) unset($query['page']);
    if (($query['ordenar'] ?? 'nome') === 'nome' 
        && ($query['direcao'] ?? 'ASC') === 'ASC' 
        && empty($query['termo'])) {
        unset($query['ordenar'], $query['direcao']);
    }
    
    $qs = !empty($query) ? '?' . http_build_query($query) : '';
    return url('/tags') . $qs;
}

/**
 * Gera URL de ordenação com toggle automático de direção.
 * Se clicar no campo já ativo → inverte ASC↔DESC
 * Se clicar em campo diferente → usa ASC como padrão
 * 
 * @param array  $filtros     Filtros atuais
 * @param string $campo       Campo de ordenação (nome|data|contagem)
 * @return string URL com ordenação ajustada
 */
function tagSortUrl(array $filtros, string $campo): string {
    $ordenarAtual  = $filtros['ordenar'] ?? 'nome';
    $direcaoAtual  = $filtros['direcao'] ?? 'ASC';
    
    // Toggle: se já está ordenando por este campo, inverte a direção
    if ($ordenarAtual === $campo) {
        $novaDirecao = ($direcaoAtual === 'ASC') ? 'DESC' : 'ASC';
    } else {
        $novaDirecao = 'ASC'; // Novo campo sempre começa ASC
    }
    
    return tagUrl($filtros, [
        'ordenar' => $campo,
        'direcao' => $novaDirecao,
        'page'    => 1, // Reset para página 1 ao mudar ordenação
    ]);
}

/**
 * Retorna ícone de seta indicando direção da ordenação ativa.
 * 
 * @param array  $filtros Filtros atuais
 * @param string $campo   Campo a verificar
 * @return string HTML do ícone Bootstrap Icons (ou vazio se inativo)
 */
function tagSortIcon(array $filtros, string $campo): string {
    $ordenarAtual = $filtros['ordenar'] ?? 'nome';
    $direcaoAtual = $filtros['direcao'] ?? 'ASC';
    
    // Só mostra ícone se este campo está ativo
    if ($ordenarAtual !== $campo) return '';
    
    // Ícone numérico para contagem, alfabético para nome/data
    if ($campo === 'contagem') {
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

// Extrai filtros com valores padrão seguros
$ordenarAtual = $filtros['ordenar'] ?? 'nome';
$direcaoAtual = $filtros['direcao'] ?? 'ASC';
$termoAtual   = $filtros['termo'] ?? '';
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
<!-- TAGS MAIS USADAS: Badges clicáveis (c/ ícone)  -->
<!-- Melhoria 3: adicionado suporte a ícone         -->
<!-- ═══════════════════════════════════════════════ -->
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

<!-- ═══════════════════════════════════════════════ -->
<!-- BUSCA + CONTROLES DE ORDENAÇÃO                 -->
<!-- ═══════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-body">
        <!-- Linha 1: Campo de busca -->
        <form action="<?= url('/tags') ?>" method="GET" class="row g-3 mb-3">
            <!-- Preserva ordenação atual durante busca (campos hidden) -->
            <input type="hidden" name="ordenar" value="<?= e($ordenarAtual) ?>">
            <input type="hidden" name="direcao" value="<?= e($direcaoAtual) ?>">
            
            <div class="col">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="termo" 
                           class="form-control" 
                           placeholder="Buscar tags por nome..."
                           value="<?= e($termoAtual) ?>">
                    <?php if (!empty($termoAtual)): ?>
                        <a href="<?= tagUrl($filtros, ['termo' => '', 'page' => 1]) ?>" 
                           class="btn btn-outline-secondary" title="Limpar busca">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </div>
        </form>
        
        <!-- Linha 2: Botões de ordenação -->
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted me-2">Ordenar por:</small>
            
            <!-- Botão Nome -->
            <a href="<?= tagSortUrl($filtros, 'nome') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'nome' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-sort-alpha-down"></i> Nome
                <?= tagSortIcon($filtros, 'nome') ?>
            </a>
            
            <!-- Botão Data -->
            <a href="<?= tagSortUrl($filtros, 'data') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'data' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar"></i> Data
                <?= tagSortIcon($filtros, 'data') ?>
            </a>
            
            <!-- Botão Contagem de Artes -->
            <a href="<?= tagSortUrl($filtros, 'contagem') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'contagem' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-images"></i> Artes
                <?= tagSortIcon($filtros, 'contagem') ?>
            </a>
        </div>
    </div>
</div>

<!-- Resultado da busca -->
<?php if (!empty($termoAtual)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <?= $totalRegistros ?> resultado(s) para "<strong><?= e($termoAtual) ?></strong>"
        <a href="<?= tagUrl($filtros, ['termo' => '']) ?>" class="float-end">
            <i class="bi bi-x-circle"></i> Limpar busca
        </a>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- GRID DE CARDS DE TAGS                          -->
<!-- Merge: Melhoria 3 (ícone+desc) + Original     -->
<!-- (dropdown three-dots + Ver Artes + Excluir)    -->
<!-- ═══════════════════════════════════════════════ -->
<?php if (empty($tags)): ?>
    <!-- Estado vazio -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-tags text-muted fs-1"></i>
            <p class="text-muted mt-2">
                <?= !empty($termoAtual) ? 'Nenhuma tag encontrada para esta busca.' : 'Nenhuma tag cadastrada ainda.' ?>
            </p>
            <?php if (empty($termoAtual)): ?>
                <a href="<?= url('/tags/criar') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Criar Primeira Tag
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
                        
                        <!-- ══════════════════════════════════════ -->
                        <!-- HEADER: Badge (c/ ícone) + Dropdown   -->
                        <!-- Badge: Melhoria 3 (getStyleInline)    -->
                        <!-- Dropdown: Restaurado do original [R1] -->
                        <!-- ══════════════════════════════════════ -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <!-- Badge clicável com cor, ícone e nome -->
                            <a href="<?= url("/tags/{$tag->getId()}") ?>" class="text-decoration-none">
                                <span class="badge fs-5" style="<?= $tag->getStyleInline() ?>">
                                    <?php if ($tag->hasIcone()): ?>
                                        <i class="<?= e($tag->getIcone()) ?> me-1"></i>
                                    <?php endif; ?>
                                    <?= e($tag->getNome()) ?>
                                </span>
                            </a>
                            
                            <!-- [R1] Menu dropdown three-dots restaurado -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false"
                                        title="Ações">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <!-- Ver detalhes da tag -->
                                    <li>
                                        <a href="<?= url("/tags/{$tag->getId()}") ?>" 
                                           class="dropdown-item">
                                            <i class="bi bi-eye me-2"></i> Ver Detalhes
                                        </a>
                                    </li>
                                    <!-- Editar tag -->
                                    <li>
                                        <a href="<?= url("/tags/{$tag->getId()}/editar") ?>" 
                                           class="dropdown-item">
                                            <i class="bi bi-pencil me-2"></i> Editar
                                        </a>
                                    </li>
                                    <!-- Separador -->
                                    <li><hr class="dropdown-divider"></li>
                                    <!-- [R3] Excluir tag — abre modal de confirmação -->
                                    <li>
                                        <button class="dropdown-item text-danger" 
                                                onclick="confirmarExclusao(<?= $tag->getId() ?>, '<?= e($tag->getNome()) ?>')">
                                            <i class="bi bi-trash me-2"></i> Excluir
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- ══════════════════════════════════════ -->
                        <!-- BODY: Contagem de artes + Descrição   -->
                        <!-- Contagem: do original                 -->
                        <!-- Descrição: Melhoria 3 (resumida)      -->
                        <!-- ══════════════════════════════════════ -->
                        
                        <!-- Contagem de artes associadas -->
                        <p class="text-muted mb-1">
                            <i class="bi bi-images"></i> 
                            <?= $tag->getArtesCount() ?> arte(s)
                        </p>
                        
                        <!-- Descrição resumida (Melhoria 3) -->
                        <?php if ($tag->hasDescricao()): ?>
                            <p class="text-muted small mb-1" 
                               title="<?= e($tag->getDescricao()) ?>">
                                <i class="bi bi-text-paragraph me-1"></i>
                                <?= e($tag->getDescricaoResumida(60)) ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Barra de cor visual -->
                        <div class="mt-2">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar" 
                                     style="width: 100%; background-color: <?= e($tag->getCor()) ?>;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ══════════════════════════════════════ -->
                    <!-- FOOTER: [R2] Botão "Ver Artes"        -->
                    <!-- Restaurado do original                -->
                    <!-- ══════════════════════════════════════ -->
                    <div class="card-footer bg-transparent">
                        <a href="<?= url("/artes?tag_id={$tag->getId()}") ?>" 
                           class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-filter"></i> Ver Artes com esta Tag
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- PAGINAÇÃO (Melhoria 1)                         -->
    <!-- Bootstrap 5 com janela de 5 páginas             -->
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
                    <!-- Botão Anterior -->
                    <li class="page-item <?= !$temAnterior ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= $temAnterior ? tagUrl($filtros, ['page' => $paginaAtual - 1]) : '#' ?>"
                           <?= !$temAnterior ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // Janela de até 5 números ao redor da página atual
                    // Ex: Para página 7 de 20: [5] [6] [7*] [8] [9]
                    $inicio = max(1, $paginaAtual - 2);
                    $fim    = min($totalPaginas, $paginaAtual + 2);
                    
                    // Ajusta se perto do início ou fim
                    if ($paginaAtual <= 2) {
                        $fim = min($totalPaginas, 5);
                    }
                    if ($paginaAtual >= $totalPaginas - 1) {
                        $inicio = max(1, $totalPaginas - 4);
                    }
                    ?>
                    
                    <!-- Primeira página + reticências -->
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
                            <a class="page-link" href="<?= tagUrl($filtros, ['page' => $i]) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Última página + reticências -->
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
                    
                    <!-- Botão Próxima -->
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
<!-- [R3] MODAL DE CONFIRMAÇÃO DE EXCLUSÃO          -->
<!-- Restaurado do original (index0.php)            -->
<!-- Usa Bootstrap 5 Modal + formulário hidden POST -->
<!-- ═══════════════════════════════════════════════ -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="modalExcluirLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExcluirLabel">
                    <i class="bi bi-exclamation-triangle text-danger"></i> Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>
                    Tem certeza que deseja excluir a tag 
                    <strong id="nomeTagExcluir"></strong>?
                </p>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> 
                    As artes associadas <strong>não serão excluídas</strong>, 
                    apenas a associação com esta tag será removida.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <!-- Formulário hidden que envia DELETE -->
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

<!-- ═══════════════════════════════════════════════ -->
<!-- [R3] JAVASCRIPT: Abrir modal de exclusão       -->
<!-- Restaurado do original (index0.php)            -->
<!-- ═══════════════════════════════════════════════ -->
<script>
/**
 * Abre o modal de confirmação de exclusão.
 * Preenche o nome da tag no texto e configura o action do form.
 * 
 * @param {number} id   - ID da tag a excluir
 * @param {string} nome - Nome da tag (exibido no modal)
 */
function confirmarExclusao(id, nome) {
    // Exibe o nome da tag no corpo do modal
    document.getElementById('nomeTagExcluir').textContent = nome;
    
    // Configura a URL de destino do formulário: POST /tags/{id} com _method=DELETE
    document.getElementById('formExcluir').action = '<?= url('/tags') ?>/' + id;
    
    // Abre o modal Bootstrap 5
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>