<?php
/**
 * View: Detalhes da Arte
 * GET /artes/{id}
 * 
 * Variáveis disponíveis: $arte, $tags, $vendas (se houver), $sessoes (timer)
 * 
 * CORREÇÕES Fase 1 (15/02/2026):
 * - Todas as URLs agora usam helper url() (antes hardcoded, quebrava no XAMPP)
 * - Status "reservada" incluído nos match() de cor/label
 * - Botão Excluir adicionado com confirmação JavaScript
 * - Formulário "Alterar Status" adicionado (T11 — POST /artes/{id}/status)
 * - Formulário "Adicionar Horas" adicionado (T12 — POST /artes/{id}/horas)
 */
$currentPage = 'artes';

// === Status: cor e label ===
// CORREÇÃO: Incluído 'reservada' nos match()
$status = $arte->getStatus();
$statusClass = match($status) {
    'disponivel'  => 'success',
    'em_producao' => 'warning',
    'vendida'     => 'info',
    'reservada'   => 'primary',   // NOVO
    default       => 'secondary'
};
$statusLabel = match($status) {
    'disponivel'  => 'Disponível',
    'em_producao' => 'Em Produção',
    'vendida'     => 'Vendida',
    'reservada'   => 'Reservada',  // NOVO
    default       => ucfirst($status)
};

// === Cálculos ===
$horasTrabalhadas = $arte->getHorasTrabalhadas();
$precoCusto = $arte->getPrecoCusto();
$custoHora = $horasTrabalhadas > 0 ? $precoCusto / $horasTrabalhadas : 0;
$precoSugerido = $precoCusto * 2.5; // Markup 150%
$tempoEstimado = method_exists($arte, 'getTempoMedioHoras') ? $arte->getTempoMedioHoras() : 0;
$progresso = ($tempoEstimado > 0 && $horasTrabalhadas > 0) 
    ? min(100, round(($horasTrabalhadas / $tempoEstimado) * 100)) 
    : 0;
?>

<!-- Breadcrumb — CORREÇÃO: usa url() em vez de hardcoded -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/artes') ?>">Artes</a></li>
        <li class="breadcrumb-item active"><?= e($arte->getNome()) ?></li>
    </ol>
</nav>

