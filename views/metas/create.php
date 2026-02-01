<?php
/**
 * VIEW: Criar Meta
 * GET /metas/criar
 */
$currentPage = 'metas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-plus-circle text-primary"></i> Nova Meta
        </h2>
        <p class="text-muted mb-0">Defina uma meta mensal de faturamento</p>
    </div>
    <a href="<?= url('/metas') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="<?= url('/metas') ?>" method="POST" id="formMeta">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="row g-3">
                        <!-- Mês/Ano -->
                        <div class="col-md-6">
                            <label for="mes_ano" class="form-label">
                                Mês/Ano <span class="text-danger">*</span>
                            </label>
                            <input type="month" 
                                   name="mes_ano" 
                                   id="mes_ano"
                                   class="form-control <?= has_error('mes_ano') ? 'is-invalid' : '' ?>" 
                                   value="<?= old('mes_ano', date('Y-m')) ?>"
                                   required>
                            <?php if (has_error('mes_ano')): ?>
                                <div class="invalid-feedback"><?= errors('mes_ano') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Valor da Meta -->
                        <div class="col-md-6">
                            <label for="valor_meta" class="form-label">
                                Valor da Meta <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" 
                                       name="valor_meta" 
                                       id="valor_meta"
                                       class="form-control <?= has_error('valor_meta') ? 'is-invalid' : '' ?>" 
                                       value="<?= old('valor_meta') ?>"
                                       step="0.01"
                                       min="0.01"
                                       placeholder="0,00"
                                       required>
                                <?php if (has_error('valor_meta')): ?>
                                    <div class="invalid-feedback"><?= errors('valor_meta') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Horas Diárias Ideal -->
                        <div class="col-md-6">
                            <label for="horas_diarias_ideal" class="form-label">
                                Horas de Trabalho por Dia
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       name="horas_diarias_ideal" 
                                       id="horas_diarias_ideal"
                                       class="form-control <?= has_error('horas_diarias_ideal') ? 'is-invalid' : '' ?>" 
                                       value="<?= old('horas_diarias_ideal', 8) ?>"
                                       min="1"
                                       max="24">
                                <span class="input-group-text">horas</span>
                                <?php if (has_error('horas_diarias_ideal')): ?>
                                    <div class="invalid-feedback"><?= errors('horas_diarias_ideal') ?></div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Usado para calcular carga horária ideal</small>
                        </div>
                        
                        <!-- Dias de Trabalho por Semana -->
                        <div class="col-md-6">
                            <label for="dias_trabalho_semana" class="form-label">
                                Dias de Trabalho por Semana
                            </label>
                            <select name="dias_trabalho_semana" 
                                    id="dias_trabalho_semana"
                                    class="form-select <?= has_error('dias_trabalho_semana') ? 'is-invalid' : '' ?>">
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option value="<?= $i ?>" <?= old('dias_trabalho_semana', 5) == $i ? 'selected' : '' ?>>
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
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Criar Meta
                        </button>
                        <a href="<?= url('/metas') ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Calculadora -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> Calculadora</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Com base nos valores informados:</p>
                
                <div class="mb-3">
                    <label class="text-muted small">Dias úteis no mês</label>
                    <h5 id="calcDias">~22 dias</h5>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small">Meta diária</label>
                    <h5 id="calcDiario" class="text-primary">R$ 0,00</h5>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small">Meta semanal</label>
                    <h5 id="calcSemanal" class="text-info">R$ 0,00</h5>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <label class="text-muted small">Horas totais no mês</label>
                    <h5 id="calcHoras">0 horas</h5>
                </div>
                
                <div>
                    <label class="text-muted small">Valor por hora de trabalho</label>
                    <h5 id="calcHora" class="text-success">R$ 0,00/h</h5>
                </div>
            </div>
        </div>
        
        <!-- Dica -->
        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="bi bi-lightbulb text-warning"></i> Dica</h6>
                <p class="text-muted small mb-0">
                    Defina metas realistas baseadas no seu histórico. 
                    Uma meta desafiadora mas alcançável mantém a motivação alta!
                </p>
            </div>
        </div>
    </div>
</div>

<script>
const valorInput = document.getElementById('valor_meta');
const horasInput = document.getElementById('horas_diarias_ideal');
const diasInput = document.getElementById('dias_trabalho_semana');

function calcular() {
    const valor = parseFloat(valorInput.value) || 0;
    const horasDia = parseInt(horasInput.value) || 8;
    const diasSemana = parseInt(diasInput.value) || 5;
    
    // Dias úteis no mês (aproximado)
    const diasUteis = Math.round((diasSemana / 7) * 30);
    const semanas = 4;
    
    // Cálculos
    const diario = diasUteis > 0 ? valor / diasUteis : 0;
    const semanal = semanas > 0 ? valor / semanas : 0;
    const horasTotais = diasUteis * horasDia;
    const valorHora = horasTotais > 0 ? valor / horasTotais : 0;
    
    // Atualiza interface
    document.getElementById('calcDias').textContent = '~' + diasUteis + ' dias';
    document.getElementById('calcDiario').textContent = formatMoney(diario);
    document.getElementById('calcSemanal').textContent = formatMoney(semanal);
    document.getElementById('calcHoras').textContent = horasTotais + ' horas';
    document.getElementById('calcHora').textContent = formatMoney(valorHora) + '/h';
}

function formatMoney(value) {
    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Eventos
valorInput.addEventListener('input', calcular);
horasInput.addEventListener('input', calcular);
diasInput.addEventListener('change', calcular);

// Cálculo inicial
calcular();
</script>
