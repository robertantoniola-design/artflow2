<?php
/**
 * ============================================
 * VIEW: Listagem de Vendas
 * GET /vendas
 * ============================================
 * 
 * CORREÇÕES ORIGINAIS (29/01/2026):
 * - clientesSelect é [id => nome], usar $id => $nome
 * - vendas podem ser objetos ou arrays (compatibilidade mantida)
 * 
 * MELHORIAS (23/02/2026):
 * - M1: Paginação 12/página (controles Bootstrap 5 com janela de 5 páginas)
 * - M2: Headers clicáveis com ícones de ordenação (7 colunas incluindo R$/h)
 * - M3: Filtros combinados (termo + cliente + forma pgto + período)
 * 
 * VARIÁVEIS RECEBIDAS DO CONTROLLER:
 * - $vendas         : array de objetos Venda (com Arte e Cliente hydrated)
 * - $paginacao      : array com dados de paginação (M1)
 * - $estatisticas   : array com stats globais (cards)
 * - $clientesSelect : array [id => nome] para dropdown de filtro
 * - $resumo         : array com total_vendas, valor_total, lucro_total
 * - $filtros        : array com filtros ativos para preservar nos links
 */
$currentPage = 'vendas';


// ============================================
// [M6] Preparação de dados para gráficos Chart.js
// ============================================

// Variáveis vindas do Controller: $vendasMensais, $distribuicaoPgto

// Flag: só carrega Chart.js se há dados para exibir
$temDadosGrafico = !empty($vendasMensais ?? []) || !empty($distribuicaoPgto ?? []);

// Labels legíveis para formas de pagamento (reutiliza array do index)
$labelsPgto = [
    'dinheiro'       => 'Dinheiro',
    'pix'            => 'PIX',
    'cartao_credito' => 'Cartão Crédito',
    'cartao_debito'  => 'Cartão Débito',
    'transferencia'  => 'Transferência',
    'outro'          => 'Outro'
];

// Cores para gráfico doughnut (mesma paleta dos badges da tabela)
$coresPgto = [
    'pix'            => '#198754',  // success (verde)
    'dinheiro'       => '#0d6efd',  // primary (azul)
    'cartao_credito' => '#ffc107',  // warning (amarelo)
    'cartao_debito'  => '#0dcaf0',  // info (ciano)
    'transferencia'  => '#6c757d',  // secondary (cinza)
    'outro'          => '#212529'   // dark (preto)
];


// ============================================
// HELPERS: Funções auxiliares para URLs (M1+M2)
// ============================================
// NOTA: Usam $_GET (superglobal PHP) para ler os parâmetros da URL atual.
// $_GET funciona em QUALQUER escopo — diferente de 'global' ou '$GLOBALS'
// que podem falhar quando a view é renderizada dentro de um método
// (BaseController::view() usa extract() → escopo local, não global).

/**
 * Helper: Monta URL de vendas preservando TODOS os parâmetros da URL atual
 * Usado nos links de paginação para não perder filtros/ordenação ao mudar de página
 * 
 * Lê $_GET diretamente para capturar todos os query params atuais,
 * depois sobrescreve com os overrides passados como argumento.
 * 
 * @param array $override Parâmetros a substituir (ex: ['pagina' => 3])
 * @return string URL completa (ex: /artflow2/vendas?ordenar=valor&direcao=ASC&pagina=3)
 */
function vendaUrl(array $override = []): string {
    // $_GET contém EXATAMENTE os parâmetros da URL atual — 100% confiável
    $params = array_merge($_GET, $override);
    
    // Remove parâmetros vazios para URL limpa
    // (evita ?termo=&cliente_id=&forma_pagamento= na URL)
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    
    $query = http_build_query($params);
    return url('/vendas') . ($query ? '?' . $query : '');
}

