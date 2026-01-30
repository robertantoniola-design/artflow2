<?php
/**
 * VIEW: Listagem de Vendas
 * GET /vendas
 * 
 * CORREÇÃO (29/01/2026):
 * - clientesSelect é [id => nome], usar $id => $nome
 * - vendas podem ser objetos ou arrays
 */
$currentPage = 'vendas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-cart-check text-primary"></i> Vendas
        </h2>
        <p class="text-muted mb-0">Gerencie suas vendas</p>
    </div>
    <a href="<?= url('/vendas/criar') ?>" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Nova Venda
    </a>
</div>

<!-- Cards de Resumo -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="opacity-75">Total de Vendas</h6>
                <h3 class="mb-0"><?= $resumo['total_vendas'] ?? count($vendas ?? []) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="opacity-75">Faturamento</h6>
                <h3 class="mb-0"><?= money($resumo['valor_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="opacity-75">Lucro Total</h6>
                <h3 class="mb-0"><?= money($resumo['lucro_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/vendas') ?>" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">Todos</option>
                    <?php 
                    // CORREÇÃO: clientesSelect é [id => nome]
                    foreach ($clientesSelect ?? [] as $id => $nome): 
                    ?>
                        <option value="<?= $id ?>" <?= ($filtros['cliente_id'] ?? '') == $id ? 'selected' : '' ?>>
                            <?= e($nome) ?>
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
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista -->
<?php if (empty($vendas)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cart display-4 text-muted"></i>
            <h5 class="mt-3">Nenhuma venda encontrada</h5>
            <a href="<?= url('/vendas/criar') ?>" class="btn btn-success mt-2">
                <i class="bi bi-plus-lg"></i> Registrar Venda
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Arte</th>
                        <th>Cliente</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Lucro</th>
                        <th class="text-end">R$/h</th>
                        <th width="100">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): 
                        // CORREÇÃO: Verifica se é objeto ou array
                        if (is_object($venda)) {
                            $vendaId = $venda->getId();
                            $dataVenda = $venda->getDataVenda();
                            $arteId = $venda->getArteId();
                            $arteNome = $venda->arte_nome ?? 'Arte #' . $arteId;
                            $clienteId = $venda->getClienteId();
                            $clienteNome = $venda->cliente_nome ?? ($clienteId ? 'Cliente #' . $clienteId : '-');
                            $valor = $venda->getValor();
                            $lucro = $venda->getLucroCalculado() ?? 0;
                            $rentabilidade = $venda->getRentabilidadeHora() ?? 0;
                        } else {
                            $vendaId = $venda['id'] ?? 0;
                            $dataVenda = $venda['data_venda'] ?? '';
                            $arteId = $venda['arte_id'] ?? 0;
                            $arteNome = $venda['arte_nome'] ?? 'Arte #' . $arteId;
                            $clienteId = $venda['cliente_id'] ?? null;
                            $clienteNome = $venda['cliente_nome'] ?? ($clienteId ? 'Cliente #' . $clienteId : '-');
                            $valor = $venda['valor'] ?? 0;
                            $lucro = $venda['lucro_calculado'] ?? 0;
                            $rentabilidade = $venda['rentabilidade_hora'] ?? 0;
                        }
                    ?>
                        <tr>
                            <td><?= date_br($dataVenda) ?></td>
                            <td>
                                <?php if ($arteId): ?>
                                    <a href="<?= url("/artes/{$arteId}") ?>"><?= e($arteNome) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($clienteId): ?>
                                    <a href="<?= url("/clientes/{$clienteId}") ?>"><?= e($clienteNome) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= money($valor) ?></td>
                            <td class="text-end <?= $lucro >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= money($lucro) ?>
                            </td>
                            <td class="text-end text-info"><?= money($rentabilidade) ?>/h</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url("/vendas/{$vendaId}") ?>" class="btn btn-outline-primary" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url("/vendas/{$vendaId}/editar") ?>" class="btn btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
