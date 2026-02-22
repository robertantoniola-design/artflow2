<?php
/**
 * ============================================
 * VIEW: Listagem de Artes (Melhoria 6 — Gráficos + Cards de Resumo)
 * ============================================
 * 
 * GET /artes
 * GET /artes?pagina=2&status=disponivel&tag_id=3&termo=retrato
 * GET /artes?ordenar=nome&direcao=ASC&pagina=1
 * 
 * VARIÁVEIS DISPONÍVEIS (via extract no View::renderFile):
 * - $artes (array<Arte>)                  — Artes da página atual
 * - $paginacao (array)                     — Metadados de paginação
 * - $filtros (array)                       — Filtros ativos
 * - $tags (array<Tag>)                     — Tags para dropdown de filtro
 * - $estatisticas (array)                  — Contagem por status (disponivel, em_producao, vendida, reservada)
 * - $distribuicaoComplexidade (array)      — [M6] Contagem por complexidade (baixa, media, alta)
 * - $resumoCards (array)                   — [M6] total, valor_estoque, horas_totais, disponiveis
 * - $temDadosGrafico (bool)                — [M6] Se false, oculta gráficos e cards M6
 * 
 * MELHORIAS IMPLEMENTADAS:
 * - [Fase 1]     Status "reservada" no dropdown, labels e cores
 * - [Melhoria 1] Paginação com controles Bootstrap 5 (12 artes/página)
 * - [Melhoria 1] Preservação de filtros (status, tag_id, termo) ao paginar
 * - [Melhoria 1] Indicador "Mostrando X-Y de Z artes"
 * - [Melhoria 2] Ordenação clicável (6 colunas) com setas visuais ▲/▼
 * - [Melhoria 2] Toggle automático ASC↔DESC ao clicar na coluna ativa
 * - [Melhoria 2] Headers da tabela clicáveis com ícones de direção
 * - [Melhoria 4] Coluna de thumbnail 45x45 na tabela
 * - [Melhoria 6] Cards de resumo financeiro (Total, Estoque, Horas, Disponíveis)
 * - [Melhoria 6] Gráfico Doughnut (status) + Barras horizontais (complexidade)
 * - [Melhoria 6] Cards M6 SUBSTITUEM os cards de contagem por status antigos
 *                (a informação de status agora está no gráfico Doughnut com legenda)
 * 
 * ARQUIVO: views/artes/index.php
 */
$currentPage = 'artes';

// ══════════════════════════════════════════════════════════════
// FUNÇÕES HELPER PARA URLs DE PAGINAÇÃO E ORDENAÇÃO
// ══════════════════════════════════════════════════════════════

/**
 * Monta URL preservando TODOS os parâmetros atuais.
 * Permite trocar apenas um parâmetro sem perder os outros.
 * 
 * @param array $filtros Filtros atuais vindos do controller
 * @param array $params  Parâmetros a sobrescrever
 * @return string URL completa
 */
function arteUrl(array $filtros, array $params = []): string {
    $merged = array_merge([
        'termo'   => $filtros['termo'] ?? '',
        'status'  => $filtros['status'] ?? '',
        'tag_id'  => $filtros['tag_id'] ?? '',
        'ordenar' => $filtros['ordenar'] ?? 'created_at',
        'direcao' => $filtros['direcao'] ?? 'DESC',
        'pagina'  => $filtros['pagina'] ?? 1,
    ], $params);
    
    $query = [];
    
    if (!empty($merged['termo'])) {
        $query['termo'] = $merged['termo'];
    }
    if (!empty($merged['status'])) {
        $query['status'] = $merged['status'];
    }
    if (!empty($merged['tag_id'])) {
        $query['tag_id'] = $merged['tag_id'];
    }
    
    // Ordenação: SEMPRE inclui para preservar estado entre páginas
    $query['ordenar'] = $merged['ordenar'];
    $query['direcao'] = $merged['direcao'];
    
    if ((int)$merged['pagina'] > 1) {
        $query['pagina'] = (int)$merged['pagina'];
    }
    
    $qs = !empty($query) ? '?' . http_build_query($query) : '';
    return url('/artes') . $qs;
}

/**
 * [MELHORIA 2] Gera URL de ordenação com toggle automático de direção.
 */