/**
 * Helper: Monta URL para ordenação (toggle ASC/DESC)
 * Ao clicar no mesmo header, inverte a direção. Em header diferente, usa direção padrão.
 * 
 * Lê $_GET['ordenar'] e $_GET['direcao'] para saber o estado atual.
 * Se a coluna clicada é a mesma que está ativa, faz toggle (ASC↔DESC).
 * Se é outra coluna, usa a direcaoPadrao definida no header.
 * 
 * @param string $campo Nome do campo no banco (ex: 'valor', 'data_venda')
 * @param string $direcaoPadrao Direção default para este campo ('ASC' ou 'DESC')
 * @return string URL com parâmetros de ordenação
 */
function vendaSortUrl(string $campo, string $direcaoPadrao = 'ASC'): string {
    // Lê estado atual direto da URL (superglobal — funciona em qualquer escopo)
    $ordenarAtual = $_GET['ordenar'] ?? 'data_venda';
    $direcaoAtual = strtoupper($_GET['direcao'] ?? 'DESC');
    
    if ($ordenarAtual === $campo) {
        // Mesma coluna: Toggle ASC → DESC, DESC → ASC
        $novaDirecao = ($direcaoAtual === 'ASC') ? 'DESC' : 'ASC';
    } else {
        // Coluna diferente: usa direção padrão do campo
        $novaDirecao = $direcaoPadrao;
    }
    
    return vendaUrl([
        'ordenar' => $campo,
        'direcao' => $novaDirecao,
        'pagina'  => 1  // Reset para página 1 ao reordenar
    ]);
}

/**
 * Helper: Retorna ícone Bootstrap Icons para o estado de ordenação atual
 * 
 * Mostra ícone cinza neutro (bi-arrow-down-up) quando a coluna NÃO está ativa.
 * Mostra ícone azul direcional quando a coluna ESTÁ ativa:
 * - 'alpha': bi-sort-alpha-down (ASC) / bi-sort-alpha-up (DESC)
 * - 'numeric': bi-sort-numeric-down (ASC) / bi-sort-numeric-up (DESC)
 * - 'down': bi-sort-down (ASC) / bi-sort-up (DESC)
 * 
 * @param string $campo Nome do campo
 * @param string $tipo 'alpha' para texto, 'numeric' para números, 'down' genérico
 * @return string HTML do ícone Bootstrap Icons
 */
function vendaSortIcon(string $campo, string $tipo = 'down'): string {
    // Lê estado atual direto da URL (superglobal — funciona em qualquer escopo)
    $ordenarAtual = $_GET['ordenar'] ?? 'data_venda';
    $direcaoAtual = strtoupper($_GET['direcao'] ?? 'DESC');
    
    // Se não é a coluna ativa, mostra ícone neutro (cinza)
    if ($ordenarAtual !== $campo) {
        return '<i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i>';
    }
    
    // Coluna ativa: mostra ícone de direção com cor primária
    if ($tipo === 'alpha') {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
    } elseif ($tipo === 'numeric') {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up';
    } else {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-down' : 'bi-sort-up';
    }
    
    return '<i class="bi ' . $icone . ' text-primary ms-1" style="font-size: 0.75rem;"></i>';
}

// ============================================
// FORMAS DE PAGAMENTO: Labels legíveis + badges
// ============================================
$formasPagamento = [
    'dinheiro'         => 'Dinheiro',
    'pix'              => 'PIX',
    'cartao_credito'   => 'Cartão de Crédito',
    'cartao_debito'    => 'Cartão de Débito',
    'transferencia'    => 'Transferência',
    'outro'            => 'Outro'
];

// Cores dos badges por forma de pagamento
$badgeClasses = [
    'pix'              => 'bg-success',
    'dinheiro'         => 'bg-primary',
    'cartao_credito'   => 'bg-warning text-dark',
    'cartao_debito'    => 'bg-info text-dark',
    'transferencia'    => 'bg-secondary',
    'outro'            => 'bg-dark'
];

