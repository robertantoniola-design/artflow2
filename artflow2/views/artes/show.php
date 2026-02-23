<?php
/**
 * ============================================
 * View: Detalhes da Arte
 * GET /artes/{id}
 * ============================================
 * 
 * Variáveis disponíveis (via Controller):
 * - $arte (Arte)           — Objeto da arte
 * - $tags (array<Tag>)     — Tags associadas
 * - $custoPorHora (float)  — Retrocompatibilidade (M5 usa $metricas)
 * - $precoSugerido (float) — Retrocompatibilidade (M5 usa $metricas)
 * - $statusList (array)    — Lista de status para dropdown
 * - $metricas (array)      — [M5] Métricas completas:
 *     - custo_por_hora  (float|null)
 *     - preco_sugerido  (float)
 *     - progresso       (array|null: percentual, valor_real, horas_faltam)
 *     - lucro           (array|null: valor_venda, lucro, margem_percentual) ← NOVO
 *     - rentabilidade   (float|null: R$/hora)                               ← NOVO
 * 
 * HISTÓRICO:
 * - [Fase 1]    URLs com url(), status "reservada", botão excluir
 * - [Melhoria 4] Imagem ampliada 400px com zoom
 * - [Melhoria 5] Cards de métricas (Custo/Hora, Preço Sugerido, Progresso)
 * - [M5 CROSS-MODULE] Cards de Lucro e Rentabilidade (22/02/2026)
 *     Só visíveis quando status = 'vendida' e dados de venda existem.
 *     Layout: 3 cards base (row 1) + 2 cards condicionais (row 2).
 */
$currentPage = 'artes';

// === Status: cor e label ===
$status = $arte->getStatus();
$statusClass = match($status) {
    'disponivel'  => 'success',
    'em_producao' => 'warning',
    'vendida'     => 'info',
    'reservada'   => 'primary',
    default       => 'secondary'
};
$statusLabel = match($status) {
    'disponivel'  => 'Disponível',
    'em_producao' => 'Em Produção',
    'vendida'     => 'Vendida',
    'reservada'   => 'Reservada',
    default       => ucfirst($status)
};

// === Dados do Model (usados em vários locais da view) ===
$horasTrabalhadas = $arte->getHorasTrabalhadas();
$precoCusto       = $arte->getPrecoCusto();
$tempoEstimado    = method_exists($arte, 'getTempoMedioHoras') ? $arte->getTempoMedioHoras() : 0;
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/artes') ?>">Artes</a></li>
        <li class="breadcrumb-item active"><?= e($arte->getNome()) ?></li>
    </ol>
</nav>

<!-- Header: título + badges + botões -->
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
        <?php if ($status !== 'vendida'): ?>
            <a href="<?= url('/artes/' . $arte->getId() . '/editar') ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="<?= url('/vendas/criar?arte_id=' . $arte->getId()) ?>" class="btn btn-success">
                <i class="bi bi-cart-plus"></i> Registrar Venda
            </a>
        <?php endif; ?>
        
        <!-- Botão Excluir com confirmação -->
        <form action="<?= url('/artes/' . $arte->getId()) ?>" method="POST" 
              class="d-inline"
              onsubmit="return confirm('Tem certeza que deseja excluir a arte \'<?= e(addslashes($arte->getNome())) ?>\'? Esta ação não pode ser desfeita.')">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Excluir
            </button>
        </form>
        
        <a href="<?= url('/artes') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- [MELHORIA 5] Cards de Métricas da Arte                        -->
