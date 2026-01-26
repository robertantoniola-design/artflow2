<?php
/**
 * VIEW: Registrar Venda
 * GET /vendas/criar
 */
$currentPage = 'vendas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-cart-plus text-primary"></i> Nova Venda
        </h2>
        <p class="text-muted mb-0">Registre uma nova venda</p>
    </div>
    <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row">
    <!-- Formulário -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="<?= url('/vendas') ?>" method="POST" id="formVenda">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <!-- Arte -->
                    <div class="mb-3">
                        <label for="arte_id" class="form-label">
                            Arte <span class="text-danger">*</span>
                        </label>
                        <select name="arte_id" 
                                id="arte_id"
                                class="form-select <?= has_error('arte_id') ? 'is-invalid' : '' ?>" 
                                required>
                            <option value="">Selecione uma arte...</option>
                            <?php foreach ($artesDisponiveis ?? [] as $arte): ?>
                                <option value="<?= $arte->getId() ?>" 
                                        data-custo="<?= $arte->getPrecoCusto() ?>"
                                        data-horas="<?= $arte->getHorasTrabalhadas() ?>"
                                        <?= old('arte_id') == $arte->getId() ? 'selected' : '' ?>>
                                    <?= e($arte->getNome()) ?> 
                                    (Custo: <?= money($arte->getPrecoCusto()) ?> | <?= $arte->getHorasTrabalhadas() ?>h)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (has_error('arte_id')): ?>
                            <div class="invalid-feedback"><?= errors('arte_id') ?></div>
                        <?php endif; ?>
                        <?php if (empty($artesDisponiveis)): ?>
                            <div class="alert alert-warning mt-2">
                                <i class="bi bi-exclamation-triangle"></i>
                                Nenhuma arte disponível para venda. 
                                <a href="<?= url('/artes/criar') ?>">Cadastre uma arte primeiro</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-select">
                            <option value="">Selecione um cliente (opcional)...</option>
                            <?php foreach ($clientesSelect ?? [] as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" 
                                        <?= old('cliente_id', $clienteSelecionado ?? '') == $cliente['id'] ? 'selected' : '' ?>>
                                    <?= e($cliente['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            Não encontrou o cliente? 
                            <a href="<?= url('/clientes/criar') ?>" target="_blank">Cadastre aqui</a>
                        </small>
                    </div>
                    
                    <div class="row">
                        <!-- Valor -->
                        <div class="col-md-6 mb-3">
                            <label for="valor" class="form-label">
                                Valor de Venda <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" 
                                       name="valor" 
                                       id="valor"
                                       class="form-control <?= has_error('valor') ? 'is-invalid' : '' ?>" 
                                       value="<?= old('valor') ?>"
                                       step="0.01"
                                       min="0.01"
                                       placeholder="0,00"
                                       required>
                                <?php if (has_error('valor')): ?>
                                    <div class="invalid-feedback"><?= errors('valor') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Data -->
                        <div class="col-md-6 mb-3">
                            <label for="data_venda" class="form-label">
                                Data da Venda <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   name="data_venda" 
                                   id="data_venda"
                                   class="form-control <?= has_error('data_venda') ? 'is-invalid' : '' ?>" 
                                   value="<?= old('data_venda', date('Y-m-d')) ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   required>
                            <?php if (has_error('data_venda')): ?>
                                <div class="invalid-feedback"><?= errors('data_venda') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary" <?= empty($artesDisponiveis) ? 'disabled' : '' ?>>
                            <i class="bi bi-check-lg"></i> Registrar Venda
                        </button>
                        <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Prévia dos Cálculos -->
    <div class="col-lg-4">
        <div class="card" id="cardPrevia" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> Prévia</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">Custo da Arte</label>
                    <h5 id="prevCusto">R$ 0,00</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Valor de Venda</label>
                    <h5 id="prevValor" class="text-primary">R$ 0,00</h5>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="text-muted small">Lucro Estimado</label>
                    <h4 id="prevLucro" class="text-success">R$ 0,00</h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Horas Trabalhadas</label>
                    <p id="prevHoras" class="mb-0">0h</p>
                </div>
                <div>
                    <label class="text-muted small">Rentabilidade/Hora</label>
                    <h5 id="prevRentabilidade" class="text-info">R$ 0,00/h</h5>
                </div>
            </div>
        </div>
        
        <!-- Dica -->
        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="bi bi-lightbulb text-warning"></i> Dica</h6>
                <p class="text-muted small mb-0">
                    O lucro e a rentabilidade são calculados automaticamente com base no custo 
                    e nas horas trabalhadas da arte selecionada.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Elementos
const arteSelect = document.getElementById('arte_id');
const valorInput = document.getElementById('valor');
const cardPrevia = document.getElementById('cardPrevia');

// Dados da arte selecionada
let arteCusto = 0;
let arteHoras = 0;

// Atualiza prévia quando seleciona arte
arteSelect.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    
    if (this.value) {
        arteCusto = parseFloat(option.dataset.custo) || 0;
        arteHoras = parseFloat(option.dataset.horas) || 0;
        cardPrevia.style.display = 'block';
        atualizarPrevia();
    } else {
        cardPrevia.style.display = 'none';
    }
});

// Atualiza prévia quando digita valor
valorInput.addEventListener('input', atualizarPrevia);

function atualizarPrevia() {
    const valor = parseFloat(valorInput.value) || 0;
    const lucro = valor - arteCusto;
    const rentabilidade = arteHoras > 0 ? lucro / arteHoras : 0;
    
    document.getElementById('prevCusto').textContent = formatMoney(arteCusto);
    document.getElementById('prevValor').textContent = formatMoney(valor);
    document.getElementById('prevLucro').textContent = formatMoney(lucro);
    document.getElementById('prevLucro').className = lucro >= 0 ? 'text-success' : 'text-danger';
    document.getElementById('prevHoras').textContent = arteHoras.toFixed(1) + 'h';
    document.getElementById('prevRentabilidade').textContent = formatMoney(rentabilidade) + '/h';
}

function formatMoney(value) {
    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Trigger inicial se já tem arte selecionada
if (arteSelect.value) {
    arteSelect.dispatchEvent(new Event('change'));
}
</script>