// ============================================
// DADOS DE PAGINAÇÃO (M1 — com defaults seguros)
// ============================================
$pag = $paginacao ?? [];
$paginaAtual    = $pag['paginaAtual']  ?? 1;
$totalPaginas   = $pag['totalPaginas'] ?? 1;
$totalRegistros = $pag['total']        ?? count($vendas ?? []);
$porPagina      = $pag['porPagina']    ?? 12;
$temAnterior    = $pag['temAnterior']  ?? false;
$temProxima     = $pag['temProxima']   ?? false;

// Cálculo do "Mostrando X–Y de Z"
$inicio = ($paginaAtual - 1) * $porPagina + 1;
$fim = min($paginaAtual * $porPagina, $totalRegistros);
if ($totalRegistros == 0) {
    $inicio = 0;
    $fim = 0;
}
?>

<!-- ============================================ -->
<!-- HEADER: Título + Botões                      -->
<!-- ============================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-cart-check text-primary"></i> Vendas
        </h2>
        <p class="text-muted mb-0">
            Gerencie suas vendas
            <?php if ($totalRegistros > 0): ?>
                — <strong><?= $totalRegistros ?></strong> venda<?= $totalRegistros !== 1 ? 's' : '' ?> encontrada<?= $totalRegistros !== 1 ? 's' : '' ?>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="<?= url('/vendas/relatorio') ?>" class="btn btn-outline-secondary me-2">
            <i class="bi bi-bar-chart"></i> Relatório
        </a>
        <a href="<?= url('/vendas/criar') ?>" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Nova Venda
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- CARDS DE RESUMO (estatísticas globais)       -->
<!-- ============================================ -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <h6 class="opacity-75 mb-1"><i class="bi bi-receipt"></i> Total de Vendas</h6>
                <h3 class="mb-0"><?= $estatisticas['total_vendas'] ?? 0 ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <h6 class="opacity-75 mb-1"><i class="bi bi-cash-stack"></i> Faturamento</h6>
                <h3 class="mb-0"><?= money($estatisticas['valor_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body py-3">
                <h6 class="opacity-75 mb-1"><i class="bi bi-graph-up-arrow"></i> Lucro Total</h6>
                <h3 class="mb-0"><?= money($estatisticas['lucro_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body py-3">
                <h6 class="opacity-75 mb-1"><i class="bi bi-tag"></i> Ticket Médio</h6>
                <h3 class="mb-0"><?= money($estatisticas['ticket_medio'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<?php if ($temDadosGrafico): ?>
<!-- [M6] Gráficos de Vendas — Collapse (padrão Artes M6) -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-bar-chart-line"></i> Gráficos de Vendas
        </h6>
        <button class="btn btn-sm btn-outline-secondary" type="button" 
                data-bs-toggle="collapse" data-bs-target="#collapseGraficos"
                aria-expanded="false" aria-controls="collapseGraficos">
            <i class="bi bi-chevron-down"></i> Mostrar/Ocultar
        </button>
    </div>
    <div class="collapse" id="collapseGraficos">
        <div class="card-body">
            <div class="row g-4">
                
                <!-- Gráfico 1: Faturamento Mensal (Barras Verticais) -->
                <?php if (!empty($vendasMensais)): ?>
                <div class="col-lg-7">
                    <h6 class="text-center text-muted mb-3">
                        <i class="bi bi-bar-chart"></i> Faturamento Mensal (últimos 6 meses)
                    </h6>
                    <!-- Container com altura fixa — evita loop de resize (lição Dashboard) -->
                    <div style="position: relative; height: 280px;">
                        <canvas id="graficoFaturamento"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Gráfico 2: Forma de Pagamento (Doughnut) -->
                <?php if (!empty($distribuicaoPgto)): ?>
                <div class="col-lg-5">
                    <h6 class="text-center text-muted mb-3">
                        <i class="bi bi-pie-chart"></i> Forma de Pagamento
                    </h6>
                    <!-- Container com altura fixa -->
                    <div style="position: relative; height: 220px;">
                        <canvas id="graficoPagamento"></canvas>
                    </div>
                    
                    <!-- Legenda manual (padrão Tags M6 — mais controle que Chart.js legend) -->
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                        <?php 
                        $totalGeral = array_sum(array_column($distribuicaoPgto, 'total'));
                        foreach ($distribuicaoPgto as $item): 
                            $fp = $item['forma_pagamento'] ?? 'outro';
                            $total = (int) ($item['total'] ?? 0);
                            $pct = $totalGeral > 0 ? round(($total / $totalGeral) * 100, 1) : 0;
                            $cor = $coresPgto[$fp] ?? '#6c757d';
                            $label = $labelsPgto[$fp] ?? ucfirst($fp);
                        ?>
                            <span class="badge bg-light text-dark border" style="font-size: 0.75rem;">
                                <span style="display:inline-block; width:10px; height:10px; background:<?= $cor ?>; border-radius:50%; margin-right:4px;"></span>
                                <?= $label ?>: <?= $total ?> (<?= $pct ?>%)
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- ============================================ -->
<!-- M3: FILTROS COMBINADOS                       -->
<!-- ============================================ -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/vendas') ?>" method="GET" class="row g-3 align-items-end">
            
            <!-- Filtro: Busca por termo (nome arte / observações) -->
            <div class="col-md-3">
                <label for="filtro-termo" class="form-label">
                    <i class="bi bi-search"></i> Buscar
                </label>
                <input type="text" 
                       id="filtro-termo"
                       name="termo" 
                       class="form-control" 
                       placeholder="Nome da arte ou observação..."
                       value="<?= e($filtros['termo'] ?? '') ?>">
            </div>
            
            <!-- Filtro: Cliente (select) -->
            <div class="col-md-2">
                <label for="filtro-cliente" class="form-label">
                    <i class="bi bi-person"></i> Cliente
                </label>
                <select id="filtro-cliente" name="cliente_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($clientesSelect ?? [] as $id => $nome): ?>
                        <option value="<?= $id ?>" <?= ($filtros['cliente_id'] ?? '') == $id ? 'selected' : '' ?>>
                            <?= e($nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro: Forma de Pagamento -->
            <div class="col-md-2">
                <label for="filtro-forma" class="form-label">
                    <i class="bi bi-credit-card"></i> Pagamento
                </label>
                <select id="filtro-forma" name="forma_pagamento" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($formasPagamento as $valor => $label): ?>
                        <option value="<?= $valor ?>" <?= ($filtros['forma_pagamento'] ?? '') === $valor ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro: Data Início -->
            <div class="col-md-2">
                <label for="filtro-data-inicio" class="form-label">
                    <i class="bi bi-calendar"></i> De
                </label>
                <input type="date" 
                       id="filtro-data-inicio"
                       name="data_inicio" 
                       class="form-control" 
                       value="<?= e($filtros['data_inicio'] ?? '') ?>">
            </div>
            
            <!-- Filtro: Data Fim -->
            <div class="col-md-2">
                <label for="filtro-data-fim" class="form-label">
                    <i class="bi bi-calendar-check"></i> Até
                </label>
                <input type="date" 
                       id="filtro-data-fim"
                       name="data_fim" 
                       class="form-control" 
                       value="<?= e($filtros['data_fim'] ?? '') ?>">
            </div>
            
            <!-- Botões: Filtrar + Limpar (btn-sm para caber em 1 coluna) -->
            <div class="col-md-1 d-flex gap-1 align-items-end">
                <button type="submit" class="btn btn-primary btn-sm px-2" title="Filtrar">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary btn-sm px-2" title="Limpar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- TABELA DE VENDAS (M2: headers clicáveis)     -->
<!-- ============================================ -->
<?php if (empty($vendas)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <?php if (!empty(array_filter($filtros ?? [], fn($v) => $v !== '' && $v !== null && $v !== 0))): ?>
            Nenhuma venda encontrada com os filtros aplicados.
            <a href="<?= url('/vendas') ?>" class="alert-link">Limpar filtros</a>
        <?php else: ?>
            Nenhuma venda registrada ainda. 
            <a href="<?= url('/vendas/criar') ?>" class="alert-link">Registrar primeira venda</a>
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Indicador: "Mostrando X–Y de Z vendas" -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted">
            Mostrando <strong><?= $inicio ?>–<?= $fim ?></strong> de <strong><?= $totalRegistros ?></strong> venda<?= $totalRegistros !== 1 ? 's' : '' ?>
        </small>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <!-- M2: Headers clicáveis com ícones de ordenação -->
                        <th>
                            <a href="<?= vendaSortUrl('data_venda', 'DESC') ?>" class="text-white text-decoration-none">
                                Data <?= vendaSortIcon('data_venda', 'down') ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= vendaSortUrl('arte_nome', 'ASC') ?>" class="text-white text-decoration-none">
                                Arte <?= vendaSortIcon('arte_nome', 'alpha') ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= vendaSortUrl('cliente_nome', 'ASC') ?>" class="text-white text-decoration-none">
                                Cliente <?= vendaSortIcon('cliente_nome', 'alpha') ?>
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="<?= vendaSortUrl('valor', 'DESC') ?>" class="text-white text-decoration-none">
                                Valor <?= vendaSortIcon('valor', 'numeric') ?>
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="<?= vendaSortUrl('lucro_calculado', 'DESC') ?>" class="text-white text-decoration-none">
                                Lucro <?= vendaSortIcon('lucro_calculado', 'numeric') ?>
                            </a>
                        </th>
                        <!-- COLUNA R$/h RESTAURADA do original -->
                        <th class="text-end">R$/h</th>
                        <th>
                            <a href="<?= vendaSortUrl('forma_pagamento', 'ASC') ?>" class="text-white text-decoration-none">
                                Pagamento <?= vendaSortIcon('forma_pagamento', 'alpha') ?>
                            </a>
                        </th>
                        <th class="text-center" style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): 
                        // allPaginated() retorna objetos Venda hydrated
                        // Mas mantemos compatibilidade com arrays por segurança
                        if (is_object($venda)) {
                            $vendaId         = $venda->getId();
                            $dataVenda       = $venda->getDataVenda();
                            $arteObj         = $venda->getArte();
                            $clienteObj      = $venda->getCliente();
                            $valor           = $venda->getValor() ?? 0;
                            $lucro           = $venda->getLucroCalculado() ?? 0;
                            $rentabilidade   = $venda->getRentabilidadeHora() ?? 0;
                            $forma           = $venda->getFormaPagamento() ?? 'outro';
                        } else {
                            // Fallback para arrays (compatibilidade com código legado)
                            $vendaId         = $venda['id'] ?? 0;
                            $dataVenda       = $venda['data_venda'] ?? '';
                            $arteObj         = null;
                            $clienteObj      = null;
                            $valor           = $venda['valor'] ?? 0;
                            $lucro           = $venda['lucro_calculado'] ?? 0;
                            $rentabilidade   = $venda['rentabilidade_hora'] ?? 0;
                            $forma           = $venda['forma_pagamento'] ?? 'outro';
                        }
                    ?>
                        <tr>
                            <!-- Data formatada dd/mm/aaaa -->
                            <td>
                                <?= $dataVenda ? date('d/m/Y', strtotime($dataVenda)) : '—' ?>
                            </td>
                            
                            <!-- Nome da arte (link para detalhes da arte) -->
                            <td>
                                <?php if (is_object($venda) && $arteObj): ?>
                                    <a href="<?= url('/artes/' . $arteObj->getId()) ?>" class="text-decoration-none">
                                        <?= e($arteObj->getNome()) ?>
                                    </a>
                                <?php elseif (!is_object($venda) && !empty($venda['arte_id'])): ?>
                                    <a href="<?= url('/artes/' . $venda['arte_id']) ?>">
                                        <?= e($venda['arte_nome'] ?? 'Arte #' . $venda['arte_id']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Arte removida</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Nome do cliente (link para detalhes do cliente) -->
                            <td>
                                <?php if (is_object($venda) && $clienteObj): ?>
                                    <a href="<?= url('/clientes/' . $clienteObj->getId()) ?>" class="text-decoration-none">
                                        <?= e($clienteObj->getNome()) ?>
                                    </a>
                                <?php elseif (!is_object($venda) && !empty($venda['cliente_id'])): ?>
                                    <a href="<?= url('/clientes/' . $venda['cliente_id']) ?>">
                                        <?= e($venda['cliente_nome'] ?? 'Cliente #' . $venda['cliente_id']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Valor da venda (formatado R$) -->
                            <td class="text-end fw-bold">
                                <?= money($valor) ?>
                            </td>
                            
                            <!-- Lucro (com cor condicional: verde se positivo, vermelho se negativo) -->
                            <td class="text-end <?= $lucro >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= money($lucro) ?>
                            </td>
                            
                            <!-- R$/h — COLUNA RESTAURADA do original -->
                            <td class="text-end text-info">
                                <?= money($rentabilidade) ?>/h
                            </td>
                            
                            <!-- Forma de pagamento (badge colorido) -->
                            <td>
                                <?php
                                $labelPgto = $formasPagamento[$forma] ?? ucfirst($forma);
                                $badgeClass = $badgeClasses[$forma] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $labelPgto ?></span>
                            </td>
                            
                            <!-- Ações: Ver, Editar, Excluir -->
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/vendas/' . $vendaId) ?>" 
                                       class="btn btn-outline-primary" title="Ver detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('/vendas/' . $vendaId . '/editar') ?>" 
                                       class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= url('/vendas/' . $vendaId) ?>" method="POST" 
                                          class="d-inline" 
                                          onsubmit="return confirm('Excluir esta venda? A arte voltará para disponível.')">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- M1: CONTROLES DE PAGINAÇÃO (Bootstrap 5)     -->
    <!-- ============================================ -->
    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Paginação de vendas" class="mt-4">
            <div class="d-flex justify-content-between align-items-center">
                
                <!-- Info: Página X de Y -->
                <small class="text-muted">
                    Página <strong><?= $paginaAtual ?></strong> de <strong><?= $totalPaginas ?></strong>
                </small>
                
                <!-- Controles de paginação -->
                <ul class="pagination mb-0">
                    
                    <!-- Botão: Primeira página -->
                    <li class="page-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= vendaUrl(['pagina' => 1]) ?>" title="Primeira página">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Botão: Página anterior -->
                    <li class="page-item <?= !$temAnterior ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= vendaUrl(['pagina' => $paginaAtual - 1]) ?>" title="Anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // ── Janela de 5 páginas centrada na página atual ──
                    // Ex: Se estiver na pág 7 de 20, mostra [5] [6] [7*] [8] [9]
                    $janela = 5;
                    $metade = floor($janela / 2); // 2
                    
                    // Calcula início e fim da janela
                    $inicioJanela = max(1, $paginaAtual - $metade);
                    $fimJanela = min($totalPaginas, $inicioJanela + $janela - 1);
                    
                    // Ajusta se ficou encostado no final
                    if ($fimJanela - $inicioJanela < $janela - 1) {
                        $inicioJanela = max(1, $fimJanela - $janela + 1);
                    }
                    
                    // Renderiza números das páginas
                    for ($i = $inicioJanela; $i <= $fimJanela; $i++):
                    ?>
                        <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">
                            <a class="page-link" href="<?= vendaUrl(['pagina' => $i]) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Botão: Próxima página -->
                    <li class="page-item <?= !$temProxima ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= vendaUrl(['pagina' => $paginaAtual + 1]) ?>" title="Próxima">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    
                    <!-- Botão: Última página -->
                    <li class="page-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= vendaUrl(['pagina' => $totalPaginas]) ?>" title="Última página">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

<?php endif; ?>

<?php if ($temDadosGrafico): ?>
<!-- [M6] CDN Chart.js 4.4.7 — carregado condicionalmente -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<script>
/**
 * [MELHORIA 6] Gráficos de Vendas — Chart.js
 * 
 * 1. Barras Verticais — Faturamento + Lucro mensal (últimos 6 meses)
 * 2. Doughnut — Distribuição por forma de pagamento
 * 
 * Padrões aplicados (Tags M6, Metas M3, Artes M6):
 * - maintainAspectRatio: false + container altura fixa → sem loop de resize
 * - Collapse com chart.resize() → recalcula após display:none → block
 * - Legend manual em HTML → mais controle visual
 */
document.addEventListener('DOMContentLoaded', function() {

    // ── Gráfico 1: Faturamento Mensal (Barras) ──
    <?php if (!empty($vendasMensais)): ?>
    const ctxFat = document.getElementById('graficoFaturamento');
    if (ctxFat) {
        new Chart(ctxFat, {
            type: 'bar',
            data: {
                // Formata labels: "2026-02" → "Fev/26"
                labels: [
                    <?php 
                    $mesesNomes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                    foreach ($vendasMensais as $vm): 
                        $partes = explode('-', $vm['mes'] ?? '');
                        $mesIdx = (int)($partes[1] ?? 1) - 1;
                        $ano2d = substr($partes[0] ?? '00', 2);
                        echo "'" . ($mesesNomes[$mesIdx] ?? '?') . '/' . $ano2d . "', ";
                    endforeach; 
                    ?>
                ],
                datasets: [
                    {
                        label: 'Faturamento (R$)',
                        data: [<?php echo implode(', ', array_map(fn($v) => (float)($v['total'] ?? 0), $vendasMensais)); ?>],
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',   // primary
                        borderColor: '#0d6efd',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 2   // Atrás do lucro
                    },
                    {
                        label: 'Lucro (R$)',
                        data: [<?php echo implode(', ', array_map(fn($v) => (float)($v['lucro'] ?? 0), $vendasMensais)); ?>],
                        backgroundColor: 'rgba(25, 135, 84, 0.7)',    // success
                        borderColor: '#198754',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 1   // Na frente
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,   // Altura controlada pelo container
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': R$ ' + 
                                    ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                return 'R$ ' + val.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // ── Gráfico 2: Forma de Pagamento (Doughnut) ──
    <?php if (!empty($distribuicaoPgto)): ?>
    const ctxPgto = document.getElementById('graficoPagamento');
    if (ctxPgto) {
        new Chart(ctxPgto, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($distribuicaoPgto as $item): 
                        $fp = $item['forma_pagamento'] ?? 'outro';
                        echo "'" . ($labelsPgto[$fp] ?? ucfirst($fp)) . "', ";
                    endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php echo implode(', ', array_map(fn($v) => (int)($v['total'] ?? 0), $distribuicaoPgto)); ?>
                    ],
                    backgroundColor: [
                        <?php foreach ($distribuicaoPgto as $item): 
                            $fp = $item['forma_pagamento'] ?? 'outro';
                            echo "'" . ($coresPgto[$fp] ?? '#6c757d') . "', ";
                        endforeach; ?>
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,   // Altura controlada pelo container
                cutout: '55%',                // Furo central do doughnut
                plugins: {
                    legend: { display: false }, // Legenda manual em HTML
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const valor = ctx.parsed;
                                const pct = total > 0 ? ((valor / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + valor + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // ── Collapse handler: resize gráficos após abrir ──
    // Chart.js não recalcula dimensões quando canvas está em display:none
    // Ao abrir o collapse, precisamos forçar resize
    const collapseEl = document.getElementById('collapseGraficos');
    if (collapseEl) {
        collapseEl.addEventListener('shown.bs.collapse', function() {
            // Força todos os charts a recalcularem tamanho
            Chart.helpers?.each(Chart.instances, function(chart) {
                chart.resize();
            });
        });
    }
});
</script>
<?php endif; ?>