<!-- Row 1: 3 Cards base (Custo/Hora, Preço Sugerido, Progresso)   -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    
    <!-- Card 1: Custo por Hora -->
    <div class="col-md-4">
        <div class="card border-start border-4 border-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">
                            <i class="bi bi-clock-fill text-info"></i> Custo/Hora
                        </h6>
                        <?php if ($metricas['custo_por_hora'] !== null): ?>
                            <h3 class="mb-0">
                                R$ <?= number_format($metricas['custo_por_hora'], 2, ',', '.') ?>
                            </h3>
                            <small class="text-muted">
                                por hora trabalhada
                            </small>
                        <?php else: ?>
                            <h3 class="mb-0 text-muted">N/A</h3>
                            <small class="text-muted">
                                Sem horas registradas
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="fs-1 text-info opacity-25">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Preço Sugerido -->
    <div class="col-md-4">
        <div class="card border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">
                            <i class="bi bi-tag-fill text-success"></i> Preço Sugerido
                        </h6>
                        <h3 class="mb-0">
                            R$ <?= number_format($metricas['preco_sugerido'], 2, ',', '.') ?>
                        </h3>
                        <small class="text-muted">
                            multiplicador 2.5× sobre custo
                        </small>
                    </div>
                    <div class="fs-1 text-success opacity-25">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <?php if ($metricas['custo_por_hora'] !== null): ?>
                    <div class="mt-2">
                        <small class="text-muted">
                            Margem: R$ <?= number_format(
                                $metricas['preco_sugerido'] - $precoCusto, 
                                2, ',', '.'
                            ) ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 3: Progresso -->
    <div class="col-md-4">
        <div class="card border-start border-4 border-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="card-subtitle text-muted mb-1">
                            <i class="bi bi-hourglass-split text-warning"></i> Progresso
                        </h6>
                        <?php if ($metricas['progresso'] !== null): ?>
                            <?php
                            $prog = $metricas['progresso'];
                            $barraClasse = $prog['valor_real'] > 100 ? 'bg-danger' : 'bg-warning';
                            $progressoTexto = $prog['valor_real'] > 100 
                                ? 'Ultrapassou em ' . round($prog['valor_real'] - 100, 1) . '%'
                                : $prog['percentual'] . '% concluído';
                            ?>
                            <h3 class="mb-0">
                                <?= $prog['valor_real'] ?>%
                            </h3>
                            
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar <?= $barraClasse ?>" 
                                     role="progressbar" 
                                     style="width: <?= $prog['percentual'] ?>%"
                                     aria-valuenow="<?= $prog['percentual'] ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            
                            <small class="text-muted d-block mt-1">
                                <?= $progressoTexto ?>
                                <?php if ($prog['horas_faltam'] > 0): ?>
                                    — faltam <?= number_format($prog['horas_faltam'], 1, ',', '.') ?>h
                                <?php endif; ?>
                            </small>
                        <?php else: ?>
                            <h3 class="mb-0 text-muted">—</h3>
                            <small class="text-muted">
                                Sem estimativa de tempo
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="fs-1 text-warning opacity-25">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- ══════════════════════════════════════════ -->
<!-- [M5] FIM Row 1 — Cards Base               -->
<!-- ══════════════════════════════════════════ -->

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- [M5 CROSS-MODULE] Cards de Lucro e Rentabilidade              -->
<!-- Row 2: Só aparecem para artes VENDIDAS com dados de venda     -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php if ($metricas['lucro'] !== null): ?>
<div class="row g-3 mb-4">
    
    <!-- Card 4: Lucro da Venda -->
    <div class="col-md-6">
        <div class="card border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">
                            <i class="bi bi-graph-up-arrow text-primary"></i> Lucro da Venda
                        </h6>
                        <?php
                        $lucroData = $metricas['lucro'];
                        // Cor do lucro: verde se positivo, vermelho se negativo
                        $lucroClasse = $lucroData['lucro'] >= 0 ? 'text-success' : 'text-danger';
                        $lucroIcone  = $lucroData['lucro'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
                        ?>
                        <h3 class="mb-0 <?= $lucroClasse ?>">
                            R$ <?= number_format($lucroData['lucro'], 2, ',', '.') ?>
                        </h3>
                        <small class="text-muted">
                            Vendida por R$ <?= number_format($lucroData['valor_venda'], 2, ',', '.') ?>
                            — Custo R$ <?= number_format($precoCusto, 2, ',', '.') ?>
                        </small>
                    </div>
                    <div class="fs-1 text-primary opacity-25">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                
                <!-- Barra de margem percentual -->
                <div class="mt-3">
                    <?php
                    // Margem: limita visualmente a 200% para não distorcer a barra
                    $margemVisual = min(100, abs($lucroData['margem_percentual']) / 2);
                    $margemBarraClasse = $lucroData['margem_percentual'] >= 0 ? 'bg-success' : 'bg-danger';
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Margem sobre custo</small>
                        <small class="fw-bold <?= $lucroClasse ?>">
                            <i class="bi <?= $lucroIcone ?>"></i>
                            <?= number_format($lucroData['margem_percentual'], 1, ',', '.') ?>%
                        </small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar <?= $margemBarraClasse ?>" 
                             style="width: <?= $margemVisual ?>%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Rentabilidade por Hora -->
    <div class="col-md-6">
        <div class="card border-start border-4 border-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">
                            <i class="bi bi-lightning-charge-fill text-danger"></i> Rentabilidade/Hora
                        </h6>
                        <?php if ($metricas['rentabilidade'] !== null): ?>
                            <h3 class="mb-0">
                                R$ <?= number_format($metricas['rentabilidade'], 2, ',', '.') ?><small class="text-muted">/h</small>
                            </h3>
                            <small class="text-muted">
                                lucro por hora trabalhada
                            </small>
                            
                            <?php
                            // Comparação: rentabilidade vs custo/hora
                            // Se rentabilidade > custo/hora, a arte foi lucrativa
                            if ($metricas['custo_por_hora'] !== null && $metricas['custo_por_hora'] > 0):
                                $multiplicador = round($metricas['rentabilidade'] / $metricas['custo_por_hora'], 1);
                            ?>
                                <div class="mt-2">
                                    <small class="<?= $multiplicador >= 1 ? 'text-success' : 'text-danger' ?>">
                                        <i class="bi bi-<?= $multiplicador >= 1 ? 'check-circle' : 'exclamation-circle' ?>"></i>
                                        <?= $multiplicador ?>× o custo por hora
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <h3 class="mb-0 text-muted">N/A</h3>
                            <small class="text-muted">
                                Sem horas registradas
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="fs-1 text-danger opacity-25">
                        <i class="bi bi-lightning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>
<!-- ══════════════════════════════════════════════════════ -->
<!-- [M5 CROSS-MODULE] FIM Row 2 — Cards Lucro/Rentab     -->
<!-- ══════════════════════════════════════════════════════ -->


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
        
        <!-- ============================================ -->
        <!-- [MELHORIA 4] Imagem da Arte (Ampliada)      -->
        <!-- ============================================ -->
        <?php if ($arte->getImagem()): ?>
            <div class="text-center mb-4">
                <img src="<?= url('/' . e($arte->getImagem())) ?>" 
                     alt="<?= e($arte->getNome()) ?>" 
                     class="img-fluid rounded shadow-sm" 
                     style="max-height: 400px; cursor: pointer;"
                     onclick="this.classList.toggle('img-expanded')"
                     title="Clique para ampliar">
            </div>
        <?php else: ?>
            <div class="text-center mb-4 p-4 bg-light rounded border">
                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2 mb-0">Nenhuma imagem cadastrada</p>
                <?php if ($status !== 'vendida'): ?>
                <a href="<?= url('/artes/' . $arte->getId() . '/editar') ?>" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="bi bi-upload"></i> Adicionar imagem
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Informações Técnicas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-gear"></i> Informações Técnicas</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <small class="text-muted d-block">Custo Material</small>
                        <strong>R$ <?= number_format($precoCusto, 2, ',', '.') ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Tempo Estimado</small>
                        <strong><?= $tempoEstimado > 0 ? number_format($tempoEstimado, 1, ',', '.') . 'h' : '—' ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Horas Trabalhadas</small>
                        <strong><?= number_format($horasTrabalhadas, 1, ',', '.') ?>h</strong>
                    </div>
                </div>
            </div>
        </div>
        
    </div><!-- /col-lg-8 -->
    
    <!-- ══════════════════════════════════════════════ -->
    <!-- COLUNA LATERAL: Ações Rápidas + Info           -->
    <!-- ══════════════════════════════════════════════ -->
    <div class="col-lg-4">
        
        <!-- Card Alterar Status -->
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
        
        <!-- Card Adicionar Horas -->
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