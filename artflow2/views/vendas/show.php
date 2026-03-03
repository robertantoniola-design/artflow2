<?php
/**
 * ============================================
 * VIEW: Detalhes da Venda — FASE 1 + MELHORIA 5
 * GET /vendas/{id}
 * ============================================
 * 
 * FASE 1 (22/02/2026): Exibição com arte/cliente hydrated (V9 fix)
 * 
 * MELHORIA 5 (24/02/2026): Cards de estatísticas derivadas
 * ── 4 mini-cards com métricas calculadas no Service:
 *    1. Margem de Lucro (%)
 *    2. Comparativo vs Ticket Médio
 *    3. Posição no Ranking de Rentabilidade
 *    4. R$/h vs Média Geral
 * 
 * CORREÇÃO: A view original referenciava $arte e $cliente como
 * variáveis separadas, mas o Controller só passa $venda (com
 * arte/cliente hydrated via getArte()/getCliente()). Agora
 * extraímos corretamente dos getters.
 * 
 * VARIÁVEIS RECEBIDAS DO CONTROLLER:
 * - $venda             : objeto Venda (com Arte e Cliente hydrated)
 * - $estatisticasVenda : array com métricas M5 (do Service)
 */
$currentPage = 'vendas';

// ── Extrai dados da venda para uso na view ──
$valor         = $venda->getValor() ?? 0;
$lucro         = $venda->getLucroCalculado() ?? 0;
$rentabilidade = $venda->getRentabilidadeHora() ?? 0;
$dataVenda     = $venda->getDataVenda();
$forma         = $venda->getFormaPagamento() ?? 'outro';

// ── CORREÇÃO: Extrai arte e cliente dos relacionamentos hydrated ──
// O Controller passa apenas $venda (com getArte()/getCliente() populados)
// A view original referenciava $arte/$cliente como variáveis separadas — BUG
$arte    = $venda->getArte();
$cliente = $venda->getCliente();

// ── M5: Extrai estatísticas com defaults seguros ──
$est = $estatisticasVenda ?? [];

// ── Labels e cores para forma de pagamento ──
$formasPagamento = [
    'dinheiro'       => 'Dinheiro',
    'pix'            => 'PIX',
    'cartao_credito' => 'Cartão de Crédito',
    'cartao_debito'  => 'Cartão de Débito',
    'transferencia'  => 'Transferência',
    'outro'          => 'Outro'
];
$badgeClasses = [
    'pix'            => 'bg-success',
    'dinheiro'       => 'bg-primary',
    'cartao_credito' => 'bg-warning text-dark',
    'cartao_debito'  => 'bg-info text-dark',
    'transferencia'  => 'bg-secondary',
    'outro'          => 'bg-dark'
];
?>

<!-- ============================================ -->
<!-- BREADCRUMB                                   -->
<!-- ============================================ -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/vendas') ?>">Vendas</a></li>
        <li class="breadcrumb-item active">Venda #<?= $venda->getId() ?></li>
    </ol>
</nav>

<!-- ============================================ -->
<!-- HEADER: Título + Botões de Ação              -->
<!-- ============================================ -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1">
            <i class="bi bi-cart-check text-primary"></i> Venda #<?= $venda->getId() ?>
        </h1>
        <p class="text-muted mb-0">
            <i class="bi bi-calendar3"></i> <?= $dataVenda ? date('d/m/Y', strtotime($dataVenda)) : '—' ?>
            <span class="mx-2">•</span>
            <?php
            $labelPgto = $formasPagamento[$forma] ?? ucfirst($forma);
            $badgeClass = $badgeClasses[$forma] ?? 'bg-secondary';
            ?>
            <span class="badge <?= $badgeClass ?>"><?= $labelPgto ?></span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/vendas/' . $venda->getId() . '/editar') ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- CARDS FINANCEIROS PRINCIPAIS (3 cards)       -->
