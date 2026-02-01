<?php
/**
 * View: Detalhes da Arte
 * Exibe informações completas de uma arte específica
 */

// Variáveis disponíveis: $arte, $tags, $vendas (se houver), $sessoes (timer)
$status = $arte->getStatus();
$statusClass = match($status) {
    'disponivel' => 'success',
    'em_producao' => 'warning',
    'vendida' => 'info',
    default => 'secondary'
};
$statusLabel = match($status) {
    'disponivel' => 'Disponível',
    'em_producao' => 'Em Produção',
    'vendida' => 'Vendida',
    default => $status
};

// Cálculos
$horasTrabalhadas = $arte->getHorasTrabalhadas();
$precoCusto = $arte->getPrecoCusto();
$custoHora = $horasTrabalhadas > 0 ? $precoCusto / $horasTrabalhadas : 0;
$precoSugerido = $precoCusto * 2.5; // Markup 150%
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/artes">Artes</a></li>
        <li class="breadcrumb-item active"><?= e($arte->getNome()) ?></li>
    </ol>
</nav>

<!-- Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1"><?= e($arte->getNome()) ?></h1>
        <span class="badge bg-<?= $statusClass ?> fs-6"><?= $statusLabel ?></span>
        
        <?php if (!empty($tags)): ?>
            <?php foreach ($tags as $tag): ?>
                <span class="badge ms-1" style="background-color: <?= e($tag->getCor()) ?>">
                    <?= e($tag->getNome()) ?>
                </span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="btn-group">
        <?php if ($status !== 'vendida'): ?>
            <a href="/artes/<?= $arte->getId() ?>/editar" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="/vendas/criar?arte_id=<?= $arte->getId() ?>" class="btn btn-success">
                <i class="bi bi-cart-plus"></i> Registrar Venda
            </a>
        <?php endif; ?>
        <a href="/artes" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="row">
    <!-- Coluna Principal -->
    <div class="col-lg-8">
        <!-- Descrição -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-file-text"></i> Descrição</h5>
            </div>
            <div class="card-body">
                <?php if ($arte->getDescricao()): ?>
                    <p class="mb-0"><?= nl2br(e($arte->getDescricao())) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0"><em>Nenhuma descrição cadastrada</em></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informações Técnicas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-gear"></i> Informações Técnicas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="50%">Complexidade:</th>
                                <td>
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
                                        <?= ucfirst($complexidade ?? 'N/A') ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Tempo Médio Estimado:</th>
                                <td><?= number_format($arte->getTempoMedioHoras() ?? 0, 1, ',', '.') ?> horas</td>
                            </tr>
                            <tr>
                                <th>Horas Trabalhadas:</th>
                                <td>
                                    <strong><?= number_format($horasTrabalhadas, 1, ',', '.') ?> horas</strong>
                                    <?php if ($arte->getTempoMedioHoras() > 0): ?>
                                        <?php $percentual = ($horasTrabalhadas / $arte->getTempoMedioHoras()) * 100; ?>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar bg-<?= $percentual > 100 ? 'danger' : 'success' ?>" 
                                                 style="width: <?= min($percentual, 100) ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= number_format($percentual, 0) ?>% do estimado</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="50%">Preço de Custo:</th>
                                <td><strong>R$ <?= number_format($precoCusto, 2, ',', '.') ?></strong></td>
                            </tr>
                            <tr>
                                <th>Custo por Hora:</th>
                                <td>R$ <?= number_format($custoHora, 2, ',', '.') ?>/h</td>
                            </tr>
                            <tr>
                                <th>Preço Sugerido:</th>
                                <td>
                                    <span class="text-success fw-bold">
                                        R$ <?= number_format($precoSugerido, 2, ',', '.') ?>
                                    </span>
                                    <br><small class="text-muted">(markup 150%)</small>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Histórico de Vendas (se vendida) -->
        <?php if (!empty($vendas)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-receipt"></i> Histórico de Vendas</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Lucro</th>
                            <th class="text-end">R$/hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($venda->getDataVenda())) ?></td>
                            <td><?= e($venda->getClienteNome() ?? 'Cliente avulso') ?></td>
                            <td class="text-end">R$ <?= number_format($venda->getValor(), 2, ',', '.') ?></td>
                            <td class="text-end text-success">
                                R$ <?= number_format($venda->getLucroCalculado() ?? 0, 2, ',', '.') ?>
                            </td>
                            <td class="text-end">
                                R$ <?= number_format($venda->getRentabilidadeHora() ?? 0, 2, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Sessões de Timer -->
        <?php if (!empty($sessoes)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Sessões de Trabalho</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th class="text-end">Duração</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessoes as $sessao): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($sessao['inicio'])) ?></td>
                            <td><?= date('H:i', strtotime($sessao['inicio'])) ?></td>
                            <td>
                                <?= $sessao['fim'] 
                                    ? date('H:i', strtotime($sessao['fim']))
                                    : '<span class="badge bg-warning">Em andamento</span>' 
                                ?>
                            </td>
                            <td class="text-end">
                                <?php
                                $duracao = $sessao['duracao_segundos'];
                                $horas = floor($duracao / 3600);
                                $minutos = floor(($duracao % 3600) / 60);
                                echo sprintf('%dh %02dmin', $horas, $minutos);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Cards de Métricas -->
        <div class="card bg-primary text-white mb-3">
            <div class="card-body text-center">
                <h6 class="text-white-50">Horas Trabalhadas</h6>
                <h2 class="mb-0"><?= number_format($horasTrabalhadas, 1, ',', '.') ?>h</h2>
            </div>
        </div>
        
        <div class="card bg-success text-white mb-3">
            <div class="card-body text-center">
                <h6 class="text-white-50">Custo Total</h6>
                <h2 class="mb-0">R$ <?= number_format($precoCusto, 2, ',', '.') ?></h2>
            </div>
        </div>
        
        <?php if ($status === 'vendida' && !empty($vendas)): ?>
            <?php $ultimaVenda = $vendas[0]; ?>
            <div class="card bg-info text-white mb-3">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Vendida por</h6>
                    <h2 class="mb-0">R$ <?= number_format($ultimaVenda->getValor(), 2, ',', '.') ?></h2>
                </div>
            </div>
            
            <div class="card bg-warning text-dark mb-3">
                <div class="card-body text-center">
                    <h6 class="text-muted">Rentabilidade/Hora</h6>
                    <h2 class="mb-0">R$ <?= number_format($ultimaVenda->getRentabilidadeHora() ?? 0, 2, ',', '.') ?></h2>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Ações Rápidas -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning"></i> Ações Rápidas</h6>
            </div>
            <div class="card-body">
                <?php if ($status !== 'vendida'): ?>
                    <!-- Alterar Status -->
                    <form method="POST" action="/artes/<?= $arte->getId() ?>/status" class="mb-3">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        <label class="form-label small">Alterar Status:</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="disponivel" <?= $status === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                            <option value="em_producao" <?= $status === 'em_producao' ? 'selected' : '' ?>>Em Produção</option>
                        </select>
                    </form>
                    
                    <!-- Adicionar Horas -->
                    <form method="POST" action="/artes/<?= $arte->getId() ?>/horas" class="mb-3">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        <label class="form-label small">Adicionar Horas:</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="horas" class="form-control" step="0.5" min="0.5" placeholder="0.5">
                            <button type="submit" class="btn btn-outline-primary">+</button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <!-- Tags -->
                <div class="mb-0">
                    <label class="form-label small">Tags:</label>
                    <div>
                        <?php if (!empty($tags)): ?>
                            <?php foreach ($tags as $tag): ?>
                                <span class="badge mb-1" style="background-color: <?= e($tag->getCor()) ?>">
                                    <?= e($tag->getNome()) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted small">Nenhuma tag</span>
                        <?php endif; ?>
                        
                        <?php if ($status !== 'vendida'): ?>
                            <a href="/artes/<?= $arte->getId() ?>/editar" class="btn btn-link btn-sm p-0">
                                <i class="bi bi-plus-circle"></i> Gerenciar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info do Sistema -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informações</h6>
            </div>
            <div class="card-body small">
                <p class="mb-1">
                    <strong>Cadastrada em:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($arte->getCreatedAt())) ?>
                </p>
                <?php if ($arte->getUpdatedAt()): ?>
                <p class="mb-0">
                    <strong>Última atualização:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($arte->getUpdatedAt())) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
