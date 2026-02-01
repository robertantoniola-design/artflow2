<?php
/**
 * VIEW: Criar Cliente
 * GET /clientes/criar
 */
$currentPage = 'clientes';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-person-plus text-primary"></i> Novo Cliente
        </h2>
        <p class="text-muted mb-0">Cadastre um novo cliente</p>
    </div>
    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- Formulário -->
<div class="card">
    <div class="card-body">
        <form action="<?= url('/clientes') ?>" method="POST" id="formCliente">
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
                           value="<?= old('nome') ?>"
                           placeholder="Nome completo do cliente"
                           required
                           autofocus>
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
                           value="<?= old('email') ?>"
                           placeholder="email@exemplo.com">
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
                           value="<?= old('telefone') ?>"
                           placeholder="(00) 00000-0000"
                           data-mask="phone">
                    <?php if (has_error('telefone')): ?>
                        <div class="invalid-feedback"><?= errors('telefone') ?></div>
                    <?php endif; ?>
                    <small class="text-muted">Formato: (00) 00000-0000</small>
                </div>
                
                <!-- Empresa -->
                <div class="col-md-6">
                    <label for="empresa" class="form-label">Empresa</label>
                    <input type="text" 
                           name="empresa" 
                           id="empresa"
                           class="form-control <?= has_error('empresa') ? 'is-invalid' : '' ?>" 
                           value="<?= old('empresa') ?>"
                           placeholder="Nome da empresa (opcional)">
                    <?php if (has_error('empresa')): ?>
                        <div class="invalid-feedback"><?= errors('empresa') ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Salvar Cliente
                </button>
                <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
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