<!-- ============================================ -->
<div class="row g-3 mb-4">
    <!-- Card: Valor da Venda -->
    <div class="col-md-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center py-3">
                <h6 class="text-white-50 mb-1"><i class="bi bi-cash-stack"></i> Valor da Venda</h6>
                <h2 class="mb-0"><?= money($valor) ?></h2>
            </div>
        </div>
    </div>
    <!-- Card: Lucro -->
    <div class="col-md-4">
        <div class="card bg-<?= $lucro >= 0 ? 'success' : 'danger' ?> text-white h-100">
            <div class="card-body text-center py-3">
                <h6 class="text-white-50 mb-1">
                    <i class="bi bi-<?= $lucro >= 0 ? 'graph-up-arrow' : 'graph-down-arrow' ?>"></i> 
                    <?= $lucro >= 0 ? 'Lucro' : 'Prejuízo' ?>
                </h6>
                <h2 class="mb-0"><?= money($lucro) ?></h2>
            </div>
        </div>
    </div>
    <!-- Card: Rentabilidade/Hora -->
    <div class="col-md-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center py-3">
                <h6 class="text-white-50 mb-1"><i class="bi bi-speedometer2"></i> Rentabilidade/Hora</h6>
                <h2 class="mb-0"><?= money($rentabilidade) ?>/h</h2>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- [M5] CARDS DE ESTATÍSTICAS DERIVADAS         -->
