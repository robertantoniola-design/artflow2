<?php
/**
 * View: Detalhes da Venda
 * Exibe informações completas de uma venda específica
 */

// Variáveis: $venda, $arte (se existir), $cliente (se existir)
$valor = $venda->getValor();
$lucro = $venda->getLucroCalculado() ?? 0;
$rentabilidade = $venda->getRentabilidadeHora() ?? 0;
$dataVenda = $venda->getDataVenda();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/vendas">Vendas</a></li>
        <li class="breadcrumb-item active">Venda #<?= $venda->getId() ?></li>
    </ol>
</nav>

<!-- Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1">Venda #<?= $venda->getId() ?></h1>
        <p class="text-muted mb-0">
            <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($dataVenda)) ?>
        </p>
    </div>
    <a href="/vendas" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row">
    <!-- Coluna Principal -->
    <div class="col-lg-8">
        <!-- Resumo Financeiro -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white-50 mb-1">Valor da Venda</h6>
                        <h2 class="mb-0">R$ <?= number_format($valor, 2, ',', '.') ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white-50 mb-1">Lucro</h6>
                        <h2 class="mb-0">R$ <?= number_format($lucro, 2, ',', '.') ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white-50 mb-1">Rentabilidade/Hora</h6>
                        <h2 class="mb-0">R$ <?= number_format($rentabilidade, 2, ',', '.') ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detalhes da Arte -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-palette"></i> Arte Vendida
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($arte) && $arte): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>
                                <a href="/artes/<?= $arte->getId() ?>" class="text-decoration-none">
                                    <?= e($arte->getNome()) ?>
                                </a>
                            </h5>
                            <?php if ($arte->getDescricao()): ?>
                                <p class="text-muted"><?= e(str_limit($arte->getDescricao(), 150)) ?></p>
                            <?php endif; ?>
                            
                            <?php
                            $complexidade = $arte->getComplexidade();
                            $compClass = match($complexidade) {
                                'baixa' => 'success',
                                'media' => 'warning',
                                'alta' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $compClass ?>">
                                Complexidade: <?= ucfirst($complexidade ?? 'N/A') ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th>Custo de Produção:</th>
                                    <td class="text-end">R$ <?= number_format($arte->getPrecoCusto(), 2, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th>Horas Trabalhadas:</th>
                                    <td class="text-end"><?= number_format($arte->getHorasTrabalhadas(), 1, ',', '.') ?>h</td>
                                </tr>
                                <tr>
                                    <th>Margem de Lucro:</th>
                                    <td class="text-end">
                                        <?php 
                                        $margem = $arte->getPrecoCusto() > 0 
                                            ? (($valor - $arte->getPrecoCusto()) / $arte->getPrecoCusto()) * 100 
                                            : 0;
                                        ?>
                                        <span class="text-<?= $margem >= 0 ? 'success' : 'danger' ?>">
                                            <?= number_format($margem, 1, ',', '.') ?>%
                                        </span>
                                    </td>
                                </tr>
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
        
        <!-- Detalhes do Cliente -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person"></i> Cliente
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($cliente) && $cliente): ?>
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5>
                                <a href="/clientes/<?= $cliente->getId() ?>" class="text-decoration-none">
                                    <?= e($cliente->getNome()) ?>
                                </a>
                            </h5>
                            <?php if ($cliente->getEmpresa()): ?>
                                <p class="text-muted mb-1">
                                    <i class="bi bi-building"></i> <?= e($cliente->getEmpresa()) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($cliente->getEmail()): ?>
                                <p class="mb-1">
                                    <i class="bi bi-envelope"></i> 
                                    <a href="mailto:<?= e($cliente->getEmail()) ?>">
                                        <?= e($cliente->getEmail()) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <?php if ($cliente->getTelefone()): ?>
                                <p class="mb-0">
                                    <i class="bi bi-telephone"></i> 
                                    <a href="tel:<?= e($cliente->getTelefone()) ?>">
                                        <?= e($cliente->getTelefone()) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                        <a href="/clientes/<?= $cliente->getId() ?>" class="btn btn-outline-primary btn-sm">
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
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Resumo -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-receipt"></i> Resumo da Venda</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th>Data:</th>
                        <td class="text-end"><?= date('d/m/Y', strtotime($dataVenda)) ?></td>
                    </tr>
                    <tr>
                        <th>Valor:</th>
                        <td class="text-end fw-bold">R$ <?= number_format($valor, 2, ',', '.') ?></td>
                    </tr>
                    <?php if (isset($arte) && $arte): ?>
                    <tr>
                        <th>Custo:</th>
                        <td class="text-end text-muted">R$ <?= number_format($arte->getPrecoCusto(), 2, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top">
                        <th>Lucro:</th>
                        <td class="text-end text-success fw-bold">R$ <?= number_format($lucro, 2, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Indicadores -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Indicadores</h6>
            </div>
            <div class="card-body">
                <?php if (isset($arte) && $arte && $arte->getHorasTrabalhadas() > 0): ?>
                    <?php $custoHora = $arte->getPrecoCusto() / $arte->getHorasTrabalhadas(); ?>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Rentabilidade por Hora</small>
                        <span class="fs-4 fw-bold text-<?= $rentabilidade >= 50 ? 'success' : ($rentabilidade >= 30 ? 'warning' : 'danger') ?>">
                            R$ <?= number_format($rentabilidade, 2, ',', '.') ?>/h
                        </span>
                        <div class="progress mt-1" style="height: 5px;">
                            <div class="progress-bar bg-<?= $rentabilidade >= 50 ? 'success' : ($rentabilidade >= 30 ? 'warning' : 'danger') ?>" 
                                 style="width: <?= min(($rentabilidade / 100) * 100, 100) ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Custo por Hora</small>
                        <span class="fs-5">R$ <?= number_format($custoHora, 2, ',', '.') ?>/h</span>
                    </div>
                    
                    <div>
                        <small class="text-muted d-block">Margem de Lucro</small>
                        <?php 
                        $margem = $arte->getPrecoCusto() > 0 
                            ? (($valor - $arte->getPrecoCusto()) / $arte->getPrecoCusto()) * 100 
                            : 0;
                        ?>
                        <span class="fs-5 text-<?= $margem >= 100 ? 'success' : ($margem >= 50 ? 'warning' : 'danger') ?>">
                            <?= number_format($margem, 1, ',', '.') ?>%
                        </span>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0 small">
                        Indicadores não disponíveis (sem dados de horas)
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informações do Sistema -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informações</h6>
            </div>
            <div class="card-body small">
                <p class="mb-1">
                    <strong>Registrada em:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($venda->getCreatedAt())) ?>
                </p>
                <?php if ($venda->getUpdatedAt()): ?>
                <p class="mb-0">
                    <strong>Última alteração:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($venda->getUpdatedAt())) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
