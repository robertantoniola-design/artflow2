<?php
/**
 * ============================================
 * VIEW: Listagem de Artes (Melhoria 2 — Ordenação Dinâmica)
 * ============================================
 * 
 * GET /artes
 * GET /artes?pagina=2&status=disponivel&tag_id=3&termo=retrato
 * GET /artes?ordenar=nome&direcao=ASC&pagina=1
 * 
 * VARIÁVEIS DISPONÍVEIS (via extract no View::renderFile):
 * - $artes (array<Arte>)     — Artes da página atual
 * - $paginacao (array)        — Metadados de paginação (total, paginaAtual, etc.)
 * - $filtros (array)          — Filtros ativos: termo, status, tag_id, pagina, ordenar, direcao
 * - $tags (array<Tag>)        — Tags para dropdown de filtro
 * - $estatisticas (array)     — Contagem por status
 * 
 * MELHORIAS IMPLEMENTADAS:
 * - [Fase 1]     Status "reservada" no dropdown, labels e cores
 * - [Melhoria 1] Paginação com controles Bootstrap 5 (12 artes/página)
 * - [Melhoria 1] Preservação de filtros (status, tag_id, termo) ao paginar
 * - [Melhoria 1] Indicador "Mostrando X-Y de Z artes"
 * - [Melhoria 2] Ordenação clicável (6 colunas) com setas visuais ▲/▼
 * - [Melhoria 2] Toggle automático ASC↔DESC ao clicar na coluna ativa
 * - [Melhoria 2] Headers da tabela clicáveis com ícones de direção
 * 
 * PADRÃO: Segue o mesmo modelo do módulo Clientes (clienteUrl → arteUrl)
 * 
 * ARQUIVO: views/artes/index.php
 */
$currentPage = 'artes';

// ══════════════════════════════════════════════════════════════
// FUNÇÕES HELPER PARA URLs DE PAGINAÇÃO E ORDENAÇÃO
// ══════════════════════════════════════════════════════════════
// Recebem $filtros como parâmetro (não usam 'global')
// porque View::renderFile() faz extract($data) em escopo local.
// Padrão idêntico ao módulo Clientes (clienteUrl → arteUrl).

/**
 * Monta URL preservando TODOS os parâmetros atuais.
 * Permite trocar apenas um parâmetro sem perder os outros.
 * 
 * Exemplo: arteUrl($filtros, ['pagina' => 3])
 *   Se filtros = {termo: 'retrato', status: 'disponivel', ordenar: 'created_at', direcao: 'DESC'}
 *   Resultado: /artes?termo=retrato&status=disponivel&ordenar=created_at&direcao=DESC&pagina=3
 * 
 * @param array $filtros Filtros atuais vindos do controller
 * @param array $params  Parâmetros a sobrescrever
 * @return string URL completa
 */
function arteUrl(array $filtros, array $params = []): string {
    // Merge: parâmetros passados sobrescrevem os atuais
    // SEMPRE inclui ordenar/direcao para garantir consistência na navegação
    $merged = array_merge([
        'termo'   => $filtros['termo'] ?? '',
        'status'  => $filtros['status'] ?? '',
        'tag_id'  => $filtros['tag_id'] ?? '',
        'ordenar' => $filtros['ordenar'] ?? 'created_at',
        'direcao' => $filtros['direcao'] ?? 'DESC',
        'pagina'  => $filtros['pagina'] ?? 1,
    ], $params);
    
    // Monta query string, incluindo apenas valores não-vazios
    $query = [];
    
    // Termo: só inclui se não vazio
    if (!empty($merged['termo'])) {
        $query['termo'] = $merged['termo'];
    }
    
    // Status: só inclui se não vazio (valor "" = "Todos")
    if (!empty($merged['status'])) {
        $query['status'] = $merged['status'];
    }
    
    // Tag: só inclui se não vazio
    if (!empty($merged['tag_id'])) {
        $query['tag_id'] = $merged['tag_id'];
    }
    
    // Ordenação: SEMPRE inclui para preservar estado entre páginas
    // (Lição do módulo Clientes: sem isso, paginação perde a ordenação)
    $query['ordenar'] = $merged['ordenar'];
    $query['direcao'] = $merged['direcao'];
    
    // Página: só inclui se > 1 (página 1 é o default)
    if ((int)$merged['pagina'] > 1) {
        $query['pagina'] = (int)$merged['pagina'];
    }
    
    $qs = !empty($query) ? '?' . http_build_query($query) : '';
    return url('/artes') . $qs;
}

