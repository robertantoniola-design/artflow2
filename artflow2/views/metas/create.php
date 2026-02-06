<?php
/**
 * VIEW: Criar Meta
 * GET /metas/criar
 * 
 * MELHORIA 5 (06/02/2026):
 * - Adicionado checkbox "Repetir meta" e campo "Quantidade de Meses"
 * - JavaScript para toggle condicional do campo quantidade
 * - Preview dos meses que serão criados
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
                    
                    <!-- ============================================ -->
                    <!-- MELHORIA 5: Seção de Meta Recorrente         -->
                    <!-- ============================================ -->
                    <hr class="my-4">
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="recorrente" 
                                   name="recorrente" 
                                   value="1"
                                   <?= old('recorrente') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-medium" for="recorrente">
                                <i class="bi bi-arrow-repeat me-1 text-primary"></i>
                                Repetir meta para os próximos meses
                            </label>
                        </div>
                        <small class="text-muted ms-4 ps-2">
                            Cria automaticamente metas com o mesmo valor para múltiplos meses consecutivos.
                        </small>
                    </div>
                    
                    <!-- Container que aparece/desaparece conforme checkbox -->
                    <div class="mb-3" id="recorrente-wrapper" style="display: none;">
                        <div class="card bg-light border-primary border-opacity-25">
                            <div class="card-body py-3">
                                <div class="row align-items-end">
                                    <div class="col-md-4">
                                        <label for="quantidade_meses" class="form-label fw-medium">
                                            Quantidade de Meses
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="quantidade_meses" 
                                               name="quantidade_meses" 
                                               min="2" 
                                               max="12" 
                                               value="<?= old('quantidade_meses', 3) ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Metas serão criadas para os próximos meses a partir do mês selecionado.
                                            Meses que já possuem meta serão <strong>ignorados automaticamente</strong>.
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Preview dos meses que serão criados -->
                                <div id="preview-meses" class="mt-3" style="display: none;">
                                    <small class="text-muted fw-medium">Meses que serão criados:</small>
                                    <div id="preview-badges" class="mt-1 d-flex flex-wrap gap-1">
                                        <!-- Preenchido via JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FIM MELHORIA 5 -->
                    
                    <!-- Botões -->
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <i class="bi bi-check-lg"></i> <span id="btnText">Criar Meta</span>
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
                    Uma meta desafiadora mas alcançável mantém a motivação alta!
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// ==========================================
// CALCULADORA (código original preservado)
// ==========================================
const valorInput = document.getElementById('valor_meta');
const horasInput = document.getElementById('horas_diarias_ideal');
const diasInput = document.getElementById('dias_trabalho_semana');

function calcular() {
    const valor = parseFloat(valorInput.value) || 0;
    const horasDia = parseInt(horasInput.value) || 8;
    const diasSemana = parseInt(diasInput.value) || 5;
    
    const diasUteis = Math.round((diasSemana / 7) * 30);
    const semanas = 4;
    
    const diario = diasUteis > 0 ? valor / diasUteis : 0;
    const semanal = semanas > 0 ? valor / semanas : 0;
    const horasTotais = diasUteis * horasDia;
    const valorHora = horasTotais > 0 ? valor / horasTotais : 0;
    
    document.getElementById('calcDias').textContent = '~' + diasUteis + ' dias';
    document.getElementById('calcDiario').textContent = formatMoney(diario);
    document.getElementById('calcSemanal').textContent = formatMoney(semanal);
    document.getElementById('calcHoras').textContent = horasTotais + ' horas';
    document.getElementById('calcHora').textContent = formatMoney(valorHora) + '/h';
}

function formatMoney(value) {
    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

valorInput.addEventListener('input', calcular);
horasInput.addEventListener('input', calcular);
diasInput.addEventListener('change', calcular);
calcular();

// ==========================================
// MELHORIA 5: Toggle de Meta Recorrente
// ==========================================
const checkRecorrente = document.getElementById('recorrente');
const wrapperRecorrente = document.getElementById('recorrente-wrapper');
const inputQuantidade = document.getElementById('quantidade_meses');
const inputMesAno = document.getElementById('mes_ano');
const previewMeses = document.getElementById('preview-meses');
const previewBadges = document.getElementById('preview-badges');
const btnText = document.getElementById('btnText');

// Nomes dos meses em português
const nomesMeses = [
    'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
    'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'
];

/**
 * Mostra/esconde a seção de recorrência
 */
function toggleRecorrente() {
    const ativo = checkRecorrente.checked;
    wrapperRecorrente.style.display = ativo ? 'block' : 'none';
    
    // Atualiza texto do botão
    if (ativo) {
        const qtd = parseInt(inputQuantidade.value) || 3;
        btnText.textContent = 'Criar ' + qtd + ' Meta' + (qtd > 1 ? 's' : '');
    } else {
        btnText.textContent = 'Criar Meta';
    }
    
    // Atualiza preview se ativo
    if (ativo) atualizarPreview();
}

/**
 * Gera preview dos meses que serão criados
 * Mostra badges com mês/ano para visualização
 */
function atualizarPreview() {
    const mesAno = inputMesAno.value; // formato: "YYYY-MM"
    const quantidade = parseInt(inputQuantidade.value) || 2;
    
    if (!mesAno || quantidade < 2) {
        previewMeses.style.display = 'none';
        return;
    }
    
    // Parse do mês/ano selecionado
    const partes = mesAno.split('-');
    let ano = parseInt(partes[0]);
    let mes = parseInt(partes[1]) - 1; // 0-based para cálculos
    
    // Gera badges
    let html = '';
    for (let i = 0; i < quantidade; i++) {
        const mesAtual = (mes + i) % 12;
        const anoAtual = ano + Math.floor((mes + i) / 12);
        const nome = nomesMeses[mesAtual] + '/' + anoAtual;
        
        // Primeiro mês em destaque, demais em tom mais suave
        const classe = i === 0 ? 'bg-primary' : 'bg-primary bg-opacity-50';
        html += '<span class="badge ' + classe + '">' + nome + '</span>';
    }
    
    previewBadges.innerHTML = html;
    previewMeses.style.display = 'block';
    
    // Atualiza texto do botão
    btnText.textContent = 'Criar ' + quantidade + ' Meta' + (quantidade > 1 ? 's' : '');
}

// Event listeners
checkRecorrente.addEventListener('change', toggleRecorrente);
inputQuantidade.addEventListener('input', atualizarPreview);
inputMesAno.addEventListener('change', function() {
    if (checkRecorrente.checked) atualizarPreview();
});

// Estado inicial (para quando voltamos com old() após erro de validação)
if (checkRecorrente.checked) {
    toggleRecorrente();
}
</script>