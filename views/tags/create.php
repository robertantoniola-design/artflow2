<?php
/**
 * VIEW: Criar Tag
 * GET /tags/criar
 */
$currentPage = 'tags';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-tag text-primary"></i> Nova Tag
        </h2>
        <p class="text-muted mb-0">Crie uma tag para organizar suas artes</p>
    </div>
    <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <form action="<?= url('/tags') ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <!-- Nome -->
                    <div class="mb-4">
                        <label for="nome" class="form-label">
                            Nome da Tag <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="nome" 
                               id="nome"
                               class="form-control <?= has_error('nome') ? 'is-invalid' : '' ?>" 
                               value="<?= old('nome') ?>"
                               placeholder="Ex: Aquarela, Retrato, Paisagem..."
                               maxlength="50"
                               required
                               autofocus>
                        <?php if (has_error('nome')): ?>
                            <div class="invalid-feedback"><?= errors('nome') ?></div>
                        <?php endif; ?>
                        <small class="text-muted">2 a 50 caracteres</small>
                    </div>
                    
                    <!-- Cor -->
                    <div class="mb-4">
                        <label class="form-label">Cor</label>
                        
                        <!-- Cores Predefinidas -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($coresPredefinidas as $hex => $nome): ?>
                                <div class="form-check">
                                    <input type="radio" 
                                           name="cor" 
                                           value="<?= $hex ?>"
                                           id="cor_<?= substr($hex, 1) ?>"
                                           class="btn-check"
                                           <?= old('cor', '#6c757d') === $hex ? 'checked' : '' ?>>
                                    <label for="cor_<?= substr($hex, 1) ?>" 
                                           class="btn btn-outline-secondary"
                                           style="width: 40px; height: 40px; background-color: <?= $hex ?>; border-color: <?= $hex ?>;"
                                           title="<?= $nome ?>">
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Cor Personalizada -->
                        <div class="input-group">
                            <span class="input-group-text">Cor personalizada</span>
                            <input type="color" 
                                   id="corPersonalizada"
                                   class="form-control form-control-color"
                                   value="<?= old('cor', '#6c757d') ?>"
                                   title="Escolha uma cor personalizada">
                            <input type="text" 
                                   id="corHex"
                                   class="form-control"
                                   value="<?= old('cor', '#6c757d') ?>"
                                   pattern="^#[0-9A-Fa-f]{6}$"
                                   placeholder="#000000">
                        </div>
                        <?php if (has_error('cor')): ?>
                            <div class="text-danger small mt-1"><?= errors('cor') ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Preview -->
                    <div class="mb-4">
                        <label class="form-label">Preview</label>
                        <div class="p-3 bg-light rounded">
                            <span id="tagPreview" class="badge fs-5" style="background-color: <?= old('cor', '#6c757d') ?>;">
                                <?= old('nome') ?: 'Nome da Tag' ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Criar Tag
                        </button>
                        <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Dicas -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Dicas</h5>
            </div>
            <div class="card-body">
                <h6>Boas práticas para tags:</h6>
                <ul class="text-muted">
                    <li>Use nomes curtos e descritivos</li>
                    <li>Agrupe por técnica: Aquarela, Óleo, Digital...</li>
                    <li>Agrupe por tema: Retrato, Paisagem, Abstrato...</li>
                    <li>Agrupe por cliente ou projeto</li>
                    <li>Use cores diferentes para categorias distintas</li>
                </ul>
                
                <hr>
                
                <h6>Exemplos de tags:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge" style="background-color: #007bff;">Aquarela</span>
                    <span class="badge" style="background-color: #28a745;">Paisagem</span>
                    <span class="badge" style="background-color: #dc3545;">Urgente</span>
                    <span class="badge" style="background-color: #6f42c1;">Encomenda</span>
                    <span class="badge" style="background-color: #fd7e14;">Em exposição</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const nomeInput = document.getElementById('nome');
const corRadios = document.querySelectorAll('input[name="cor"]');
const corPersonalizada = document.getElementById('corPersonalizada');
const corHex = document.getElementById('corHex');
const tagPreview = document.getElementById('tagPreview');

// Atualiza preview quando digita nome
nomeInput.addEventListener('input', function() {
    tagPreview.textContent = this.value || 'Nome da Tag';
});

// Atualiza preview quando seleciona cor predefinida
corRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        tagPreview.style.backgroundColor = this.value;
        corPersonalizada.value = this.value;
        corHex.value = this.value;
    });
});

// Sincroniza cor personalizada
corPersonalizada.addEventListener('input', function() {
    corHex.value = this.value;
    tagPreview.style.backgroundColor = this.value;
    // Desmarca radios
    corRadios.forEach(r => r.checked = false);
});

corHex.addEventListener('input', function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        corPersonalizada.value = this.value;
        tagPreview.style.backgroundColor = this.value;
        corRadios.forEach(r => r.checked = false);
    }
});

// Adiciona campo hidden para garantir que a cor seja enviada
document.querySelector('form').addEventListener('submit', function() {
    // Verifica se algum radio está marcado, senão usa a cor personalizada
    let corSelecionada = null;
    corRadios.forEach(r => { if (r.checked) corSelecionada = r.value; });
    
    if (!corSelecionada) {
        // Cria input hidden com a cor personalizada
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'cor';
        hiddenInput.value = corHex.value;
        this.appendChild(hiddenInput);
    }
});
</script>