<!-- Header — CORREÇÃO: botões usam url(), adicionado Excluir -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1"><?= e($arte->getNome()) ?></h1>
        <span class="badge bg-<?= $statusClass ?> fs-6"><?= $statusLabel ?></span>
        
        <?php if (!empty($tags)): ?>
            <?php foreach ($tags as $tag): ?>
                <span class="badge ms-1" style="background-color: <?= e($tag->getCor()) ?>">
                    <?php if (method_exists($tag, 'getIcone') && $tag->getIcone()): ?>
                        <i class="<?= e($tag->getIcone()) ?> me-1"></i>
                    <?php endif; ?>
                    <?= e($tag->getNome()) ?>
                </span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="btn-group">
        <!-- CORREÇÃO: Botão Editar usa url() — antes hardcoded causava 404 no XAMPP -->
        <?php if ($status !== 'vendida'): ?>
            <a href="<?= url('/artes/' . $arte->getId() . '/editar') ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="<?= url('/vendas/criar?arte_id=' . $arte->getId()) ?>" class="btn btn-success">
                <i class="bi bi-cart-plus"></i> Registrar Venda
            </a>
        <?php endif; ?>
        
        <!-- NOVO: Botão Excluir com confirmação -->
        <form action="<?= url('/artes/' . $arte->getId()) ?>" method="POST" 
              class="d-inline"
              onsubmit="return confirm('Tem certeza que deseja excluir a arte \'<?= e(addslashes($arte->getNome())) ?>\'? Esta ação não pode ser desfeita.')">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Excluir
            </button>
        </form>
        
        <!-- CORREÇÃO: Botão Voltar usa url() -->
        <a href="<?= url('/artes') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="row">
    <!-- ══════════════════════════════════════════════ -->
    <!-- COLUNA PRINCIPAL: Descrição + Info Técnica     -->
    <!-- ══════════════════════════════════════════════ -->
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
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Complexidade</small>
                        <?php
                        $compCor = match($arte->getComplexidade()) {
                            'baixa' => 'success', 'media' => 'warning', 'alta' => 'danger', default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $compCor ?>">
                            <?= ucfirst($arte->getComplexidade()) ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Tempo Estimado</small>
                        <strong><?= $tempoEstimado > 0 ? number_format($tempoEstimado, 1, ',', '.') . 'h' : '—' ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Horas Trabalhadas</small>
                        <strong><?= number_format($horasTrabalhadas, 1, ',', '.') ?>h</strong>
                    </div>
                </div>
                
                <!-- Barra de progresso (se tempo estimado definido) -->
                <?php if ($tempoEstimado > 0): ?>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Progresso</small>
                        <small class="text-muted"><?= $progresso ?>%</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-<?= $progresso >= 100 ? 'success' : 'primary' ?>" 
                             style="width: <?= $progresso ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Cards Financeiros -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <small class="text-muted d-block">Custo</small>
                        <h4 class="mb-0 text-primary"><?= money($precoCusto) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <small class="text-muted d-block">Custo/Hora</small>
                        <h4 class="mb-0 text-warning">
                            <?= $custoHora > 0 ? money($custoHora) : '—' ?>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <small class="text-muted d-block">Preço Sugerido</small>
                        <h4 class="mb-0 text-success"><?= money($precoSugerido) ?></h4>
                        <small class="text-muted">Markup 2.5×</small>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /col-lg-8 -->
    
    <!-- ══════════════════════════════════════════════ -->
    <!-- COLUNA LATERAL: Ações Rápidas + Info           -->
    <!-- ══════════════════════════════════════════════ -->
    <div class="col-lg-4">
        
        <!-- NOVO T11: Card Alterar Status -->
        <?php if ($status !== 'vendida'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-arrow-repeat"></i> Alterar Status
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= url('/artes/' . $arte->getId() . '/status') ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <select name="status" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="disponivel" <?= $status === 'disponivel' ? 'selected' : '' ?>>
                                Disponível
                            </option>
                            <option value="em_producao" <?= $status === 'em_producao' ? 'selected' : '' ?>>
                                Em Produção
                            </option>
                            <option value="vendida" <?= $status === 'vendida' ? 'selected' : '' ?>>
                                Vendida
                            </option>
                            <option value="reservada" <?= $status === 'reservada' ? 'selected' : '' ?>>
                                Reservada
                            </option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-check-lg"></i> Alterar Status
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- NOVO T12: Card Adicionar Horas -->
        <?php if ($status !== 'vendida'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history"></i> Adicionar Horas
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= url('/artes/' . $arte->getId() . '/horas') ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Horas a adicionar</label>
                        <input type="number" 
                               name="horas" 
                               class="form-control" 
                               min="0.5" 
                               step="0.5" 
                               value="1.0"
                               required>
                        <small class="text-muted">
                            Atual: <?= number_format($horasTrabalhadas, 1, ',', '.') ?>h
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-outline-success w-100">
                        <i class="bi bi-plus-lg"></i> Adicionar Horas
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Card de Informações -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> Informações
                </h5>
            </div>
            <div class="card-body">
                <!-- Status -->
                <div class="mb-3">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                </div>
                
                <!-- Tags -->
                <div class="mb-3">
                    <small class="text-muted d-block">Tags</small>
                    <?php if (!empty($tags)): ?>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?= url('/tags/' . $tag->getId()) ?>" class="text-decoration-none">
                                    <span class="badge" style="background-color: <?= e($tag->getCor()) ?>">
                                        <?= e($tag->getNome()) ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic">Sem tags</span>
                    <?php endif; ?>
                </div>
                
                <!-- Datas -->
                <div class="mb-3">
                    <small class="text-muted d-block">Criada em</small>
                    <span><?= date_br($arte->getCreatedAt()) ?></span>
                </div>
                <div class="mb-0">
                    <small class="text-muted d-block">Atualizada em</small>
                    <span><?= date_br($arte->getUpdatedAt()) ?></span>
                </div>
            </div>
        </div>
        
    </div><!-- /col-lg-4 -->
</div>