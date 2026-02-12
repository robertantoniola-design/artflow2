<?php
/**
 * ============================================
 * VIEW: Listagem de Tags (Melhoria 6 — Gráfico de Distribuição)
 * ============================================
 * 
 * GET /tags
 * GET /tags?page=2&ordenar=contagem&direcao=DESC&termo=Aqua
 * 
 * VARIÁVEIS DISPONÍVEIS (via extract no View::renderFile):
 * - $tags (array<Tag>)            — Tags da página atual (objetos Tag com artesCount)
 * - $paginacao (array)            — Metadados de paginação
 * - $tagsMaisUsadas (array<Tag>)  — Top 5 tags mais populares
 * - $contagemPorTag (array)       — MELHORIA 6: [{nome, cor, quantidade}] para gráfico
 * - $filtros (array)              — Filtros ativos: termo, ordenar, direcao, page
 * 
 * MELHORIAS IMPLEMENTADAS:
 * - [Melhoria 1] Paginação com controles Bootstrap 5 (12 tags/página)
 * - [Melhoria 2] Ordenação clicável (Nome, Data, Contagem) com setas visuais
 * - [Melhoria 3] Ícones nos badges + descrição resumida nos cards
 * - [Melhoria 6] Gráfico de distribuição (Doughnut ↔ Barras) com Chart.js
 * 
 * CORREÇÕES PRESERVADAS (11/02/2026):
 * - [R1] Restaurado dropdown three-dots (...) com menu de ações
 * - [R2] Restaurado link "Ver Artes com esta Tag" no card-footer
 * - [R3] Restaurado botão "Excluir" com modal de confirmação
 * - [FIX] Função tagUrl() corrigida: recebe $filtros como parâmetro
 *         (não usa 'global' — compatível com extract() do View::renderFile)
 * 
 * ARQUIVO: views/tags/index.php
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

// ── MELHORIA 6: Prepara dados do gráfico ──
// Filtra tags que possuem pelo menos 1 arte (evita gráfico poluído com zeros)
$dadosGrafico = array_filter($contagemPorTag ?? [], fn($item) => (int)$item['quantidade'] > 0);
// Indica se há dados suficientes para exibir o gráfico (mínimo 1 tag com artes)
$temDadosGrafico = count($dadosGrafico) >= 1;
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

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MELHORIA 6: GRÁFICO DE DISTRIBUIÇÃO DE TAGS               -->
<!-- Chart.js — Doughnut ↔ Barras (toggle)                     -->
<!-- Só exibe se existirem tags com pelo menos 1 arte associada -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php if ($temDadosGrafico): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-pie-chart"></i> Distribuição de Tags
            </h5>
            <div class="d-flex align-items-center gap-2">
                <!-- Toggle tipo de gráfico: Doughnut ↔ Barras -->
                <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de gráfico">
                    <button type="button" class="btn btn-outline-primary active" id="btnDoughnut" 
                            onclick="trocarTipoGrafico('doughnut')" title="Gráfico de Rosca">
                        <i class="bi bi-pie-chart"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnBar"
                            onclick="trocarTipoGrafico('bar')" title="Gráfico de Barras">
                        <i class="bi bi-bar-chart-line"></i>
                    </button>
                </div>
                <!-- Botão collapse: permite recolher o gráfico -->
                <button class="btn btn-sm btn-outline-secondary" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#graficoCollapse"
                        aria-expanded="true" aria-controls="graficoCollapse"
                        title="Mostrar/ocultar gráfico">
                    <i class="bi bi-chevron-up" id="collapseIcon"></i>
                </button>
            </div>
        </div>
        <div class="collapse show" id="graficoCollapse">
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Canvas do gráfico — altura fixa evita loop de redimensionamento -->
                    <div class="col-lg-8">
                        <div style="position: relative; height: 300px;">
                            <canvas id="tagDistribuicaoChart"></canvas>
                        </div>
                    </div>
                    <!-- Legenda lateral com porcentagens -->
                    <div class="col-lg-4 mt-3 mt-lg-0">
                        <h6 class="text-muted mb-3">
                            <i class="bi bi-info-circle"></i> Resumo
                        </h6>
                        <?php 
                        // Calcula total de artes para porcentagem
                        $totalArtesGrafico = array_sum(array_column($dadosGrafico, 'quantidade'));
                        foreach ($dadosGrafico as $item): 
                            $percentual = $totalArtesGrafico > 0 
                                ? round(($item['quantidade'] / $totalArtesGrafico) * 100, 1) 
                                : 0;
                        ?>
                            <div class="d-flex align-items-center mb-2">
                                <!-- Quadrado colorido como indicador visual -->
                                <span class="d-inline-block me-2 rounded" 
                                      style="width: 14px; height: 14px; background-color: <?= e($item['cor']) ?>; flex-shrink: 0;">
                                </span>
                                <span class="text-truncate me-auto" title="<?= e($item['nome']) ?>">
                                    <?= e($item['nome']) ?>
                                </span>
                                <span class="badge bg-light text-dark ms-2">
                                    <?= (int)$item['quantidade'] ?>
                                </span>
                                <small class="text-muted ms-1" style="min-width: 40px; text-align: right;">
                                    <?= $percentual ?>%
                                </small>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Total geral -->
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong><?= $totalArtesGrafico ?> arte(s)</strong>
                        </div>
                    </div>
                </div>
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

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MELHORIA 6: JavaScript — Chart.js para Gráfico de Tags    -->
<!-- Carrega Chart.js via CDN + inicializa Doughnut com toggle  -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php if ($temDadosGrafico): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
/**
 * ============================================
 * MELHORIA 6: Gráfico de Distribuição de Tags
 * ============================================
 * 
 * Usa Chart.js para renderizar Doughnut ou Bar chart.
 * Dados vêm do PHP ($contagemPorTag) via json_encode.
 * Cores usam as cores reais de cada tag do banco de dados.
 * 
 * FUNCIONALIDADES:
 * - Toggle Doughnut ↔ Barras (horizontal)
 * - Tooltip com nome + quantidade + porcentagem
 * - Cores reais das tags do banco
 * - Responsivo com altura fixa (300px)
 * - Collapse para recolher o gráfico
 */

