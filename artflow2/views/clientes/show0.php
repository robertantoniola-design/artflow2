<?php
/**
 * VIEW: Detalhes do Cliente
 * GET /clientes/{id}
 */
$currentPage = 'clientes';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-person text-primary"></i> <?= e($cliente->getNome()) ?>
        </h2>
        <?php if ($cliente->getEmpresa()): ?>
            <p class="text-muted mb-0">
                <i class="bi bi-building"></i> <?= e($cliente->getEmpresa()) ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url("/clientes/{$cliente->getId()}/editar") ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="row">
    <!-- Informações do Cliente -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Informações</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="text-muted">Email</dt>
                    <dd>
                        <?php if ($cliente->getEmail()): ?>
                            <a href="mailto:<?= e($cliente->getEmail()) ?>">
                                <?= e($cliente->getEmail()) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Não informado</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="text-muted mt-3">Telefone</dt>
                    <dd>
                        <?php if ($cliente->getTelefone()): ?>
                            <a href="tel:<?= e($cliente->getTelefone()) ?>">
                                <?= e($cliente->getTelefoneFormatado()) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Não informado</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="text-muted mt-3">Empresa</dt>
                    <dd>
                        <?= e($cliente->getEmpresa()) ?: '<span class="text-muted">Não informada</span>' ?>
                    </dd>
                    
                    <dt class="text-muted mt-3">Cliente desde</dt>
                    <dd><?= date_br($cliente->getCreatedAt()) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    
    <!-- Estatísticas -->
    <div class="col-lg-8 mb-4">
        <div class="row g-3 mb-4">
            <!-- Total de Compras -->
            <div class="col-md-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-cart-check display-6"></i>
                        <h3 class="mt-2 mb-0"><?= $estatisticas['total_compras'] ?? 0 ?></h3>
                        <small>Compras</small>
                    </div>
                </div>
            </div>
            
            <!-- Valor Total -->
            <div class="col-md-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-currency-dollar display-6"></i>
                        <h3 class="mt-2 mb-0"><?= money($estatisticas['valor_total'] ?? 0) ?></h3>
                        <small>Total Gasto</small>
                    </div>
                </div>
            </div>
            
            <!-- Ticket Médio -->
            <div class="col-md-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-graph-up display-6"></i>
                        <h3 class="mt-2 mb-0"><?= money($estatisticas['ticket_medio'] ?? 0) ?></h3>
                        <small>Ticket Médio</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Histórico de Compras -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Histórico de Compras</h5>
                <a href="<?= url('/vendas/criar') ?>?cliente_id=<?= $cliente->getId() ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Nova Venda
                </a>
            </div>
            <?php if (empty($vendas)): ?>
                <div class="card-body text-center py-5">
                    <i class="bi bi-bag display-4 text-muted"></i>
                    <p class="text-muted mt-3">Nenhuma compra registrada</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Arte</th>
                                <th>Valor</th>
                                <th width="80">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendas as $venda): ?>
                                <tr>
                                    <td><?= date_br($venda->getDataVenda()) ?></td>
                                    <td>
                                        <?php if ($venda->getArteId()): ?>
                                            <a href="<?= url("/artes/{$venda->getArteId()}") ?>">
                                                <?= e($venda->arte_nome ?? 'Arte #' . $venda->getArteId()) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-success fw-medium">
                                        <?= money($venda->getValor()) ?>
                                    </td>
                                    <td>
                                        <a href="<?= url("/vendas/{$venda->getId()}") ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="Ver detalhes">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
