<?php
/**
 * VIEW: Registrar Venda
 * GET /vendas/criar
 * 
 * CORREÇÃO (29/01/2026):
 * - clientesSelect é array [id => nome], usar $id => $nome no foreach
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
                        <select name="arte_id" id="arte_id"
                                class="form-select <?= has_error('arte_id') ? 'is-invalid' : '' ?>" 
                                required>
                            <option value="">Selecione uma arte...</option>
                            <?php foreach ($artesDisponiveis ?? [] as $arte): 
                                // Verifica se é objeto ou array
                                $arteId = is_object($arte) ? $arte->getId() : ($arte['id'] ?? 0);
                                $arteNome = is_object($arte) ? $arte->getNome() : ($arte['nome'] ?? '');
                                $arteCusto = is_object($arte) ? $arte->getPrecoCusto() : ($arte['preco_custo'] ?? 0);
                                $arteHoras = is_object($arte) ? $arte->getHorasTrabalhadas() : ($arte['horas_trabalhadas'] ?? 0);
                            ?>
                                <option value="<?= $arteId ?>" 
                                        data-custo="<?= $arteCusto ?>"
                                        data-horas="<?= $arteHoras ?>"
                                        <?= old('arte_id', $arteSelecionada ?? '') == $arteId ? 'selected' : '' ?>>
                                    <?= e($arteNome) ?> 
                                    (Custo: <?= money($arteCusto) ?> | <?= $arteHoras ?>h)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (has_error('arte_id')): ?>
                            <div class="invalid-feedback"><?= errors('arte_id') ?></div>
                        <?php endif; ?>
                        <?php if (empty($artesDisponiveis)): ?>
                            <div class="alert alert-warning mt-2">
                                <i class="bi bi-exclamation-triangle"></i>
                                Nenhuma arte disponível. 
                                <a href="<?= url('/artes/criar') ?>">Cadastre uma arte primeiro</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-select">
                            <option value="">Selecione um cliente (opcional)...</option>
                            <?php 
                            // CORREÇÃO: clientesSelect é [id => nome]
                            foreach ($clientesSelect ?? [] as $id => $nome): 
                            ?>
                                <option value="<?= $id ?>" 
                                        <?= old('cliente_id', $clienteSelecionado ?? '') == $id ? 'selected' : '' ?>>
                                    <?= e($nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <a href="<?= url('/clientes/criar') ?>" target="_blank">Cadastrar novo cliente</a>
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
                                <input type="number" name="valor" id="valor"
                                       class="form-control <?= has_error('valor') ? 'is-invalid' : '' ?>" 
                                       step="0.01" min="0.01"
                                       value="<?= old('valor') ?>"
                                       placeholder="0,00" required>
                            </div>
                            <?php if (has_error('valor')): ?>
                                <div class="invalid-feedback d-block"><?= errors('valor') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Data -->
                        <div class="col-md-6 mb-3">
                            <label for="data_venda" class="form-label">
                                Data da Venda <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="data_venda" id="data_venda"
                                   class="form-control <?= has_error('data_venda') ? 'is-invalid' : '' ?>" 
                                   value="<?= old('data_venda', date('Y-m-d')) ?>"
                                   max="<?= date('Y-m-d') ?>" required>
                            <?php if (has_error('data_venda')): ?>
                                <div class="invalid-feedback"><?= errors('data_venda') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Registrar Venda
                        </button>
                        <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Preview -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-calculator"></i> Prévia</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">Custo</label>
                    <h5 id="previewCusto" class="text-danger">R$ 0,00</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Valor de Venda</label>
                    <h5 id="previewValor" class="text-success">R$ 0,00</h5>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="text-muted small">Lucro Estimado</label>
                    <h4 id="previewLucro" class="text-primary">R$ 0,00</h4>
                </div>
                <div>
                    <label class="text-muted small">Rentabilidade/Hora</label>
                    <h5 id="previewRent" class="text-info">R$ 0,00/h</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const arteSelect = document.getElementById('arte_id');
    const valorInput = document.getElementById('valor');
    
    function formatMoney(value) {
        return 'R$ ' + parseFloat(value || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }
    
    function updatePreview() {
        const opt = arteSelect.options[arteSelect.selectedIndex];
        const custo = parseFloat(opt?.dataset?.custo || 0);
        const horas = parseFloat(opt?.dataset?.horas || 0);
        const valor = parseFloat(valorInput.value || 0);
        
        const lucro = valor - custo;
        const rent = horas > 0 ? lucro / horas : 0;
        
        document.getElementById('previewCusto').textContent = formatMoney(custo);
        document.getElementById('previewValor').textContent = formatMoney(valor);
        document.getElementById('previewLucro').textContent = formatMoney(lucro);
        document.getElementById('previewLucro').className = lucro >= 0 ? 'text-success' : 'text-danger';
        document.getElementById('previewRent').textContent = formatMoney(rent) + '/h';
    }
    
    arteSelect.addEventListener('change', updatePreview);
    valorInput.addEventListener('input', updatePreview);
    updatePreview();
});
</script>
