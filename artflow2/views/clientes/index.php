<?php
/**
 * ============================================
 * VIEW: Listagem de Clientes (Melhoria 2 — Ordenação Dinâmica)
 * ============================================
 * 
 * GET /clientes
 * GET /clientes?pagina=2&ordenar=cidade&direcao=ASC&termo=João
 * 
 * VARIÁVEIS DISPONÍVEIS (via extract no View::renderFile):
 * - $clientes (array<Cliente>)    — Clientes da página atual
 * - $paginacao (array)            — Metadados de paginação
 * - $filtros (array)              — Filtros ativos: termo, pagina, ordenar, direcao
 * - $total (int)                  — Total de registros
 * 
 * MELHORIAS IMPLEMENTADAS:
 * - [Melhoria 1] Paginação com controles Bootstrap 5 (12 clientes/página)
 * - [Melhoria 2] Ordenação clicável (Nome, Data, Cidade) com setas visuais
 * - [Melhoria 3] Campos UI expandidos (localização nos cards)
 * 
 * PADRÃO: Segue o mesmo modelo do módulo Tags (tagUrl → clienteUrl, etc.)
 * DIFERENÇA: Clientes usa 'pagina' como param (Tags usa 'page')
 * 
 * ARQUIVO: views/clientes/index.php
 */
$currentPage = 'clientes';

// ══════════════════════════════════════════════════════════════
// FUNÇÕES HELPER PARA URLs DE PAGINAÇÃO E ORDENAÇÃO
// ══════════════════════════════════════════════════════════════
// Recebem $filtros como parâmetro (não usam 'global')
// porque View::renderFile() faz extract($data) em escopo local.
// Padrão idêntico ao módulo Tags (tagUrl → clienteUrl).

/**
 * Monta URL preservando TODOS os parâmetros atuais.
 * Permite trocar apenas um parâmetro sem perder os outros.
 * 
 * Exemplo: clienteUrl($filtros, ['pagina' => 3])
 *   Se filtros = {termo: 'João', ordenar: 'nome', direcao: 'ASC'}
 *   Resultado: /clientes?termo=João&ordenar=nome&direcao=ASC&pagina=3
 * 
 * @param array $filtros Filtros atuais vindos do controller
 * @param array $params  Parâmetros a sobrescrever
 * @return string URL completa
 */
function clienteUrl(array $filtros, array $params = []): string {
    // Merge: parâmetros passados sobrescrevem os atuais
    // SEMPRE inclui ordenar/direcao para garantir consistência na navegação
    $merged = array_merge([
        'termo'   => $filtros['termo'] ?? '',
        'ordenar' => $filtros['ordenar'] ?? 'nome',
        'direcao' => $filtros['direcao'] ?? 'ASC',
        'pagina'  => $filtros['pagina'] ?? 1,
    ], $params);
    
    // Monta query string SEMPRE com ordenar e direcao (evita perda de filtros)
    $query = [];
    
    // Termo: só inclui se não vazio
    if (!empty($merged['termo'])) {
        $query['termo'] = $merged['termo'];
    }
    
    // Ordenação: SEMPRE inclui para preservar estado entre páginas
    $query['ordenar'] = $merged['ordenar'];
    $query['direcao'] = $merged['direcao'];
    
    // Página: só inclui se > 1 (página 1 é o default)
    if ((int)$merged['pagina'] > 1) {
        $query['pagina'] = (int)$merged['pagina'];
    }
    
    $qs = !empty($query) ? '?' . http_build_query($query) : '';
    return url('/clientes') . $qs;
}

/**
 * Gera URL de ordenação com toggle automático de direção.
 * - Clicar na MESMA coluna: inverte ASC↔DESC
 * - Clicar em OUTRA coluna: começa com ASC (exceto created_at → DESC)
 * - Sempre volta para página 1 ao mudar ordenação
 * 
 * @param array  $filtros Filtros atuais
 * @param string $coluna  Coluna clicada: 'nome'|'created_at'|'cidade'
 * @return string URL com nova ordenação
 */
