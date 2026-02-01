<?php
/**
 * VIEW: Registrar Venda
 * GET /vendas/criar
 * 
 * CORREÇÕES (01/02/2026):
 * - Adicionado campo forma_pagamento (select com opções de pagamento)
 * - Adicionado campo observacoes (textarea)
 * - Estes campos existem na tabela vendas mas estavam ausentes do form
 * - Sem eles, INSERT falhava quando forma_pagamento não tinha DEFAULT no MySQL
 * - Token CSRF padronizado para _csrf
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
                    <!-- CORREÇÃO: Padronizado para _csrf (bug #12 documentado) -->
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    
                    <!-- ========================================
                         ARTE (obrigatório)
                         ======================================== -->
                    <div class="mb-3">
                        <label for="arte_id" class="form-label">
                            Arte <span class="text-danger">*</span>
                        </label>
                        <select name="arte_id" id="arte_id"
                                class="form-select <?= has_error('arte_id') ? 'is-invalid' : '' ?>" 
                                required>
                            <option value="">Selecione uma arte...</option>
                            <?php foreach ($artesDisponiveis ?? [] as $arte): 
                                // Compatível com objeto ou array
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
                    
                    <!-- ========================================
                         CLIENTE (opcional)
                         ======================================== -->
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-select">
                            <!-- NOTA: value="" é intencional - o Controller converte "" → null -->
                            <option value="">Selecione um cliente (opcional)...</option>
                            <?php 
                            // clientesSelect é [id => nome]
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
                        <!-- ========================================
                             VALOR (obrigatório)
                             ======================================== -->
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
                        
                        <!-- ========================================
                             DATA DA VENDA (obrigatório)
                             ======================================== -->
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
                    
                    <div class="row">
                        <!-- ========================================
                             FORMA DE PAGAMENTO (ADICIONADO - 01/02/2026)
                             Campo existia na tabela mas não no form.
                             Sem ele, INSERT falhava quando a coluna 
                             forma_pagamento não tinha DEFAULT no MySQL.
                             ======================================== -->
                        <div class="col-md-6 mb-3">
                            <label for="forma_pagamento" class="form-label">
                                Forma de Pagamento
                            </label>
                            <select name="forma_pagamento" id="forma_pagamento"
                                    class="form-select <?= has_error('forma_pagamento') ? 'is-invalid' : '' ?>">
                                <option value="pix" <?= old('forma_pagamento', 'pix') === 'pix' ? 'selected' : '' ?>>
                                    PIX
                                </option>
                                <option value="dinheiro" <?= old('forma_pagamento') === 'dinheiro' ? 'selected' : '' ?>>
                                    Dinheiro
                                </option>
                                <option value="cartao_credito" <?= old('forma_pagamento') === 'cartao_credito' ? 'selected' : '' ?>>
                                    Cartão de Crédito
                                </option>
                                <option value="cartao_debito" <?= old('forma_pagamento') === 'cartao_debito' ? 'selected' : '' ?>>
                                    Cartão de Débito
                                </option>
                                <option value="transferencia" <?= old('forma_pagamento') === 'transferencia' ? 'selected' : '' ?>>
                                    Transferência
                                </option>
                                <option value="outro" <?= old('forma_pagamento') === 'outro' ? 'selected' : '' ?>>
                                    Outro
                                </option>
                            </select>
                            <?php if (has_error('forma_pagamento')): ?>
                                <div class="invalid-feedback"><?= errors('forma_pagamento') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ========================================
                         OBSERVAÇÕES (ADICIONADO - 01/02/2026)
                         Campo existia na tabela mas não no form.
                         ======================================== -->
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea name="observacoes" id="observacoes" 
                                  class="form-control" rows="3"
                                  placeholder="Anotações sobre a venda (opcional)..."><?= old('observacoes') ?></textarea>
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
    
    <!-- Preview de cálculos em tempo real -->
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
                <div class="mb-3">
                    <label class="text-muted small">Margem</label>
                    <h5 id="previewMargem" class="text-secondary">0%</h5>
                </div>
                <div>
                    <label class="text-muted small">Rentabilidade/Hora</label>
                    <h5 id="previewRent" class="text-info">R$ 0,00/h</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript: Cálculo automático da prévia -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const arteSelect = document.getElementById('arte_id');
    const valorInput = document.getElementById('valor');
    
    /**
     * Formata valor em reais
     */
    function formatMoney(value) {
        return 'R$ ' + parseFloat(value || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }
    
    /**
     * Atualiza prévia de cálculos quando arte ou valor mudam
     */
    function updatePreview() {
        const opt = arteSelect.options[arteSelect.selectedIndex];
        const custo = parseFloat(opt?.dataset?.custo || 0);
        const horas = parseFloat(opt?.dataset?.horas || 0);
        const valor = parseFloat(valorInput.value || 0);
        
        const lucro = valor - custo;
        const rent = horas > 0 ? lucro / horas : 0;
        const margem = valor > 0 ? (lucro / valor) * 100 : 0;
        
        // Atualiza displays
        document.getElementById('previewCusto').textContent = formatMoney(custo);
        document.getElementById('previewValor').textContent = formatMoney(valor);
        document.getElementById('previewLucro').textContent = formatMoney(lucro);
        document.getElementById('previewRent').textContent = formatMoney(rent) + '/h';
        document.getElementById('previewMargem').textContent = margem.toFixed(1) + '%';
        
        // Cores dinâmicas baseadas no lucro
        const lucroEl = document.getElementById('previewLucro');
        lucroEl.className = lucro >= 0 ? 'text-primary' : 'text-danger';
    }
    
    // Event listeners
    arteSelect.addEventListener('change', updatePreview);
    valorInput.addEventListener('input', updatePreview);
    
    // Calcula inicial se houver valores pré-preenchidos (old input)
    updatePreview();
});
</script>