<?php
/**
 * View: Editar Meta
 * Formulário de edição de meta mensal existente
 */

// Variáveis: $meta
$mesAno = $meta->getMesAno();
$mesFormatado = date('F/Y', strtotime($mesAno));
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/metas">Metas</a></li>
        <li class="breadcrumb-item active">Editar Meta</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Editar Meta</h1>
                <p class="text-muted mb-0">
                    <?= strftime('%B/%Y', strtotime($mesAno)) ?>
                </p>
            </div>
            <a href="/metas" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
        
        <!-- Progresso Atual -->
        <div class="card mb-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Progresso Atual</h6>
                    <span class="badge bg-<?= $meta->getPorcentagemAtingida() >= 100 ? 'success' : 'primary' ?>">
                        <?= number_format($meta->getPorcentagemAtingida(), 1, ',', '.') ?>%
                    </span>
                </div>
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-<?= $meta->getPorcentagemAtingida() >= 100 ? 'success' : 'primary' ?>" 
                         style="width: <?= min($meta->getPorcentagemAtingida(), 100) ?>%">
                    </div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Realizado: R$ <?= number_format($meta->getValorRealizado(), 2, ',', '.') ?></span>
                    <span>Meta: R$ <?= number_format($meta->getValorMeta(), 2, ',', '.') ?></span>
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
                <form method="POST" action="/metas/<?= $meta->getId() ?>" id="formMeta">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    
                    <!-- Mês/Ano (readonly) -->
                    <div class="mb-3">
                        <label class="form-label">Mês/Ano</label>
                        <input type="text" class="form-control" 
                               value="<?= date('m/Y', strtotime($mesAno)) ?>" readonly disabled>
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
                                       step="0.01" 
                                       min="0.01"
                                       value="<?= old('valor_meta', $meta->getValorMeta()) ?>" 
                                       required>
                            </div>
                            <?php if (has_error('valor_meta')): ?>
                                <div class="invalid-feedback d-block"><?= errors('valor_meta') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Valor Realizado -->
                        <div class="col-md-6 mb-3">
                            <label for="valor_realizado" class="form-label">Valor Realizado</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" 
                                       class="form-control" 
                                       id="valor_realizado" 
                                       name="valor_realizado" 
                                       step="0.01" 
                                       min="0"
                                       value="<?= old('valor_realizado', $meta->getValorRealizado()) ?>">
                            </div>
                            <small class="text-muted">Atualizado automaticamente pelas vendas</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Horas Diárias -->
                        <div class="col-md-6 mb-3">
                            <label for="horas_diarias_ideal" class="form-label">Horas Diárias de Trabalho</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="horas_diarias_ideal" 
                                       name="horas_diarias_ideal" 
                                       min="1" 
                                       max="24"
                                       value="<?= old('horas_diarias_ideal', $meta->getHorasDiariasIdeal()) ?>">
                                <span class="input-group-text">horas/dia</span>
                            </div>
                        </div>
                        
                        <!-- Dias por Semana -->
                        <div class="col-md-6 mb-3">
                            <label for="dias_trabalho_semana" class="form-label">Dias de Trabalho por Semana</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="dias_trabalho_semana" 
                                       name="dias_trabalho_semana" 
                                       min="1" 
                                       max="7"
                                       value="<?= old('dias_trabalho_semana', $meta->getDiasTrabalhoSemana()) ?>">
                                <span class="input-group-text">dias/semana</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calculadora -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-calculator"></i> Projeções Atualizadas</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="small text-muted">Meta Diária</div>
                                    <div class="fw-bold text-primary" id="metaDiaria">R$ 0,00</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Meta Semanal</div>
                                    <div class="fw-bold text-primary" id="metaSemanal">R$ 0,00</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Falta Vender</div>
                                    <div class="fw-bold text-danger" id="faltaVender">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Salvar Alterações
                        </button>
                        <a href="/metas" class="btn btn-outline-secondary">Cancelar</a>
                        
                        <button type="button" class="btn btn-outline-info ms-auto" id="btnRecalcular">
                            <i class="bi bi-arrow-repeat"></i> Recalcular Progresso
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Card Perigo -->
        <div class="card border-danger mt-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Zona de Perigo</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Excluir esta meta irá remover permanentemente todos os dados de progresso associados.
                </p>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalExcluir">
                    <i class="bi bi-trash"></i> Excluir Meta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a meta de <strong><?= date('m/Y', strtotime($mesAno)) ?></strong>?</p>
                <p class="text-danger mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Esta ação não pode ser desfeita!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="/metas/<?= $meta->getId() ?>/excluir" class="d-inline">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Sim, Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const valorMeta = document.getElementById('valor_meta');
    const valorRealizado = document.getElementById('valor_realizado');
    const horasDiarias = document.getElementById('horas_diarias_ideal');
    const diasSemana = document.getElementById('dias_trabalho_semana');
    
    // Formatar moeda
    function formatMoney(value) {
        return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    
    // Calcular projeções
    function calcular() {
        const meta = parseFloat(valorMeta.value) || 0;
        const realizado = parseFloat(valorRealizado.value) || 0;
        const horas = parseInt(horasDiarias.value) || 8;
        const dias = parseInt(diasSemana.value) || 5;
        
        // Dias úteis no mês (aproximado)
        const diasUteisMes = dias * 4;
        
        // Meta diária
        const metaDiaria = meta / diasUteisMes;
        document.getElementById('metaDiaria').textContent = formatMoney(metaDiaria);
        
        // Meta semanal
        const metaSemanal = meta / 4;
        document.getElementById('metaSemanal').textContent = formatMoney(metaSemanal);
        
        // Falta vender
        const falta = Math.max(0, meta - realizado);
        document.getElementById('faltaVender').textContent = formatMoney(falta);
        document.getElementById('faltaVender').className = falta > 0 ? 'fw-bold text-danger' : 'fw-bold text-success';
    }
    
    // Event listeners
    valorMeta.addEventListener('input', calcular);
    valorRealizado.addEventListener('input', calcular);
    horasDiarias.addEventListener('input', calcular);
    diasSemana.addEventListener('input', calcular);
    
    // Recalcular progresso via AJAX
    document.getElementById('btnRecalcular').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Recalculando...';
        
        fetch('/metas/<?= $meta->getId() ?>/recalcular', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao recalcular: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(() => alert('Erro de conexão'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Recalcular Progresso';
        });
    });
    
    // Calcular inicial
    calcular();
});
</script>

<style>
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