// ── Dados vindos do PHP (injetados como JSON) ──
const dadosGrafico = <?= json_encode(array_values($dadosGrafico), JSON_UNESCAPED_UNICODE) ?>;

// ── Extrai arrays para Chart.js ──
const labels = dadosGrafico.map(item => item.nome);
const quantidades = dadosGrafico.map(item => parseInt(item.quantidade));
const cores = dadosGrafico.map(item => item.cor);

// ── Calcula total para porcentagem nos tooltips ──
const totalArtes = quantidades.reduce((sum, val) => sum + val, 0);

// ── Gera cores com transparência para hover ──
const coresHover = cores.map(cor => cor + '99');

// ── Variável global do gráfico (para destruir e recriar no toggle) ──
let chartInstance = null;

// ── Tipo de gráfico atual (default: doughnut) ──
let tipoAtual = 'doughnut';

/**
 * Cria (ou recria) o gráfico no canvas.
 * Destrói o anterior se existir para evitar memory leak do Chart.js.
 * 
 * @param {string} tipo - 'doughnut' ou 'bar'
 */
function criarGrafico(tipo) {
    const ctx = document.getElementById('tagDistribuicaoChart').getContext('2d');
    
    // Destrói gráfico anterior se existir (previne memory leak)
    if (chartInstance) {
        chartInstance.destroy();
    }
    
    // ── Configuração específica por tipo de gráfico ──
    const isDoughnut = (tipo === 'doughnut');
    
    const config = {
        type: isDoughnut ? 'doughnut' : 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Artes',
                data: quantidades,
                backgroundColor: cores,
                hoverBackgroundColor: coresHover,
                borderColor: isDoughnut ? '#fff' : cores,
                borderWidth: isDoughnut ? 2 : 1,
                borderRadius: isDoughnut ? 0 : 4,
                barPercentage: 0.7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            
            layout: {
                padding: { top: 5, bottom: 5 }
            },
            
            plugins: {
                legend: {
                    display: isDoughnut,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyleWidth: 12,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const nome = context.label || '';
                            const valor = context.parsed.y ?? context.parsed ?? 0;
                            const pct = totalArtes > 0 
                                ? ((valor / totalArtes) * 100).toFixed(1) 
                                : 0;
                            return ' ' + nome + ': ' + valor + ' arte(s) (' + pct + '%)';
                        }
                    }
                }
            },
            
            // Escalas: só para gráfico de barras
            scales: isDoughnut ? {} : {
                x: {
                    beginAtZero: true,
                    grid: { display: true, color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(value) {
                            return Number.isInteger(value) ? value : '';
                        },
                        font: { size: 11 }
                    },
                    title: {
                        display: true,
                        text: 'Quantidade de Artes',
                        font: { size: 12, weight: 'bold' }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { size: 12 } }
                }
            },
            
            // Barras horizontais
            indexAxis: isDoughnut ? 'x' : 'y',
            
            // Doughnut: tamanho do "buraco" central
            cutout: isDoughnut ? '55%' : undefined
        }
    };
    
    chartInstance = new Chart(ctx, config);
}

/**
 * Alterna entre Doughnut e Barras.
 * Atualiza os botões (active/inactive) e recria o gráfico.
 * 
 * @param {string} tipo - 'doughnut' ou 'bar'
 */
function trocarTipoGrafico(tipo) {
    tipoAtual = tipo;
    
    // Atualiza estado visual dos botões
    document.getElementById('btnDoughnut').classList.toggle('active', tipo === 'doughnut');
    document.getElementById('btnBar').classList.toggle('active', tipo === 'bar');
    
    // Recria o gráfico com o novo tipo
    criarGrafico(tipo);
}

// ── Inicializa o gráfico quando a página carrega ──
document.addEventListener('DOMContentLoaded', function() {
    criarGrafico('doughnut');
});

// ── Collapse: anima ícone da seta (chevron up ↔ down) ──
const graficoCollapse = document.getElementById('graficoCollapse');
if (graficoCollapse) {
    graficoCollapse.addEventListener('hidden.bs.collapse', function() {
        document.getElementById('collapseIcon').classList.replace('bi-chevron-up', 'bi-chevron-down');
    });
    graficoCollapse.addEventListener('shown.bs.collapse', function() {
        document.getElementById('collapseIcon').classList.replace('bi-chevron-down', 'bi-chevron-up');
        // Redimensiona o gráfico quando expandido (Chart.js precisa disso)
        if (chartInstance) chartInstance.resize();
    });
}
</script>
<?php endif; ?>