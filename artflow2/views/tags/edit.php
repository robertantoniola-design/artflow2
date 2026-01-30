<?php
/**
 * VIEW: Editar Tag
 * GET /tags/{id}/editar
 * 
 * Variáveis:
 * - $tag: Objeto Tag para edição
 * - $coresPredefinidas: Array de cores predefinidas
 * 
 * CORREÇÃO (29/01/2026): 
 * - Campo CSRF padronizado para _token
 * - URLs usando helper url()
 */
$currentPage = 'tags';
$corAtual = $tag->getCor();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/tags') ?>">Tags</a></li>
        <li class="breadcrumb-item active">Editar Tag</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Editar Tag</h1>
                <span class="badge" style="background-color: <?= e($corAtual) ?>; font-size: 1rem;">
                    <?= e($tag->getNome()) ?>
                </span>
            </div>
            <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
        
        <!-- Formulário -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil"></i> Alterar Tag
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/tags/' . $tag->getId()) ?>" id="formTag">
                    <!-- CORREÇÃO: Token CSRF padronizado para _token -->
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <!-- Nome -->
                    <div class="mb-4">
                        <label for="nome" class="form-label">
                            Nome da Tag <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control <?= has_error('nome') ? 'is-invalid' : '' ?>"
                               id="nome" 
                               name="nome" 
                               value="<?= old('nome', $tag->getNome()) ?>"
                               maxlength="50"
                               required>
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
                            <?php foreach ($coresPredefinidas ?? [] as $hex => $nome): ?>
                                <div class="form-check">
                                    <input type="radio" 
                                           name="cor" 
                                           value="<?= $hex ?>"
                                           id="cor_<?= substr($hex, 1) ?>"
                                           class="btn-check"
                                           <?= old('cor', $corAtual) === $hex ? 'checked' : '' ?>>
                                    <label for="cor_<?= substr($hex, 1) ?>"
                                           class="btn btn-outline-secondary"
                                           style="background-color: <?= $hex ?>; border-color: <?= $hex ?>; width: 40px; height: 40px;"
                                           title="<?= e($nome) ?>">
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Cor Customizada -->
                        <div class="input-group" style="max-width: 200px;">
                            <span class="input-group-text">
                                <i class="bi bi-palette"></i>
                            </span>
                            <input type="color" 
                                   id="corCustom" 
                                   class="form-control form-control-color" 
                                   value="<?= old('cor', $corAtual) ?>"
                                   title="Escolha uma cor customizada">
                            <input type="text" 
                                   name="cor" 
                                   id="corHex"
                                   class="form-control <?= has_error('cor') ? 'is-invalid' : '' ?>"
                                   value="<?= old('cor', $corAtual) ?>"
                                   pattern="^#[0-9A-Fa-f]{6}$"
                                   placeholder="#000000">
                        </div>
                        <?php if (has_error('cor')): ?>
                            <div class="invalid-feedback d-block"><?= errors('cor') ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Formato: #RRGGBB</small>
                    </div>
                    
                    <!-- Prévia -->
                    <div class="mb-4">
                        <label class="form-label">Prévia</label>
                        <div>
                            <span class="badge fs-6" id="previewTag" style="background-color: <?= e($corAtual) ?>;">
                                <?= e($tag->getNome()) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                        </button>
                        <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">
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
                <p>Tem certeza que deseja excluir a tag <strong><?= e($tag->getNome()) ?></strong>?</p>
                <p class="text-muted mb-0">As artes associadas a esta tag não serão excluídas, apenas a associação será removida.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?= url('/tags/' . $tag->getId()) ?>" method="POST" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Sincroniza cor customizada com campo hex
document.addEventListener('DOMContentLoaded', function() {
    const corCustom = document.getElementById('corCustom');
    const corHex = document.getElementById('corHex');
    const previewTag = document.getElementById('previewTag');
    const nomeInput = document.getElementById('nome');
    const radios = document.querySelectorAll('input[name="cor"][type="radio"]');
    
    // Atualiza prévia
    function updatePreview() {
        previewTag.style.backgroundColor = corHex.value;
        previewTag.textContent = nomeInput.value || 'Tag';
    }
    
    // Cor picker -> hex input
    corCustom.addEventListener('input', function() {
        corHex.value = this.value;
        // Desmarca radios
        radios.forEach(r => r.checked = false);
        updatePreview();
    });
    
    // Hex input -> cor picker
    corHex.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            corCustom.value = this.value;
            // Desmarca radios
            radios.forEach(r => r.checked = false);
            updatePreview();
        }
    });
    
    // Radio buttons
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            corHex.value = this.value;
            corCustom.value = this.value;
            updatePreview();
        });
    });
    
    // Nome
    nomeInput.addEventListener('input', updatePreview);
});
</script>