<!-- 4 mini-cards com border-start-4 colorido     -->
<!-- Padrão: Tags M5, Artes M5 (border-start)    -->
<!-- ============================================ -->
<?php if (!empty($est)): ?>
<div class="row g-3 mb-4">
    
    <!-- M5 Card 1: Margem de Lucro (%) -->
    <div class="col-md-3">
        <?php
        // Cores dinâmicas: verde ≥30%, amarelo ≥15%, vermelho <15%
        $margem = $est['margem_lucro'] ?? 0;
        $corMargem = $margem >= 30 ? 'success' : ($margem >= 15 ? 'warning' : 'danger');
        ?>
        <div class="card h-100 border-start border-start-4 border-<?= $corMargem ?>">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Margem de Lucro</small>
                        <span class="fs-4 fw-bold text-<?= $corMargem ?>">
                            <?= number_format($margem, 1, ',', '.') ?>%
                        </span>
                    </div>
                    <i class="bi bi-percent fs-3 text-<?= $corMargem ?> opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- M5 Card 2: vs Ticket Médio -->
    <div class="col-md-3">
        <?php
        $difTicket = $est['diferenca_ticket'] ?? 0;
        $corTicket = $difTicket >= 0 ? 'success' : 'danger';
        $iconTicket = $difTicket >= 0 ? 'arrow-up' : 'arrow-down';
        ?>
        <div class="card h-100 border-start border-start-4 border-<?= $corTicket ?>">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">vs Ticket Médio</small>
                        <span class="fs-4 fw-bold text-<?= $corTicket ?>">
                            <?= $difTicket >= 0 ? '+' : '' ?><?= number_format($difTicket, 1, ',', '.') ?>%
                        </span>
                        <small class="text-muted d-block">
                            Média: <?= money($est['ticket_medio_geral'] ?? 0) ?>
                        </small>
                    </div>
                    <i class="bi bi-<?= $iconTicket ?> fs-3 text-<?= $corTicket ?> opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- M5 Card 3: Posição no Ranking -->
    <div class="col-md-3">
        <?php
        $posicao = $est['posicao_ranking'] ?? 0;
        $totalRank = $est['total_com_ranking'] ?? 0;
        // Troféu dourado para top 3
        $corRank = ($posicao > 0 && $posicao <= 3) ? 'warning' : 'primary';
        $iconRank = ($posicao > 0 && $posicao <= 3) ? 'trophy' : 'bar-chart';
        ?>
        <div class="card h-100 border-start border-start-4 border-<?= $corRank ?>">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Ranking R$/h</small>
                        <?php if ($posicao > 0): ?>
                            <span class="fs-4 fw-bold text-<?= $corRank ?>">
                                <?= $posicao ?>° <small class="fw-normal">de <?= $totalRank ?></small>
                            </span>
                        <?php else: ?>
                            <span class="fs-5 text-muted">Sem ranking</span>
                        <?php endif; ?>
                    </div>
                    <i class="bi bi-<?= $iconRank ?> fs-3 text-<?= $corRank ?> opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- M5 Card 4: R$/h vs Média -->
    <div class="col-md-3">
        <?php
        $difRent = $est['diferenca_rent'] ?? 0;
        $corRent = $difRent >= 0 ? 'success' : 'danger';
        $iconRent = $difRent >= 0 ? 'arrow-up' : 'arrow-down';
        ?>
        <div class="card h-100 border-start border-start-4 border-<?= $corRent ?>">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">R$/h vs Média</small>
                        <?php if ($rentabilidade > 0): ?>
                            <span class="fs-4 fw-bold text-<?= $corRent ?>">
                                <?= $difRent >= 0 ? '+' : '' ?><?= number_format($difRent, 1, ',', '.') ?>%
                            </span>
                            <small class="text-muted d-block">
                                Média: <?= money($est['rentabilidade_media'] ?? 0) ?>/h
                            </small>
                        <?php else: ?>
                            <span class="fs-5 text-muted">N/A</span>
                        <?php endif; ?>
                    </div>
                    <i class="bi bi-<?= $iconRent ?> fs-3 text-<?= $corRent ?> opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- ============================================ -->
    <!-- COLUNA PRINCIPAL (8 colunas)                 -->
    <!-- ============================================ -->
    <div class="col-lg-8">
        
        <!-- Card: Arte Vendida -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-palette"></i> Arte Vendida
                </h5>
            </div>
            <div class="card-body">
                <?php if ($arte): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>
                                <a href="<?= url('/artes/' . $arte->getId()) ?>" class="text-decoration-none">
                                    <?= e($arte->getNome()) ?>
                                </a>
                            </h5>
                            <?php
                            // Badge de complexidade com cores semafóricas
                            $complexidade = $arte->getComplexidade();
                            $compClass = match($complexidade) {
                                'baixa' => 'success',
                                'media' => 'warning',
                                'alta'  => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $compClass ?>">
                                Complexidade: <?= ucfirst($complexidade ?? 'N/A') ?>
                            </span>
                            <span class="badge bg-dark ms-1">
                                Status: <?= ucfirst($arte->getStatus() ?? 'N/A') ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <!-- Tabela de métricas da arte (padrão Artes show.php) -->
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="text-muted">Custo de Produção:</th>
                                    <td class="text-end"><?= money($arte->getPrecoCusto()) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Horas Trabalhadas:</th>
                                    <td class="text-end"><?= number_format($arte->getHorasTrabalhadas() ?? 0, 1, ',', '.') ?>h</td>
                                </tr>
                                <?php if ($arte->getPrecoCusto() > 0): ?>
                                <tr>
                                    <th class="text-muted">Markup:</th>
                                    <td class="text-end">
                                        <?php 
                                        $markup = (($valor - $arte->getPrecoCusto()) / $arte->getPrecoCusto()) * 100;
                                        ?>
                                        <span class="text-<?= $markup >= 0 ? 'success' : 'danger' ?> fw-bold">
                                            <?= number_format($markup, 1, ',', '.') ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($arte->getHorasTrabalhadas() > 0): ?>
                                <tr>
                                    <th class="text-muted">Custo/Hora:</th>
                                    <td class="text-end">
                                        <?= money($arte->getPrecoCusto() / $arte->getHorasTrabalhadas()) ?>/h
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        <i class="bi bi-exclamation-circle"></i> 
                        Arte não disponível ou foi removida do sistema.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Card: Cliente -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person"></i> Cliente
                </h5>
            </div>
            <div class="card-body">
                <?php if ($cliente): ?>
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5>
                                <a href="<?= url('/clientes/' . $cliente->getId()) ?>" class="text-decoration-none">
                                    <?= e($cliente->getNome()) ?>
                                </a>
                            </h5>
                            <?php if (method_exists($cliente, 'getEmail') && $cliente->getEmail()): ?>
                                <p class="mb-1">
                                    <i class="bi bi-envelope"></i> 
                                    <a href="mailto:<?= e($cliente->getEmail()) ?>"><?= e($cliente->getEmail()) ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if (method_exists($cliente, 'getTelefone') && $cliente->getTelefone()): ?>
                                <p class="mb-0">
                                    <i class="bi bi-telephone"></i> 
                                    <a href="tel:<?= e($cliente->getTelefone()) ?>"><?= e($cliente->getTelefone()) ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                        <a href="<?= url('/clientes/' . $cliente->getId()) ?>" class="btn btn-outline-primary btn-sm">
                            Ver Perfil
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        <i class="bi bi-person-x"></i> Venda avulsa (sem cliente vinculado)
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Card: Observações (se houver) -->
        <?php if ($venda->getObservacoes()): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-chat-text"></i> Observações</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(e($venda->getObservacoes())) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================ -->
    <!-- SIDEBAR (4 colunas)                          -->
    <!-- ============================================ -->
    <div class="col-lg-4">
        
        <!-- Card: Resumo Financeiro -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-receipt"></i> Resumo Financeiro</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th>Data:</th>
                        <td class="text-end"><?= $dataVenda ? date('d/m/Y', strtotime($dataVenda)) : '—' ?></td>
                    </tr>
                    <tr>
                        <th>Valor:</th>
                        <td class="text-end fw-bold"><?= money($valor) ?></td>
                    </tr>
                    <?php if ($arte): ?>
                    <tr>
                        <th>Custo:</th>
                        <td class="text-end text-muted"><?= money($arte->getPrecoCusto()) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top">
                        <th>Lucro:</th>
                        <td class="text-end fw-bold text-<?= $lucro >= 0 ? 'success' : 'danger' ?>">
                            <?= money($lucro) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>R$/Hora:</th>
                        <td class="text-end text-info fw-bold"><?= money($rentabilidade) ?>/h</td>
                    </tr>
                    <tr>
                        <th>Pagamento:</th>
                        <td class="text-end">
                            <span class="badge <?= $badgeClasses[$forma] ?? 'bg-secondary' ?>">
                                <?= $formasPagamento[$forma] ?? ucfirst($forma) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Card: Indicadores de Performance -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Indicadores</h6>
            </div>
            <div class="card-body">
                <?php if ($arte && $arte->getHorasTrabalhadas() > 0): ?>
                    <?php $custoHora = $arte->getPrecoCusto() / $arte->getHorasTrabalhadas(); ?>
                    
                    <!-- Rentabilidade por Hora com barra de progresso -->
                    <div class="mb-3">
                        <small class="text-muted d-block">Rentabilidade por Hora</small>
                        <span class="fs-4 fw-bold text-<?= $rentabilidade >= 50 ? 'success' : ($rentabilidade >= 30 ? 'warning' : 'danger') ?>">
                            <?= money($rentabilidade) ?>/h
                        </span>
                        <div class="progress mt-1" style="height: 5px;">
                            <div class="progress-bar bg-<?= $rentabilidade >= 50 ? 'success' : ($rentabilidade >= 30 ? 'warning' : 'danger') ?>" 
                                 style="width: <?= min(($rentabilidade / 100) * 100, 100) ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Custo por Hora -->
                    <div class="mb-3">
                        <small class="text-muted d-block">Custo por Hora</small>
                        <span class="fs-5"><?= money($custoHora) ?>/h</span>
                    </div>
                    
                    <!-- Margem de Lucro (%) -->
                    <div>
                        <small class="text-muted d-block">Margem de Lucro</small>
                        <?php $margem = $valor > 0 ? (($lucro / $valor) * 100) : 0; ?>
                        <span class="fs-5 text-<?= $margem >= 30 ? 'success' : ($margem >= 15 ? 'warning' : 'danger') ?>">
                            <?= number_format($margem, 1, ',', '.') ?>%
                        </span>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0 small">
                        <i class="bi bi-info-circle"></i>
                        Indicadores não disponíveis (sem dados de horas da arte)
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Card: Informações do Sistema -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informações</h6>
            </div>
            <div class="card-body small">
                <p class="mb-1">
                    <strong>Registrada em:</strong><br>
                    <?= $venda->getCreatedAt() ? date('d/m/Y H:i', strtotime($venda->getCreatedAt())) : '—' ?>
                </p>
                <?php if ($venda->getUpdatedAt()): ?>
                <p class="mb-0">
                    <strong>Última alteração:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($venda->getUpdatedAt())) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card: Ações -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-gear"></i> Ações</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= url('/vendas/' . $venda->getId() . '/editar') ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i> Editar Venda
                    </a>
                    <!-- Excluir com confirmação -->
                    <form action="<?= url('/vendas/' . $venda->getId()) ?>" method="POST"
                          onsubmit="return confirm('Tem certeza que deseja excluir esta venda?\n\nA arte voltará para o status disponível e a meta do mês será recalculada.')">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-trash"></i> Excluir Venda
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>