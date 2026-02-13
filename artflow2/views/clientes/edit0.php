<?php
/**
 * VIEW: Editar Cliente
 * GET /clientes/{id}/editar
 */
$currentPage = 'clientes';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-person-gear text-primary"></i> Editar Cliente
        </h2>
        <p class="text-muted mb-0"><?= e($cliente->getNome()) ?></p>
    </div>
    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- Formulário -->
<div class="card">
    <div class="card-body">
        <form action="<?= url("/clientes/{$cliente->getId()}") ?>" method="POST">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            
            <div class="row g-3">
                <!-- Nome -->
                <div class="col-md-6">
                    <label for="nome" class="form-label">
                        Nome <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           name="nome" 
                           id="nome"
                           class="form-control <?= has_error('nome') ? 'is-invalid' : '' ?>" 
                           value="<?= old('nome', $cliente->getNome()) ?>"
                           required>
                    <?php if (has_error('nome')): ?>
                        <div class="invalid-feedback"><?= errors('nome') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" 
                           value="<?= old('email', $cliente->getEmail()) ?>">
                    <?php if (has_error('email')): ?>
                        <div class="invalid-feedback"><?= errors('email') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Telefone -->
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="tel" 
                           name="telefone" 
                           id="telefone"
                           class="form-control <?= has_error('telefone') ? 'is-invalid' : '' ?>" 
                           value="<?= old('telefone', $cliente->getTelefone()) ?>"
                           data-mask="phone">
                    <?php if (has_error('telefone')): ?>
                        <div class="invalid-feedback"><?= errors('telefone') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Empresa -->
                <div class="col-md-6">
                    <label for="empresa" class="form-label">Empresa</label>
                    <input type="text" 
                           name="empresa" 
                           id="empresa"
                           class="form-control <?= has_error('empresa') ? 'is-invalid' : '' ?>" 
                           value="<?= old('empresa', $cliente->getEmpresa()) ?>">
                    <?php if (has_error('empresa')): ?>
                        <div class="invalid-feedback"><?= errors('empresa') ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Info -->
            <div class="mt-3 p-3 bg-light rounded">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Cadastrado em <?= datetime_br($cliente->getCreatedAt()) ?>
                </small>
            </div>
            
            <!-- Botões -->
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Salvar Alterações
                </button>
                <a href="<?= url("/clientes/{$cliente->getId()}") ?>" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Máscara de telefone
document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substring(0, 11);
    
    if (value.length > 6) {
        value = '(' + value.substring(0,2) + ') ' + value.substring(2,7) + '-' + value.substring(7);
    } else if (value.length > 2) {
        value = '(' + value.substring(0,2) + ') ' + value.substring(2);
    } else if (value.length > 0) {
        value = '(' + value;
    }
    
    e.target.value = value;
});
</script>
