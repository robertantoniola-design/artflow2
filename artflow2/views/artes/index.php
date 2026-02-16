<?php
/**
 * ============================================
 * VIEW: Listagem de Artes (Melhoria 1 — Paginação)
 * ============================================
 * 
 * GET /artes
 * GET /artes?pagina=2&status=disponivel&tag_id=3&termo=retrato
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
 * 
 * PADRÃO: Segue o mesmo modelo do módulo Clientes (clienteUrl → arteUrl)
 * 
 * ARQUIVO: views/artes/index.php
 */
$currentPage = 'artes';

// ══════════════════════════════════════════════════════════════
// FUNÇÕES HELPER PARA URLs DE PAGINAÇÃO
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
    $query['ordenar'] = $merged['ordenar'];
    $query['direcao'] = $merged['direcao'];
    
    // Página: só inclui se > 1 (página 1 é o default)
    if ((int)$merged['pagina'] > 1) {
        $query['pagina'] = (int)$merged['pagina'];
    }
    
    $qs = !empty($query) ? '?' . http_build_query($query) : '';
    return url('/artes') . $qs;
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
$inicio = $total > 0 ? (($paginaAtual - 1) * $porPagina) + 1 : 0;
$fim    = min($paginaAtual * $porPagina, $total);

// ══════════════════════════════════════════════════════════════
// MAPA DE LABELS E CORES POR STATUS
// ══════════════════════════════════════════════════════════════
// Centralizado aqui para evitar repetição no HTML
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
?>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- HEADER DA PÁGINA                                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Gerencie suas obras de arte</p>
    </div>
    <a href="<?= url('/artes/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nova Arte
    </a>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- CARDS DE ESTATÍSTICAS POR STATUS                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-success">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-success fs-4"><?= $estatisticas['disponivel'] ?? 0 ?></div>
                <small class="text-muted">Disponíveis</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-warning fs-4"><?= $estatisticas['em_producao'] ?? 0 ?></div>
                <small class="text-muted">Em Produção</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-info">
            <div class="card-body text-center py-2">
                <div class="fw-bold text-info fs-4"><?= $estatisticas['vendida'] ?? 0 ?></div>
                <small class="text-muted">Vendidas</small>
            </div>
        </div>
    </div>
    <!-- Fase 1: Card de reservadas -->
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
<!-- BARRA DE FILTROS                                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-body">
        <!-- 
            NOTA: O form usa GET para que os filtros fiquem na URL.
            Ao submeter, pagina é resetada para 1 automaticamente
            (não há input hidden para pagina no form).
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
            
            <!-- Preserva ordenação ao filtrar -->
            <input type="hidden" name="ordenar" value="<?= e($filtros['ordenar'] ?? 'created_at') ?>">
            <input type="hidden" name="direcao" value="<?= e($filtros['direcao'] ?? 'DESC') ?>">
        </form>
        
        <!-- Link "Limpar filtros" — só aparece se há filtros ativos -->
        <?php if (!empty($filtros['termo']) || !empty($filtros['status']) || !empty($filtros['tag_id'])): ?>
            <div class="mt-2">
                <a href="<?= url('/artes') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                </a>
            </div>
        <?php endif; ?>
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
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Complexidade</th>
                        <th>Custo</th>
                        <th>Horas</th>
                        <th>Status</th>
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
                                <?php
                                    $complexLabels = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
                                    $complexCores  = ['baixa' => 'success', 'media' => 'warning', 'alta' => 'danger'];
                                    $comp = $arte->getComplexidade();
                                ?>
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
                    // ── Lógica de janela de páginas (mostra até 5 números) ──
                    // Ex: Para página 7 de 20: [5] [6] [7*] [8] [9]
                    $inicio_pag = max(1, $paginaAtual - 2);
                    $fim_pag    = min($totalPaginas, $paginaAtual + 2);
                    
                    // Ajusta janela se perto do início ou fim
                    if ($paginaAtual <= 2) {
                        $fim_pag = min($totalPaginas, 5);
                    }
                    if ($paginaAtual >= $totalPaginas - 1) {
                        $inicio_pag = max(1, $totalPaginas - 4);
                    }
                    ?>
                    
                    <!-- Primeira página + reticências (se janela não começa em 1) -->
                    <?php if ($inicio_pag > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => 1]) ?>">1</a>
                        </li>
                        <?php if ($inicio_pag > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Números de página (janela de até 5) -->
                    <?php for ($i = $inicio_pag; $i <= $fim_pag; $i++): ?>
                        <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">
                            <?php if ($i === $paginaAtual): ?>
                                <span class="page-link"><?= $i ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= arteUrl($filtros, ['pagina' => $i]) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Reticências + última página (se janela não termina no final) -->
                    <?php if ($fim_pag < $totalPaginas): ?>
                        <?php if ($fim_pag < $totalPaginas - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
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