/**
 * [MELHORIA 2] Gera URL de ordenação com toggle automático de direção.
 * - Clicar na MESMA coluna: inverte ASC↔DESC
 * - Clicar em OUTRA coluna: usa direção padrão da coluna
 * - Sempre volta para página 1 ao mudar ordenação
 * 
 * Direções padrão por coluna:
 *   nome, complexidade, status → ASC (A→Z, baixa→alta)
 *   preco_custo, horas_trabalhadas, created_at → DESC (maior primeiro)
 * 
 * @param array  $filtros Filtros atuais
 * @param string $coluna  Coluna clicada (deve estar na whitelist do Repository)
 * @return string URL com nova ordenação
 */
function arteSortUrl(array $filtros, string $coluna): string {
    $ordenarAtual = $filtros['ordenar'] ?? 'created_at';
    $direcaoAtual = $filtros['direcao'] ?? 'DESC';
    
    if ($ordenarAtual === $coluna) {
        // Mesma coluna: inverte a direção (toggle ASC↔DESC)
        $novaDirecao = ($direcaoAtual === 'ASC') ? 'DESC' : 'ASC';
    } else {
        // Coluna diferente: usa direção padrão da coluna
        // Colunas numéricas/data começam DESC (maior primeiro)
        // Colunas de texto/enum começam ASC (A→Z)
        $colunasDesc = ['preco_custo', 'horas_trabalhadas', 'created_at'];
        $novaDirecao = in_array($coluna, $colunasDesc) ? 'DESC' : 'ASC';
    }
    
    return arteUrl($filtros, [
        'ordenar' => $coluna,
        'direcao' => $novaDirecao,
        'pagina'  => 1  // Volta para página 1 ao trocar ordenação
    ]);
}

/**
 * [MELHORIA 2] Retorna ícone HTML de seta para indicar direção de ordenação.
 * - Coluna ativa: seta colorida (azul) na direção atual
 * - Coluna inativa: seta cinza neutra (↕)
 * - Ícones específicos por tipo: alfa para texto, numérico para valores, calendário para data
 * 
 * @param array  $filtros Filtros atuais
 * @param string $coluna  Coluna a verificar
 * @return string HTML do ícone Bootstrap
 */
function arteSortIcon(array $filtros, string $coluna): string {
    $ordenarAtual = $filtros['ordenar'] ?? 'created_at';
    $direcaoAtual = $filtros['direcao'] ?? 'DESC';
    
    // Coluna inativa: seta cinza neutra (indica que é clicável)
    if ($ordenarAtual !== $coluna) {
        return '<i class="bi bi-arrow-down-up text-muted opacity-50"></i>';
    }
    
    // Coluna ativa: ícone específico por tipo de coluna
    // Colunas de texto: ícone alfabético (A↓Z / Z↓A)
    $colunasTexto = ['nome', 'complexidade', 'status'];
    
    if (in_array($coluna, $colunasTexto)) {
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
    } elseif ($coluna === 'created_at') {
        // Data: ícone genérico de ordenação
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-down' : 'bi-sort-up';
    } else {
        // Colunas numéricas (preco_custo, horas_trabalhadas): ícone numérico
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up';
    }
    
    return '<i class="bi ' . $icone . ' text-primary"></i>';
}

// ══════════════════════════════════════════════════════════════
// EXTRAÇÃO DE DADOS DE PAGINAÇÃO
// ══════════════════════════════════════════════════════════════
// Extraímos para variáveis locais por legibilidade na view

$total        = $paginacao['total'] ?? 0;
$porPagina    = $paginacao['porPagina'] ?? 12;
$paginaAtual  = $paginacao['paginaAtual'] ?? 1;
$totalPaginas = $paginacao['totalPaginas'] ?? 1;
$temAnterior  = $paginacao['temAnterior'] ?? false;
$temProxima   = $paginacao['temProxima'] ?? false;