function arteSortUrl(array $filtros, string $coluna): string {
    $ordenarAtual = $filtros['ordenar'] ?? 'created_at';
    $direcaoAtual = $filtros['direcao'] ?? 'DESC';
    
    if ($ordenarAtual === $coluna) {
        $novaDirecao = ($direcaoAtual === 'ASC') ? 'DESC' : 'ASC';
    } else {
        $colunasDesc = ['preco_custo', 'horas_trabalhadas', 'created_at'];
        $novaDirecao = in_array($coluna, $colunasDesc) ? 'DESC' : 'ASC';
    }
    
    return arteUrl($filtros, [
        'ordenar' => $coluna,
        'direcao' => $novaDirecao,
        'pagina'  => 1
    ]);
}

/**
 * [MELHORIA 2] Retorna ícone HTML de seta para indicar direção de ordenação.
 */
function arteSortIcon(array $filtros, string $coluna): string {
    $ordenarAtual = $filtros['ordenar'] ?? 'created_at';
    $direcaoAtual = $filtros['direcao'] ?? 'DESC';
    
    if ($ordenarAtual !== $coluna) {
        return '<i class="bi bi-arrow-down-up text-muted opacity-50"></i>';
    }
    
    $colunasTexto = ['nome', 'complexidade', 'status'];
    
    if (in_array($coluna, $colunasTexto)) {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
    } elseif ($coluna === 'created_at') {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-down' : 'bi-sort-up';
    } else {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up';
    }
    
    return '<i class="bi ' . $icone . ' text-primary"></i>';
}

// ══════════════════════════════════════════════════════════════
// EXTRAÇÃO DE DADOS DE PAGINAÇÃO
// ══════════════════════════════════════════════════════════════
$total        = $paginacao['total'] ?? 0;
$porPagina    = $paginacao['porPagina'] ?? 12;
$paginaAtual  = $paginacao['paginaAtual'] ?? 1;
$totalPaginas = $paginacao['totalPaginas'] ?? 1;
$temAnterior  = $paginacao['temAnterior'] ?? false;
$temProxima   = $paginacao['temProxima'] ?? false;

// Cálculo de "Mostrando X-Y de Z"
$inicio = $total > 0 ? ($paginaAtual - 1) * $porPagina + 1 : 0;
$fim    = min($paginaAtual * $porPagina, $total);

// [MELHORIA 2] Filtros de ordenação para botões/headers
$ordenarAtual = $filtros['ordenar'] ?? 'created_at';
$direcaoAtual = $filtros['direcao'] ?? 'DESC';

// Mapas de labels e cores (reusados na tabela)
$statusLabels = [
    'disponivel'  => 'Disponível',
    'em_producao' => 'Em Produção',
    'vendida'     => 'Vendida',
    'reservada'   => 'Reservada',
];
$statusCores = [
    'disponivel'  => 'success',
    'em_producao' => 'warning',
    'vendida'     => 'info',
    'reservada'   => 'secondary',
];

$complexLabels = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
$complexCores  = ['baixa' => 'success', 'media' => 'warning', 'alta' => 'danger'];
?>

