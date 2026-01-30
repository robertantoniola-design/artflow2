<?php
/**
 * VIEW: Dashboard Principal
 * GET /
 * 
 * Variáveis:
 * - $artesStats: Estatísticas de artes
 * - $vendasMes: Vendas do mês atual (array de objetos Venda)
 * - $faturamentoMes: Total faturado no mês
 * - $metaAtual: Array com informações da meta
 * - $topClientes: Array de objetos Cliente (CORREÇÃO: usar métodos, não índices)
 * - $artesDisponiveis: Artes disponíveis para venda
 * - $vendasMensais: Dados para gráfico
 * - $maisRentaveis: Artes mais rentáveis
 * 
 * CORREÇÃO (29/01/2026):
 * - topClientes são objetos Cliente, usar getNome(), getTotalCompras(), etc.
 */
$currentPage = 'dashboard';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Dashboard</h2>
        <p class="text-muted mb-0">Visão geral do seu negócio</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/artes/criar') ?>" class="btn btn-outline-primary">
            <i class="bi bi-plus-lg"></i> Nova Arte
        </a>
        <a href="<?= url('/vendas/criar') ?>" class="btn btn-success">
            <i class="bi bi-cart-plus"></i> Nova Venda
        </a>
    </div>
</div>

<!-- Cards Principais -->
<div class="row g-3 mb-4">
    <!-- Total de Artes -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Total de Artes</h6>
                        <h2 class="mb-0"><?= $artesStats['total'] ?? 0 ?></h2>
                    </div>
                    <i class="bi bi-palette display-6 opacity-50"></i>
                </div>
                <small class="opacity-75">
                    <?= $artesStats['disponiveis'] ?? 0 ?> disponíveis
                </small>
            </div>
        </div>
    </div>
    
    <!-- Vendas do Mês -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Vendas (mês)</h6>
                        <h2 class="mb-0"><?= is_array($vendasMes) ? count($vendasMes) : 0 ?></h2>
                    </div>
                    <i class="bi bi-cart-check display-6 opacity-50"></i>
                </div>
                <small class="opacity-75">
                    <?= money($faturamentoMes ?? 0) ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Artes Disponíveis -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">À Venda</h6>
                        <h2 class="mb-0"><?= is_array($artesDisponiveis) ? count($artesDisponiveis) : 0 ?></h2>
                    </div>
                    <i class="bi bi-tag display-6 opacity-50"></i>
                </div>
                <small class="opacity-75">
                    Prontas para vender
                </small>
            </div>
        </div>
    </div>
    
    <!-- Meta do Mês -->
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75">Meta do Mês</h6>
                        <h2 class="mb-0"><?= number_format($metaAtual['porcentagem'] ?? 0, 0) ?>%</h2>
                    </div>
                    <i class="bi bi-bullseye display-6 opacity-50"></i>
                </div>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-dark" 
                         style="width: <?= min($metaAtual['porcentagem'] ?? 0, 100) ?>%">
                    </div>
                </div>
                <small class="opacity-75 d-block mt-1">
                    <?= money($metaAtual['valor_realizado'] ?? 0) ?> / <?= money($metaAtual['valor_meta'] ?? 0) ?>
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Gráfico de Vendas Mensais -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>
                    Vendas Mensais
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($vendasMensais)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Mês</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendasMensais as $mes): ?>
                                    <tr>
                                        <td><?= $mes['mes_nome'] ?? $mes['mes'] ?? '-' ?></td>
                                        <td class="text-center"><?= $mes['quantidade'] ?? 0 ?></td>
                                        <td class="text-end"><?= money($mes['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-5">Sem dados de vendas</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Clientes -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-trophy me-2 text-warning"></i>
                    Top Clientes
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($topClientes)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topClientes as $index => $cliente): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-<?= $index < 3 ? 'warning' : 'secondary' ?> rounded-pill">
                                        <?= $index + 1 ?>º
                                    </span>
                                    <div>
                                        <?php
                                        // CORREÇÃO: Verifica se é objeto ou array
                                        $nomeCliente = '';
                                        $totalCompras = 0;
                                        $valorTotal = 0;
                                        
                                        if (is_object($cliente)) {
                                            // É um objeto Cliente - usar métodos
                                            $nomeCliente = $cliente->getNome();
                                            // Propriedades extras podem estar como atributos públicos
                                            $totalCompras = $cliente->total_compras ?? 0;
                                            $valorTotal = $cliente->valor_total_compras ?? 0;
                                        } elseif (is_array($cliente)) {
                                            // É um array - usar índices
                                            $nomeCliente = $cliente['nome'] ?? '';
                                            $totalCompras = $cliente['total_compras'] ?? 0;
                                            $valorTotal = $cliente['valor_total_compras'] ?? $cliente['total_gasto'] ?? 0;
                                        }
                                        ?>
                                        <div class="fw-medium"><?= e($nomeCliente) ?></div>
                                        <small class="text-muted">
                                            <?= $totalCompras ?> compra<?= $totalCompras != 1 ? 's' : '' ?>
                                        </small>
                                    </div>
                                </div>
                                <span class="text-success fw-bold">
                                    <?= money($valorTotal) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-people display-6"></i>
                        <p class="mt-2 mb-0">Nenhuma venda registrada</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <a href="<?= url('/clientes') ?>" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-people"></i> Ver Todos os Clientes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Artes Disponíveis e Mais Rentáveis -->
<div class="row g-4 mt-2">
    <!-- Artes Disponíveis para Venda -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-palette me-2 text-info"></i>
                    Prontas para Venda
                </h5>
                <a href="<?= url('/artes?status=disponivel') ?>" class="btn btn-sm btn-outline-info">
                    Ver todas
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($artesDisponiveis)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Arte</th>
                                    <th class="text-end">Custo</th>
                                    <th class="text-center">Horas</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $artesExibir = array_slice(
                                    is_array($artesDisponiveis) ? $artesDisponiveis : [], 
                                    0, 
                                    5
                                );
                                foreach ($artesExibir as $arte): 
                                    // CORREÇÃO: Verifica se é objeto ou array
                                    $arteId = is_object($arte) ? $arte->getId() : ($arte['id'] ?? 0);
                                    $arteNome = is_object($arte) ? $arte->getNome() : ($arte['nome'] ?? '');
                                    $arteCusto = is_object($arte) ? $arte->getPrecoCusto() : ($arte['preco_custo'] ?? 0);
                                    $arteHoras = is_object($arte) ? $arte->getHorasTrabalhadas() : ($arte['horas_trabalhadas'] ?? 0);
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url("/artes/{$arteId}") ?>" class="text-decoration-none">
                                                <?= e($arteNome) ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?= money($arteCusto) ?></td>
                                        <td class="text-center"><?= $arteHoras ?>h</td>
                                        <td class="text-end">
                                            <a href="<?= url("/vendas/criar?arte_id={$arteId}") ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="bi bi-cart-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-palette display-6"></i>
                        <p class="mt-2 mb-0">Nenhuma arte disponível</p>
                        <a href="<?= url('/artes/criar') ?>" class="btn btn-sm btn-primary mt-2">
                            <i class="bi bi-plus-lg"></i> Criar Arte
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Artes Mais Rentáveis -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up-arrow me-2 text-success"></i>
                    Mais Rentáveis
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($maisRentaveis)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Arte</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-end">R$/hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($maisRentaveis, 0, 5) as $item): ?>
                                    <tr>
                                        <td><?= e($item['arte_nome'] ?? $item['nome'] ?? '-') ?></td>
                                        <td class="text-end"><?= money($item['valor'] ?? 0) ?></td>
                                        <td class="text-end text-success fw-bold">
                                            <?= money($item['rentabilidade_hora'] ?? 0) ?>/h
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-graph-up display-6"></i>
                        <p class="mt-2 mb-0">Registre vendas para ver ranking</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