function clienteSortUrl(array $filtros, string $coluna): string {
    $ordenarAtual  = $filtros['ordenar'] ?? 'nome';
    $direcaoAtual  = $filtros['direcao'] ?? 'ASC';
    
    // Se já está ordenando por esta coluna, inverte a direção
    if ($ordenarAtual === $coluna) {
        $novaDirecao = ($direcaoAtual === 'ASC') ? 'DESC' : 'ASC';
    } else {
        // Coluna diferente: começa com ASC (exceto data, que faz mais sentido DESC = recentes primeiro)
        $novaDirecao = ($coluna === 'created_at') ? 'DESC' : 'ASC';
    }
    
    return clienteUrl($filtros, [
        'ordenar' => $coluna,
        'direcao' => $novaDirecao,
        'pagina'  => 1  // Volta para página 1 ao trocar ordenação
    ]);
}

/**
 * Retorna ícone HTML de seta para indicar direção de ordenação.
 * - Coluna ativa: seta colorida na direção atual
 * - Coluna inativa: seta cinza neutra (↕)
 * - Ícones específicos: alfa para nome/cidade, calendário para data
 * 
 * @param array  $filtros Filtros atuais
 * @param string $coluna  Coluna a verificar
 * @return string HTML do ícone Bootstrap
 */
function clienteSortIcon(array $filtros, string $coluna): string {
    $ordenarAtual = $filtros['ordenar'] ?? 'nome';
    $direcaoAtual = $filtros['direcao'] ?? 'ASC';
    
    // Coluna inativa: seta cinza neutra
    if ($ordenarAtual !== $coluna) {
        return '<i class="bi bi-arrow-down-up text-muted opacity-50"></i>';
    }
    
    // Coluna ativa: ícone específico por tipo de coluna
    if ($coluna === 'created_at') {
        // Data: seta genérica (mais recente ↓ ou mais antigo ↑)
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-down' : 'bi-sort-up';
    } else {
        // Nome e Cidade: seta alfabética
        $icone = ($direcaoAtual === 'ASC') ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
    }
    
    return '<i class="bi ' . $icone . ' text-primary"></i>';
}

// ── Extrai dados de paginação para uso no template ──
// Compatibilidade: se não houver paginação, cria estrutura padrão
$paginacao = $paginacao ?? [
    'total'        => $total ?? count($clientes ?? []),
    'porPagina'    => 12,
    'paginaAtual'  => 1,
    'totalPaginas' => 1,
    'temAnterior'  => false,
    'temProxima'   => false
];

// Extrai filtros com valores padrão seguros
$ordenarAtual = $filtros['ordenar'] ?? 'nome';
$direcaoAtual = $filtros['direcao'] ?? 'ASC';
$termoAtual   = $filtros['termo'] ?? '';
?>

