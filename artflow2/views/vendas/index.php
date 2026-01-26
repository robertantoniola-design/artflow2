<?php
/**
 * VIEW: Listagem de Vendas
 * GET /vendas
 */
$currentPage = 'vendas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-cart3 text-primary"></i> Vendas
        </h2>
        <p class="text-muted mb-0">Gerencie suas vendas e faturamento</p>
    </div>
    <a href="<?= url('/vendas/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Venda
    </a>
</div>

<!-- Cards de Resumo -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-white-50">Faturamento Mês</h6>
                        <h3 class="mb-0"><?= money($resumo['faturamento_mes'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-white-50">Vendas no Mês</h6>
                        <h3 class="mb-0"><?= $resumo['vendas_mes'] ?? 0 ?></h3>
                    </div>
                    <i class="bi bi-bag-check display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-white-50">Ticket Médio</h6>
                        <h3 class="mb-0"><?= money($resumo['ticket_medio'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-graph-up display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-dark opacity-75">Lucro Total</h6>
                        <h3 class="mb-0"><?= money($resumo['lucro_total'] ?? 0) ?></h3>
                    </div>
                    <i class="bi bi-piggy-bank display-6 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/vendas') ?>" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Período</label>
                <select name="mes_ano" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($mesesDisponiveis ?? [] as $mes): ?>
                        <option value="<?= $mes['valor'] ?>" <?= ($filtros['mes_ano'] ?? '') === $mes['valor'] ? 'selected' : '' ?>>
                            <?= $mes['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($clientesSelect ?? [] as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filtros['cliente_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data Inicial</label>
                <input type="date" name="data_inicio" class="form-control" value="<?= $filtros['data_inicio'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Data Final</label>
                <input type="date" name="data_fim" class="form-control" value="<?= $filtros['data_fim'] ?? '' ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Limpar
                </a>
                <a href="<?= url('/vendas/relatorio') ?>" class="btn btn-outline-info float-end">
                    <i class="bi bi-graph-up"></i> Ver Relatórios
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Vendas -->
<?php if (empty($vendas)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cart display-4 text-muted"></i>
            <h5 class="mt-3">Nenhuma venda encontrada</h5>
            <p class="text-muted">Registre sua primeira venda.</p>
            <a href="<?= url('/vendas/criar') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Registrar Venda
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header bg-transparent">
            <span class="text-muted"><?= count($vendas) ?> venda(s) encontrada(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Arte</th>
                        <th>Cliente</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Lucro</th>
                        <th class="text-end">R$/hora</th>
                        <th width="100">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?= date_br($venda->getDataVenda()) ?></td>
                            <td>
                                <?php if ($venda->getArteId()): ?>
                                    <a href="<?= url("/artes/{$venda->getArteId()}") ?>" class="text-decoration-none">
                                        <?= e($venda->arte_nome ?? 'Arte #' . $venda->getArteId()) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($venda->getClienteId()): ?>
                                    <a href="<?= url("/clientes/{$venda->getClienteId()}") ?>" class="text-decoration-none">
                                        <?= e($venda->cliente_nome ?? 'Cliente #' . $venda->getClienteId()) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-medium"><?= money($venda->getValor()) ?></td>
                            <td class="text-end <?= $venda->getLucroCalculado() >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= money($venda->getLucroCalculado()) ?>
                            </td>
                            <td class="text-end">
                                <?php if ($venda->getRentabilidadeHora() > 0): ?>
                                    <span class="badge bg-<?= $venda->getRentabilidadeHora() > 50 ? 'success' : 'secondary' ?>">
                                        <?= money($venda->getRentabilidadeHora()) ?>/h
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url("/vendas/{$venda->getId()}") ?>" 
                                       class="btn btn-outline-info" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            onclick="confirmarExclusao(<?= $venda->getId() ?>)"
                                            title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta venda?</p>
                <p class="text-danger small">
                    <i class="bi bi-exclamation-triangle"></i>
                    A meta do mês será recalculada automaticamente.
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
function confirmarExclusao(id) {
    document.getElementById('formExcluir').action = '/vendas/' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>