<!-- ═══════════════════════════════════════════════ -->
<!-- HEADER: Título + Botão Nova Arte               -->
<!-- ═══════════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-palette text-primary"></i> Artes
        </h2>
        <p class="text-muted mb-0">
            <?php if ($total > 0): ?>
                <?= $total ?> arte<?= $total > 1 ? 's' : '' ?> cadastrada<?= $total > 1 ? 's' : '' ?>
            <?php else: ?>
                Gerencie suas obras de arte
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= url('/artes/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nova Arte
    </a>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- [MELHORIA 6] Cards de Resumo — 4 Indicadores Financeiros      -->
<!-- SUBSTITUEM os cards de contagem por status antigos.            -->
<!-- A informação de status agora está no gráfico Doughnut abaixo. -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php if ($temDadosGrafico): ?>
<div class="row g-3 mb-4">
    
    <!-- Card: Total de Artes -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-primary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Total de Artes</small>
                        <h3 class="mb-0 mt-1"><?= $resumoCards['total'] ?></h3>
                    </div>
                    <div class="fs-1 text-primary opacity-25">
                        <i class="bi bi-palette2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card: Valor em Estoque -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-success h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Valor em Estoque</small>
                        <h3 class="mb-0 mt-1">
                            R$ <?= number_format($resumoCards['valor_estoque'], 2, ',', '.') ?>
                        </h3>
                    </div>
                    <div class="fs-1 text-success opacity-25">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <small class="text-muted">Excluindo vendidas</small>
            </div>
        </div>
    </div>
    
    <!-- Card: Horas Totais Investidas -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-warning h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Horas Investidas</small>
                        <h3 class="mb-0 mt-1">
                            <?= number_format($resumoCards['horas_totais'], 1, ',', '.') ?>h
                        </h3>
                    </div>
                    <div class="fs-1 text-warning opacity-25">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card: Artes Disponíveis -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-4 border-info h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Disponíveis</small>
                        <h3 class="mb-0 mt-1"><?= $resumoCards['disponiveis'] ?></h3>
                    </div>
                    <div class="fs-1 text-info opacity-25">
                        <i class="bi bi-bag-check"></i>
                    </div>
                </div>
                <small class="text-muted">Prontas para venda</small>
            </div>
        </div>
    </div>
    
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- [MELHORIA 6] Gráficos de Distribuição (Chart.js)              -->
<!-- Doughnut: Status | Barras: Complexidade                       -->
<!-- Container com altura fixa de 280px (lição do Dashboard)       -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-pie-chart me-2"></i>
            Distribuição de Artes
        </h5>
        <button class="btn btn-sm btn-outline-secondary" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#graficosCollapse"
                aria-expanded="true" 
                aria-controls="graficosCollapse"
                title="Expandir/Recolher">
            <i class="bi bi-chevron-up" id="collapseIconGraficos"></i>
        </button>
    </div>
    
    <div class="collapse show" id="graficosCollapse">
        <div class="card-body">
            <div class="row">
                
                <!-- Gráfico 1: Distribuição por Status (Doughnut) -->
                <div class="col-md-6">
                    <h6 class="text-center text-muted mb-3">
                        <i class="bi bi-circle-half"></i> Por Status
                    </h6>
                    <!-- Container altura fixa — evita loop de redimensionamento Chart.js -->
                    <div style="position: relative; height: 280px;">
                        <canvas id="graficoStatus"></canvas>
                    </div>
                    
                    <!-- Legenda manual com valores (substitui os cards de status antigos) -->
                    <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap">
                        <?php
                        // [M6] Cores do gráfico (Bootstrap 5 exatas)
                        $statusGraficoCores = [
                            'disponivel'  => ['cor' => '#198754', 'label' => 'Disponível'],
                            'em_producao' => ['cor' => '#ffc107', 'label' => 'Em Produção'],
                            'vendida'     => ['cor' => '#0dcaf0', 'label' => 'Vendida'],
                            'reservada'   => ['cor' => '#0d6efd', 'label' => 'Reservada'],
                        ];
                        foreach ($statusGraficoCores as $key => $info):
                            $valor = $estatisticas[$key] ?? 0;
                        ?>
                            <small class="d-flex align-items-center gap-1">
                                <span class="d-inline-block rounded-circle" 
                                      style="width:10px; height:10px; background:<?= $info['cor'] ?>"></span>
                                <?= $info['label'] ?>: <strong><?= $valor ?></strong>
                            </small>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Gráfico 2: Distribuição por Complexidade (Barras Horizontais) -->
                <div class="col-md-6">
                    <h6 class="text-center text-muted mb-3">
                        <i class="bi bi-bar-chart-line"></i> Por Complexidade
                    </h6>
                    <div style="position: relative; height: 280px;">
                        <canvas id="graficoComplexidade"></canvas>
                    </div>
                    
                    <!-- Legenda manual -->
                    <div class="d-flex justify-content-center gap-3 mt-2">
                        <?php
                        $complexGraficoCores = [
                            'baixa' => ['cor' => '#198754', 'label' => 'Baixa'],
                            'media' => ['cor' => '#ffc107', 'label' => 'Média'],
                            'alta'  => ['cor' => '#dc3545', 'label' => 'Alta'],
                        ];
                        foreach ($complexGraficoCores as $key => $info):
                            $valor = $distribuicaoComplexidade[$key] ?? 0;
                        ?>
                            <small class="d-flex align-items-center gap-1">
                                <span class="d-inline-block rounded-circle" 
                                      style="width:10px; height:10px; background:<?= $info['cor'] ?>"></span>
                                <?= $info['label'] ?>: <strong><?= $valor ?></strong>
                            </small>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </div><!-- /row -->
        </div><!-- /card-body -->
    </div><!-- /collapse -->
</div><!-- /card gráficos -->

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════ -->
<!-- FALLBACK: Quando NÃO há artes no banco ($temDadosGrafico=false) -->
<!-- Exibe os cards simples de status com zeros (comportamento anterior) -->
<!-- Motivo: sem dados, os gráficos Chart.js ficam vazios/feios         -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-success">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-success fs-4">0</div>
                <small class="text-muted">Disponíveis</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-warning fs-4">0</div>
                <small class="text-muted">Em Produção</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-info">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-info fs-4">0</div>
                <small class="text-muted">Vendidas</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-secondary">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-secondary fs-4">0</div>
                <small class="text-muted">Reservadas</small>
            </div>
        </div>
    </div>
</div>
<?php endif; // $temDadosGrafico ?>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- BARRA DE FILTROS + ORDENAÇÃO                              -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/artes') ?>" method="GET" class="row g-3 align-items-end">
            <!-- Busca por nome/descrição -->
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" 
                       name="termo" 
                       class="form-control" 
                       placeholder="Nome da arte..."
                       value="<?= e($filtros['termo'] ?? '') ?>">
            </div>
            
            <!-- Filtro por status -->
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
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
            </div>
            
            <!-- [M2] Preserva ordenação durante busca -->
            <input type="hidden" name="ordenar" value="<?= e($ordenarAtual) ?>">
            <input type="hidden" name="direcao" value="<?= e($direcaoAtual) ?>">
        </form>
        
        <!-- Link "Limpar filtros" -->
        <?php if (!empty($filtros['termo']) || !empty($filtros['status']) || !empty($filtros['tag_id'])): ?>
            <div class="mt-2">
                <a href="<?= url('/artes') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                </a>
            </div>
        <?php endif; ?>
        
        <!-- ══════════════════════════════════════════ -->
        <!-- [MELHORIA 2] Botões de ordenação          -->
        <!-- ══════════════════════════════════════════ -->
        <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top">
            <span class="text-muted small me-1">
                <i class="bi bi-sort-down"></i> Ordenar:
            </span>
            
            <a href="<?= arteSortUrl($filtros, 'nome') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'nome' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por nome">
                Nome <?= arteSortIcon($filtros, 'nome') ?>
            </a>
            
            <a href="<?= arteSortUrl($filtros, 'complexidade') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'complexidade' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por complexidade">
                Complexidade <?= arteSortIcon($filtros, 'complexidade') ?>
            </a>
            
            <a href="<?= arteSortUrl($filtros, 'preco_custo') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'preco_custo' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por custo">
                Custo <?= arteSortIcon($filtros, 'preco_custo') ?>
            </a>
            
            <a href="<?= arteSortUrl($filtros, 'horas_trabalhadas') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'horas_trabalhadas' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por horas trabalhadas">
                Horas <?= arteSortIcon($filtros, 'horas_trabalhadas') ?>
            </a>
            
            <a href="<?= arteSortUrl($filtros, 'status') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'status' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por status">
                Status <?= arteSortIcon($filtros, 'status') ?>
            </a>
            
            <a href="<?= arteSortUrl($filtros, 'created_at') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'created_at' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por data de criação">
                Data <?= arteSortIcon($filtros, 'created_at') ?>
            </a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- TABELA DE ARTES                                           -->
<!-- ══════════════════════════════════════════════════════════ -->
<?php if (empty($artes)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-palette2 display-1 text-muted"></i>
            <p class="text-muted mt-3 mb-0">
                <?php if (!empty($filtros['termo']) || !empty($filtros['status']) || !empty($filtros['tag_id'])): ?>
                    Nenhuma arte encontrada com os filtros aplicados.
                <?php else: ?>
                    Nenhuma arte cadastrada ainda. 
                    <a href="<?= url('/artes/criar') ?>">Criar primeira arte</a>
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    
    <!-- [M1] Indicador de total -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <small class="text-muted">
            Mostrando <?= $inicio ?>–<?= $fim ?> de <?= $total ?> arte<?= $total !== 1 ? 's' : '' ?>
        </small>
    </div>
    
    <!-- Tabela responsiva -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <!-- [M2] Headers clicáveis com setas ▲/▼ -->
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="<?= arteSortUrl($filtros, 'nome') ?>" 
                               class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Nome <?= arteSortIcon($filtros, 'nome') ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= arteSortUrl($filtros, 'complexidade') ?>" 
                               class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Complexidade <?= arteSortIcon($filtros, 'complexidade') ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= arteSortUrl($filtros, 'preco_custo') ?>" 
                               class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Custo <?= arteSortIcon($filtros, 'preco_custo') ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= arteSortUrl($filtros, 'horas_trabalhadas') ?>" 
                               class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Horas <?= arteSortIcon($filtros, 'horas_trabalhadas') ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= arteSortUrl($filtros, 'status') ?>" 
                               class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Status <?= arteSortIcon($filtros, 'status') ?>
                            </a>
                        </th>
                        <th style="width: 60px;">Imagem</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artes as $arte): ?>
                        <tr>
                            <!-- Nome -->
                            <td>
                                <a href="<?= url('/artes/' . $arte->getId()) ?>" class="text-decoration-none fw-medium">
                                    <?= e($arte->getNome()) ?>
                                </a>
                                <?php if ($arte->getDescricao()): ?>
                                    <br><small class="text-muted"><?= e(mb_strimwidth($arte->getDescricao(), 0, 60, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Complexidade -->
                            <td>
                                <?php $comp = $arte->getComplexidade(); ?>
                                <span class="badge bg-<?= $complexCores[$comp] ?? 'secondary' ?>">
                                    <?= $complexLabels[$comp] ?? ucfirst($comp) ?>
                                </span>
                            </td>
                            
                            <!-- Custo -->
                            <td>R$ <?= number_format($arte->getPrecoCusto(), 2, ',', '.') ?></td>
                            
                            <!-- Horas -->
                            <td><?= number_format($arte->getHorasTrabalhadas(), 1, ',', '.') ?>h</td>
                            
                            <!-- Status -->
                            <td>
                                <?php $st = $arte->getStatus(); ?>
                                <span class="badge bg-<?= $statusCores[$st] ?? 'secondary' ?>">
                                    <?= $statusLabels[$st] ?? ucfirst($st) ?>
                                </span>
                            </td>
                            
                            <!-- [M4] Thumbnail -->
                            <td class="text-center align-middle">
                                <?php if ($arte->getImagem()): ?>
                                    <img src="<?= url('/' . e($arte->getImagem())) ?>" 
                                         alt="<?= e($arte->getNome()) ?>" 
                                         class="rounded" 
                                         style="width: 45px; height: 45px; object-fit: cover;"
                                         loading="lazy">
                                <?php else: ?>
                                    <span class="text-muted" title="Sem imagem">
                                        <i class="bi bi-image" style="font-size: 1.2rem;"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Ações -->
                            <td class="text-end">
                                <a href="<?= url('/artes/' . $arte->getId()) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/artes/' . $arte->getId() . '/editar') ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ══════════════════════════════════════════════════════ -->
    <!-- [MELHORIA 1] CONTROLES DE PAGINAÇÃO                   -->
    <!-- ══════════════════════════════════════════════════════ -->
    <?php if ($totalPaginas > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">
                Página <?= $paginaAtual ?> de <?= $totalPaginas ?>
            </small>
            
            <nav aria-label="Paginação de artes">
                <ul class="pagination mb-0">
                    
                    <!-- Anterior -->
                    <li class="page-item <?= !$temAnterior ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= $temAnterior ? arteUrl($filtros, ['pagina' => $paginaAtual - 1]) : '#' ?>"
                           <?= !$temAnterior ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // Janela de 5 páginas centrada na atual
                    $janelaSize = 5;
                    $metade = floor($janelaSize / 2);
                    $janelaInicio = max(1, $paginaAtual - $metade);
                    $janelaFim = min($totalPaginas, $janelaInicio + $janelaSize - 1);
                    
                    if ($janelaFim - $janelaInicio < $janelaSize - 1) {
                        $janelaInicio = max(1, $janelaFim - $janelaSize + 1);
                    }
                    
                    // Reticências início
                    if ($janelaInicio > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => 1]) ?>">1</a>
                        </li>
                        <?php if ($janelaInicio > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Números -->
                    <?php for ($p = $janelaInicio; $p <= $janelaFim; $p++): ?>
                        <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
                            <?php if ($p === $paginaAtual): ?>
                                <span class="page-link"><?= $p ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => $p]) ?>"><?= $p ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Reticências fim -->
                    <?php if ($janelaFim < $totalPaginas): ?>
                        <?php if ($janelaFim < $totalPaginas - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => $totalPaginas]) ?>"><?= $totalPaginas ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Próxima -->
                    <li class="page-item <?= !$temProxima ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= $temProxima ? arteUrl($filtros, ['pagina' => $paginaAtual + 1]) : '#' ?>"
                           <?= !$temProxima ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
    
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- [MELHORIA 6] Chart.js — Script dos Gráficos                   -->
<!-- CDN carregado condicionalmente (só se há dados)               -->
<!-- Padrão: Chart.js 4.4.7 (mesmo de Tags M6 e Metas M3)        -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php if ($temDadosGrafico): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<script>
/**
 * [MELHORIA 6] Gráficos de Distribuição de Artes
 * 
 * 1. Doughnut — Distribuição por Status (4 fatias)
 * 2. Barras Horizontais — Distribuição por Complexidade (3 barras)
 * 
 * maintainAspectRatio: false + container altura fixa = sem loop de redimensionamento
 * (lição do Dashboard)
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // ── Gráfico 1: Distribuição por Status (Doughnut) ──
    const ctxStatus = document.getElementById('graficoStatus');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Disponível', 'Em Produção', 'Vendida', 'Reservada'],
                datasets: [{
                    data: [
                        <?= (int)($estatisticas['disponivel'] ?? 0) ?>,
                        <?= (int)($estatisticas['em_producao'] ?? 0) ?>,
                        <?= (int)($estatisticas['vendida'] ?? 0) ?>,
                        <?= (int)($estatisticas['reservada'] ?? 0) ?>
                    ],
                    backgroundColor: [
                        '#198754',  // success — Disponível
                        '#ffc107',  // warning — Em Produção
                        '#0dcaf0',  // info    — Vendida
                        '#0d6efd'   // primary — Reservada
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: { display: false }, // Legenda manual em HTML
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const valor = context.parsed;
                                const pct = total > 0 ? ((valor / total) * 100).toFixed(1) : 0;
                                return ' ' + context.label + ': ' + valor + ' arte(s) (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // ── Gráfico 2: Distribuição por Complexidade (Barras Horizontais) ──
    const ctxComplex = document.getElementById('graficoComplexidade');
    if (ctxComplex) {
        new Chart(ctxComplex, {
            type: 'bar',
            data: {
                labels: ['Baixa', 'Média', 'Alta'],
                datasets: [{
                    label: 'Quantidade',
                    data: [
                        <?= (int)($distribuicaoComplexidade['baixa'] ?? 0) ?>,
                        <?= (int)($distribuicaoComplexidade['media'] ?? 0) ?>,
                        <?= (int)($distribuicaoComplexidade['alta'] ?? 0) ?>
                    ],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                    borderWidth: 1,
                    borderColor: ['#157347', '#e0a800', '#bb2d3b'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',  // Barras horizontais
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.parsed.x + ' arte(s)';
                            }
                        }
                    }
                },
                scales: {
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
                        ticks: { font: { size: 13, weight: 'bold' } }
                    }
                }
            }
        });
    }
    
    // ── Collapse: anima ícone da seta ──
    const graficosCollapse = document.getElementById('graficosCollapse');
    if (graficosCollapse) {
        graficosCollapse.addEventListener('hidden.bs.collapse', function() {
            const icon = document.getElementById('collapseIconGraficos');
            if (icon) icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
        });
        graficosCollapse.addEventListener('shown.bs.collapse', function() {
            const icon = document.getElementById('collapseIconGraficos');
            if (icon) icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
            // Força resize dos gráficos ao expandir
            // (Chart.js precisa recalcular após display:none → block)
            Chart.instances.forEach(function(chart) {
                chart.resize();
            });
        });
    }
});
</script>
<?php endif; // $temDadosGrafico ?>