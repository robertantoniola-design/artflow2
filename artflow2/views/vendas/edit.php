<?php
/**
 * VIEW: Editar Venda
 * GET /vendas/{id}/editar
 * 
 * Variáveis:
 * - $venda: Objeto Venda
 * - $clientesSelect: Array [id => nome] de clientes
 * 
 * CORREÇÃO (29/01/2026):
 * - Foreach do select de clientes usa $id => $nome
 */
$currentPage = 'vendas';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-pencil text-primary"></i> Editar Venda #<?= $venda->getId() ?>
        </h2>
        <p class="text-muted mb-0">Altere os dados da venda</p>
    </div>
    <a href="<?= url('/vendas/' . $venda->getId()) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row">
    <!-- Formulário -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="<?= url('/vendas/' . $venda->getId()) ?>" method="POST" id="formVenda">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <!-- Arte (não editável) -->
                    <div class="mb-3">
                        <label class="form-label">Arte</label>
                        <input type="text" 
                               class="form-control" 
                               value="<?= e($venda->arte_nome ?? 'Arte #' . $venda->getArteId()) ?>" 
                               disabled>
                        <small class="text-muted">A arte da venda não pode ser alterada</small>
                    </div>
                    
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-select">
                            <option value="">Sem cliente vinculado</option>
                            <?php 
                            // CORREÇÃO: ClienteService::getParaSelect() retorna [id => nome]
                            foreach ($clientesSelect ?? [] as $id => $nome): 
                            ?>
                                <option value="<?= $id ?>" 
                                        <?= old('cliente_id', $venda->getClienteId()) == $id ? 'selected' : '' ?>>
                                    <?= e($nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                                       step="0.01"
                                       min="0.01"
                                       value="<?= old('valor', $venda->getValor()) ?>"
                                       required>
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
                            <input type="date" 
                                   name="data_venda" 
                                   id="data_venda"
                                   class="form-control <?= has_error('data_venda') ? 'is-invalid' : '' ?>" 
                                   value="<?= old('data_venda', $venda->getDataVenda()) ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   required>
                            <?php if (has_error('data_venda')): ?>
                                <div class="invalid-feedback"><?= errors('data_venda') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                        </button>
                        <a href="<?= url('/vendas/' . $venda->getId()) ?>" class="btn btn-outline-secondary">
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
    
    <!-- Info da Venda -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i> Informações
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">Lucro Calculado</label>
                    <h5 class="<?= ($venda->getLucroCalculado() ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= money($venda->getLucroCalculado() ?? 0) ?>
                    </h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Rentabilidade/Hora</label>
                    <h5 class="text-info">
                        <?= money($venda->getRentabilidadeHora() ?? 0) ?>/h
                    </h5>
                </div>
                <hr>
                <div>
                    <label class="text-muted small">Registrada em</label>
                    <p class="mb-0"><?= datetime_br($venda->getCreatedAt()) ?></p>
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
                <p>Tem certeza que deseja excluir esta venda?</p>
                <p class="text-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    A arte voltará ao status "disponível" e a meta do mês será ajustada.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?= url('/vendas/' . $venda->getId()) ?>" method="POST" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir Venda
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