<!-- ═══════════════════════════════════════════════ -->
<!-- HEADER: Título + Botão Novo Cliente            -->
<!-- ═══════════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-people text-primary"></i> Clientes
        </h2>
        <p class="text-muted mb-0">
            <?php if ($paginacao['total'] > 0): ?>
                <?= $paginacao['total'] ?> cliente<?= $paginacao['total'] > 1 ? 's' : '' ?> cadastrado<?= $paginacao['total'] > 1 ? 's' : '' ?>
            <?php else: ?>
                Gerencie sua base de clientes
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= url('/clientes/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Novo Cliente
    </a>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- BUSCA + CONTROLES DE ORDENAÇÃO (MELHORIA 2)    -->
<!-- ═══════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-body">
        <!-- Linha 1: Campo de busca -->
        <form action="<?= url('/clientes') ?>" method="GET" class="row g-3 mb-3">
            <!-- Preserva ordenação atual durante busca (campos hidden) -->
            <input type="hidden" name="ordenar" value="<?= e($ordenarAtual) ?>">
            <input type="hidden" name="direcao" value="<?= e($direcaoAtual) ?>">
            
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           name="termo" 
                           class="form-control" 
                           placeholder="Buscar por nome, email, telefone ou cidade..."
                           value="<?= e($termoAtual) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <?php if (!empty($termoAtual)): ?>
                        <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary" title="Limpar busca">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        
        <!-- ══════════════════════════════════════════ -->
        <!-- MELHORIA 2: Botões de ordenação           -->
        <!-- Linha 2: Botões clicáveis com setas ▲/▼   -->
        <!-- Toggle: clicar no ativo inverte ASC↔DESC   -->
        <!-- ══════════════════════════════════════════ -->
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small me-1">
                <i class="bi bi-sort-down"></i> Ordenar:
            </span>
            
            <!-- Botão Nome (A-Z / Z-A) -->
            <a href="<?= clienteSortUrl($filtros, 'nome') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'nome' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-person"></i> Nome
                <?= clienteSortIcon($filtros, 'nome') ?>
            </a>
            
            <!-- Botão Data (Recentes / Antigos) -->
            <a href="<?= clienteSortUrl($filtros, 'created_at') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'created_at' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar"></i> Data
                <?= clienteSortIcon($filtros, 'created_at') ?>
            </a>
            
            <!-- Botão Cidade (A-Z / Z-A) -->
            <a href="<?= clienteSortUrl($filtros, 'cidade') ?>" 
               class="btn btn-sm <?= $ordenarAtual === 'cidade' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-geo-alt"></i> Cidade
                <?= clienteSortIcon($filtros, 'cidade') ?>
            </a>
        </div>
    </div>
</div>

<!-- Resultado da busca (info contextual) -->
<?php if (!empty($termoAtual)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <?= $paginacao['total'] ?> resultado(s) para "<strong><?= e($termoAtual) ?></strong>"
        <a href="<?= clienteUrl($filtros, ['termo' => '']) ?>" class="float-end">
            <i class="bi bi-x-circle"></i> Limpar busca
        </a>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- GRID DE CARDS DE CLIENTES                      -->
<!-- ═══════════════════════════════════════════════ -->
<?php if (empty($clientes)): ?>
    <!-- Estado vazio -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-people display-4 text-muted"></i>
            <h5 class="mt-3">Nenhum cliente encontrado</h5>
            <p class="text-muted">
                <?php if (!empty($termoAtual)): ?>
                    Não encontramos clientes com o termo "<?= e($termoAtual) ?>".
                    <br>
                    <a href="<?= url('/clientes') ?>" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="bi bi-arrow-left"></i> Ver todos os clientes
                    </a>
                <?php else: ?>
                    Comece cadastrando seu primeiro cliente.
                    <br>
                    <a href="<?= url('/clientes/criar') ?>" class="btn btn-primary btn-sm mt-2">
                        <i class="bi bi-plus-lg"></i> Novo Cliente
                    </a>
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <!-- Grid responsivo: 1 col mobile, 2 cols md, 3 cols lg -->
    <div class="row g-3">
        <?php foreach ($clientes as $cliente): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 hover-shadow">
                    <div class="card-body">
                        <!-- Nome + Empresa -->
                        <h5 class="card-title mb-1">
                            <a href="<?= url("/clientes/{$cliente->getId()}") ?>" class="text-decoration-none">
                                <?= e($cliente->getNome()) ?>
                            </a>
                        </h5>
                        <?php if ($cliente->getEmpresa()): ?>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-building"></i> <?= e($cliente->getEmpresa()) ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Contato -->
                        <?php if ($cliente->getEmail()): ?>
                            <p class="mb-1 small">
                                <i class="bi bi-envelope text-muted"></i> <?= e($cliente->getEmail()) ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($cliente->getTelefone()): ?>
                            <p class="mb-1 small">
                                <i class="bi bi-telephone text-muted"></i> <?= e($cliente->getTelefone()) ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- MELHORIA 3: Localização (Cidade/UF) -->
                        <?php 
                        $cidade = $cliente->getCidade();
                        $estado = $cliente->getEstado();
                        if ($cidade || $estado): 
                        ?>
                            <p class="mb-1 small">
                                <i class="bi bi-geo-alt text-muted"></i> 
                                <?= e(implode('/', array_filter([$cidade, $estado]))) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Footer: Ações -->
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Data de cadastro -->
                            <small class="text-muted">
                                <i class="bi bi-calendar-plus"></i>
                                <?= date('d/m/Y', strtotime($cliente->getCreatedAt())) ?>
                            </small>
                            
                            <!-- Botões de ação -->
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url("/clientes/{$cliente->getId()}") ?>" 
                                   class="btn btn-outline-secondary"
                                   title="Ver detalhes">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url("/clientes/{$cliente->getId()}/editar") ?>" 
                                   class="btn btn-outline-primary"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-outline-danger"
                                        title="Excluir"
                                        onclick="confirmarExclusao(<?= $cliente->getId() ?>, '<?= e(addslashes($cliente->getNome())) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- ══════════════════════════════════════════════ -->
    <!-- PAGINAÇÃO (só exibe se houver mais de 1 página) -->
    <!-- MELHORIA 2: Agora usa clienteUrl() para preservar ordenação -->
    <!-- ══════════════════════════════════════════════ -->
    <?php if ($paginacao['totalPaginas'] > 1): ?>
        <nav aria-label="Navegação de páginas" class="mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Info da Paginação -->
                <small class="text-muted">
                    Página <?= $paginacao['paginaAtual'] ?> de <?= $paginacao['totalPaginas'] ?>
                    (<?= $paginacao['total'] ?> clientes)
                </small>
                
                <!-- Controles de Paginação -->
                <ul class="pagination pagination-sm mb-0">
                    <!-- Primeira Página -->
                    <li class="page-item <?= !$paginacao['temAnterior'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= clienteUrl($filtros, ['pagina' => 1]) ?>"
                           title="Primeira página">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Página Anterior -->
                    <li class="page-item <?= !$paginacao['temAnterior'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= clienteUrl($filtros, ['pagina' => $paginacao['paginaAtual'] - 1]) ?>"
                           title="Página anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <!-- Números de Página (até 5 visíveis) -->
                    <?php
                    // Calcula range de páginas visíveis (janela de 5)
                    $inicio = max(1, $paginacao['paginaAtual'] - 2);
                    $fim = min($paginacao['totalPaginas'], $inicio + 4);
                    // Ajusta início se estiver perto do final
                    if ($fim - $inicio < 4) {
                        $inicio = max(1, $fim - 4);
                    }
                    
                    for ($i = $inicio; $i <= $fim; $i++):
                    ?>
                        <li class="page-item <?= $i === $paginacao['paginaAtual'] ? 'active' : '' ?>">
                            <a class="page-link" 
                               href="<?= clienteUrl($filtros, ['pagina' => $i]) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Próxima Página -->
                    <li class="page-item <?= !$paginacao['temProxima'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= clienteUrl($filtros, ['pagina' => $paginacao['paginaAtual'] + 1]) ?>"
                           title="Próxima página">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    
                    <!-- Última Página -->
                    <li class="page-item <?= !$paginacao['temProxima'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= clienteUrl($filtros, ['pagina' => $paginacao['totalPaginas']]) ?>"
                           title="Última página">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- MODAL: Confirmação de Exclusão                 -->
<!-- ═══════════════════════════════════════════════ -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o cliente <strong id="nomeClienteExcluir"></strong>?</p>
                <p class="text-danger small">
                    <i class="bi bi-exclamation-triangle"></i>
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST" class="d-inline">
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

<!-- Script para Modal de Exclusão -->
<script>
/**
 * Abre modal de confirmação de exclusão.
 * @param {number} id   - ID do cliente
 * @param {string} nome - Nome do cliente (exibido no modal)
 */
function confirmarExclusao(id, nome) {
    document.getElementById('nomeClienteExcluir').textContent = nome;
    document.getElementById('formExcluir').action = '<?= url('/clientes/') ?>' + id;
    
    var modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
    modal.show();
}
</script>

<style>
/* Efeito hover nos cards */
.hover-shadow {
    transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}
</style>