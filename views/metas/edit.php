<?php
/**
 * VIEW: Editar Meta
 * GET /metas/{id}/editar
 * 
 * Variáveis:
 * - $meta: Objeto Meta para edição
 * 
 * CORREÇÃO (29/01/2026): 
 * - Campo CSRF padronizado para _token
 * - URLs usando helper url()
 * - Verificação de métodos do Model
 */
$currentPage = 'metas';

// Obtém mês/ano da meta
$mesAno = $meta->getMesAno();
$mesFormatado = date('F/Y', strtotime($mesAno));

// Calcula porcentagem
$porcentagem = 0;
if (method_exists($meta, 'getPorcentagemAtingida')) {
    $porcentagem = $meta->getPorcentagemAtingida();
} elseif ($meta->getValorMeta() > 0) {
    $porcentagem = ($meta->getValorRealizado() / $meta->getValorMeta()) * 100;
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-pencil text-primary"></i> Editar Meta
        </h2>
        <p class="text-muted mb-0"><?= $mesFormatado ?></p>
    </div>
    <a href="<?= url('/metas') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Progresso Atual -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="text-muted mb-2">Progresso Atual</h6>
                <div class="progress mb-2" style="height: 25px;">
                    <div class="progress-bar <?= $porcentagem >= 100 ? 'bg-success' : 'bg-primary' ?>" 
                         style="width: <?= min($porcentagem, 100) ?>%">
                        <?= number_format($porcentagem, 1) ?>%
                    </div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Realizado: <?= money($meta->getValorRealizado()) ?></span>
                    <span>Meta: <?= money($meta->getValorMeta()) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Formulário -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil"></i> Alterar Valores
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/metas/' . $meta->getId()) ?>" id="formMeta">
                    <!-- CORREÇÃO: Token CSRF padronizado para _token -->
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <!-- Mês/Ano (readonly) -->
                    <div class="mb-3">
                        <label class="form-label">Mês/Ano</label>
                        <input type="text" class="form-control" 
                               value="<?= date('m/Y', strtotime($mesAno)) ?>" 
                               readonly 
                               disabled>
                        <small class="text-muted">O período não pode ser alterado</small>
                    </div>
                    
                    <div class="row">
                        <!-- Valor da Meta -->
                        <div class="col-md-6 mb-3">
                            <label for="valor_meta" class="form-label">
                                Valor da Meta <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" 
                                       class="form-control <?= has_error('valor_meta') ? 'is-invalid' : '' ?>"
                                       id="valor_meta" 
                                       name="valor_meta" 
                                       value="<?= old('valor_meta', $meta->getValorMeta()) ?>"
                                       min="0"
                                       step="0.01"
                                       required>
                            </div>
                            <?php if (has_error('valor_meta')): ?>
                                <div class="invalid-feedback d-block"><?= errors('valor_meta') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Horas Diárias Ideal -->
                        <div class="col-md-6 mb-3">
                            <label for="horas_diarias_ideal" class="form-label">
                                Horas Diárias Ideal
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control <?= has_error('horas_diarias_ideal') ? 'is-invalid' : '' ?>"
                                       id="horas_diarias_ideal" 
                                       name="horas_diarias_ideal" 
                                       value="<?= old('horas_diarias_ideal', $meta->getHorasDiariasIdeal()) ?>"
                                       min="1"
                                       max="24"
                                       step="0.5">
                                <span class="input-group-text">h/dia</span>
                            </div>
                            <?php if (has_error('horas_diarias_ideal')): ?>
                                <div class="invalid-feedback d-block"><?= errors('horas_diarias_ideal') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Dias de Trabalho por Semana -->
                        <div class="col-md-6 mb-3">
                            <label for="dias_trabalho_semana" class="form-label">
                                Dias de Trabalho/Semana
                            </label>
                            <select class="form-select <?= has_error('dias_trabalho_semana') ? 'is-invalid' : '' ?>"
                                    id="dias_trabalho_semana" 
                                    name="dias_trabalho_semana">
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option value="<?= $i ?>" <?= old('dias_trabalho_semana', $meta->getDiasTrabalhoSemana()) == $i ? 'selected' : '' ?>>
                                        <?= $i ?> dia<?= $i > 1 ? 's' : '' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <?php if (has_error('dias_trabalho_semana')): ?>
                                <div class="invalid-feedback"><?= errors('dias_trabalho_semana') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                        </button>
                        <a href="<?= url('/metas') ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#modalExcluir">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar com Estatísticas -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Estatísticas</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">Valor Realizado</label>
                    <h5 class="text-success"><?= money($meta->getValorRealizado()) ?></h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Falta para Meta</label>
                    <?php 
                    $faltante = $meta->getValorMeta() - $meta->getValorRealizado();
                    ?>
                    <h5 class="<?= $faltante <= 0 ? 'text-success' : 'text-warning' ?>">
                        <?= money(max(0, $faltante)) ?>
                    </h5>
                </div>
                <div>
                    <label class="text-muted small">Status</label>
                    <p class="mb-0">
                        <?php if ($porcentagem >= 100): ?>
                            <span class="badge bg-success">Meta Atingida! 🎉</span>
                        <?php elseif ($porcentagem >= 75): ?>
                            <span class="badge bg-info">Quase lá!</span>
                        <?php elseif ($porcentagem >= 50): ?>
                            <span class="badge bg-primary">Em progresso</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Iniciando</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a meta de <strong><?= $mesFormatado ?></strong>?</p>
                <p class="text-muted mb-0">As vendas do período não serão afetadas.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?= url('/metas/' . $meta->getId()) ?>" method="POST" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