// Cálculo de "Mostrando X-Y de Z"
$inicio = $total > 0 ? ($paginaAtual - 1) * $porPagina + 1 : 0;
$fim    = min($paginaAtual * $porPagina, $total);

// [MELHORIA 2] Extrai filtros de ordenação para uso nos botões/headers
$ordenarAtual = $filtros['ordenar'] ?? 'created_at';
$direcaoAtual = $filtros['direcao'] ?? 'DESC';

// Mapas de labels e cores para status (reusados na tabela)
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

// Mapas de labels e cores para complexidade
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

<!-- ═══════════════════════════════════════════════ -->
<!-- CARDS DE ESTATÍSTICAS POR STATUS               -->
<!-- ═══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <!-- Card: Disponíveis -->
    <div class="col-6 col-md-3">
        <div class="card border-success">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-success fs-4"><?= $estatisticas['disponivel'] ?? 0 ?></div>
                <small class="text-muted">Disponíveis</small>
            </div>
        </div>
    </div>
    <!-- Card: Em Produção -->
    <div class="col-6 col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-warning fs-4"><?= $estatisticas['em_producao'] ?? 0 ?></div>
                <small class="text-muted">Em Produção</small>
            </div>
        </div>
    </div>
    <!-- Card: Vendidas -->
    <div class="col-6 col-md-3">
        <div class="card border-info">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-info fs-4"><?= $estatisticas['vendida'] ?? 0 ?></div>
                <small class="text-muted">Vendidas</small>
            </div>
        </div>
    </div>
    <!-- Card: Reservadas (Fase 1) -->
    <div class="col-6 col-md-3">
        <div class="card border-secondary">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-secondary fs-4"><?= $estatisticas['reservada'] ?? 0 ?></div>
                <small class="text-muted">Reservadas</small>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- BARRA DE FILTROS + ORDENAÇÃO                              -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-body">
        <!-- 
            NOTA: O form usa GET para que os filtros fiquem na URL.
            Ao submeter, pagina é resetada para 1 automaticamente
            (não há input hidden para pagina no form).
            [MELHORIA 2] Campos hidden preservam ordenação durante busca.
        -->
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
            
            <!-- Filtro por status (4 opções + Todos) -->
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
            
            <!-- [MELHORIA 2] Preserva ordenação atual durante busca (campos hidden) -->
            <input type="hidden" name="ordenar" value="<?= e($ordenarAtual) ?>">
            <input type="hidden" name="direcao" value="<?= e($direcaoAtual) ?>">
        </form>
        
        <!-- Link "Limpar filtros" — só aparece se há filtros ativos -->
        <?php if (!empty($filtros['termo']) || !empty($filtros['status']) || !empty($filtros['tag_id'])): ?>
            <div class="mt-2">
                <a href="<?= url('/artes') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                </a>
            </div>
        <?php endif; ?>
        
        <!-- ══════════════════════════════════════════ -->
        <!-- [MELHORIA 2] Botões de ordenação          -->
        <!-- Toggle: clicar no ativo inverte ASC↔DESC   -->
        <!-- 6 colunas ordenáveis (whitelist backend)   -->
        <!-- ══════════════════════════════════════════ -->
        <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top">
            <span class="text-muted small me-1">
                <i class="bi bi-sort-down"></i> Ordenar:
            </span>
            
            <!-- Botão Nome (A-Z / Z-A) -->
            <a href="<?= arteSortUrl($filtros, 'nome') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'nome' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por nome">
                Nome <?= arteSortIcon($filtros, 'nome') ?>
            </a>
            
            <!-- Botão Complexidade (baixa→alta / alta→baixa) -->
            <a href="<?= arteSortUrl($filtros, 'complexidade') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'complexidade' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por complexidade">
                Complexidade <?= arteSortIcon($filtros, 'complexidade') ?>
            </a>
            
            <!-- Botão Custo (R$ maior/menor) -->
            <a href="<?= arteSortUrl($filtros, 'preco_custo') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'preco_custo' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por custo">
                Custo <?= arteSortIcon($filtros, 'preco_custo') ?>
            </a>
            
            <!-- Botão Horas (mais/menos horas) -->
            <a href="<?= arteSortUrl($filtros, 'horas_trabalhadas') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'horas_trabalhadas' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por horas trabalhadas">
                Horas <?= arteSortIcon($filtros, 'horas_trabalhadas') ?>
            </a>
            
            <!-- Botão Status -->
            <a href="<?= arteSortUrl($filtros, 'status') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'status' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Ordenar por status">
                Status <?= arteSortIcon($filtros, 'status') ?>
            </a>
            
            <!-- Botão Data (recentes/antigos) — DEFAULT -->
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
    <!-- Mensagem quando não há resultados -->
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
    
    <!-- [MELHORIA 1] Indicador de total: "Mostrando X-Y de Z artes" -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <small class="text-muted">
            Mostrando <?= $inicio ?>–<?= $fim ?> de <?= $total ?> arte<?= $total !== 1 ? 's' : '' ?>
        </small>
    </div>
    
    <!-- Tabela responsiva -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <!-- ══════════════════════════════════════════════ -->
                <!-- [MELHORIA 2] Headers clicáveis com setas ▲/▼  -->
                <!-- Cada header é um link que ordena por coluna    -->
                <!-- ══════════════════════════════════════════════ -->
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
                        <th class="width: 60px;">Imagem</th> <!-- [M4] Coluna thumbnail -->
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artes as $arte): ?>
                        <tr>
                            <!-- Nome (link para detalhes) -->
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
                            
                            <!-- Horas trabalhadas -->
                            <td><?= number_format($arte->getHorasTrabalhadas(), 1, ',', '.') ?>h</td>
                            
                            <!-- Status (com badge colorido) -->
                            <td>
                                <?php $st = $arte->getStatus(); ?>
                                <span class="badge bg-<?= $statusCores[$st] ?? 'secondary' ?>">
                                    <?= $statusLabels[$st] ?? ucfirst($st) ?>
                                </span>
                            </td>
                            
                            <!-- [MELHORIA 4] Thumbnail na listagem -->
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
    <!-- Só exibe se houver mais de 1 página -->
    <?php if ($totalPaginas > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <!-- Indicador de página atual -->
            <small class="text-muted">
                Página <?= $paginaAtual ?> de <?= $totalPaginas ?>
            </small>
            
            <!-- Controles de paginação Bootstrap 5 -->
            <nav aria-label="Paginação de artes">
                <ul class="pagination mb-0">
                    
                    <!-- Botão "Anterior" (desabilitado na primeira página) -->
                    <li class="page-item <?= !$temAnterior ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= $temAnterior ? arteUrl($filtros, ['pagina' => $paginaAtual - 1]) : '#' ?>"
                           <?= !$temAnterior ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // ══════════════════════════════════════════
                    // JANELA DE PÁGINAS (máx 5 números visíveis)
                    // ══════════════════════════════════════════
                    // Mostra até 5 páginas centradas na atual,
                    // com reticências (...) quando há páginas ocultas.
                    $janelaSize = 5;
                    $metade = floor($janelaSize / 2);
                    $janelaInicio = max(1, $paginaAtual - $metade);
                    $janelaFim = min($totalPaginas, $janelaInicio + $janelaSize - 1);
                    
                    // Ajusta início se o fim ficou limitado
                    if ($janelaFim - $janelaInicio < $janelaSize - 1) {
                        $janelaInicio = max(1, $janelaFim - $janelaSize + 1);
                    }
                    
                    // Reticências no início (se a janela não começa na página 1)
                    if ($janelaInicio > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => 1]) ?>">1</a>
                        </li>
                        <?php if ($janelaInicio > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">…</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Números de página na janela -->
                    <?php for ($p = $janelaInicio; $p <= $janelaFim; $p++): ?>
                        <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
                            <?php if ($p === $paginaAtual): ?>
                                <span class="page-link"><?= $p ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => $p]) ?>"><?= $p ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Reticências no final (se a janela não termina na última página) -->
                    <?php if ($janelaFim < $totalPaginas): ?>
                        <?php if ($janelaFim < $totalPaginas - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">…</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => $totalPaginas]) ?>"><?= $totalPaginas ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Botão "Próxima" (desabilitado na última página) -->
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