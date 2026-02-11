<?php
/**
 * VIEW: Criar Tag (Melhoria 3 — + Descrição e Ícone)
 * GET /tags/criar
 * 
 * Variáveis recebidas do Controller:
 * - $cores: array de cores predefinidas (hex => nome)
 * - $icones: array de ícones disponíveis (classe => nome) — MELHORIA 3
 */
$currentPage = 'tags';
// Variável segura para cores (compatibilidade)
$coresPredefinidas = $cores ?? [];
$iconesDisponiveis = $icones ?? [];
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
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <form action="<?= url('/tags') ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <!-- ==========================================
                         NOME (obrigatório)
                         ========================================== -->
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
                    
                    <!-- ==========================================
                         DESCRIÇÃO (opcional) — MELHORIA 3
                         ========================================== -->
                    <div class="mb-4">
                        <label for="descricao" class="form-label">
                            Descrição
                        </label>
                        <textarea name="descricao" 
                                  id="descricao"
                                  class="form-control <?= has_error('descricao') ? 'is-invalid' : '' ?>" 
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Descreva o uso desta tag... (opcional)"><?= old('descricao') ?></textarea>
                        <?php if (has_error('descricao')): ?>
                            <div class="invalid-feedback"><?= errors('descricao') ?></div>
                        <?php endif; ?>
                        <small class="text-muted">
                            <span id="descricaoCount">0</span>/500 caracteres
                        </small>
                    </div>
                    
                    <!-- ==========================================
                         ÍCONE (opcional) — MELHORIA 3
                         ========================================== -->
                    <div class="mb-4">
                        <label for="icone" class="form-label">
                            Ícone
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" id="iconePreviewContainer">
                                <i id="iconePreview" class="bi bi-tag"></i>
                            </span>
                            <select name="icone" 
                                    id="icone" 
                                    class="form-select <?= has_error('icone') ? 'is-invalid' : '' ?>">
                                <option value="">Sem ícone (padrão)</option>
                                <?php foreach ($iconesDisponiveis as $classe => $nomeIcone): ?>
                                    <option value="<?= e($classe) ?>" 
                                            <?= old('icone') === $classe ? 'selected' : '' ?>>
                                        <?= e($nomeIcone) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (has_error('icone')): ?>
                            <div class="text-danger small mt-1"><?= errors('icone') ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Escolha um ícone Bootstrap Icons para a tag</small>
                    </div>
                    
                    <!-- ==========================================
                         COR (opcional)
                         ========================================== -->
                    <div class="mb-4">
                        <label class="form-label">Cor</label>
                        
                        <!-- Cores Predefinidas (radio buttons visuais) -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($coresPredefinidas as $hex => $nome): ?>
                                <div class="form-check">
                                    <input type="radio" 
                                           name="cor" 
                                           value="<?= $hex ?>"
                                           id="cor_<?= substr($hex, 1) ?>"
                                           class="btn-check corRadio"
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
                        <div class="input-group" style="max-width: 280px;">
                            <span class="input-group-text">Personalizada</span>
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
                    
                    <!-- ==========================================
                         PREVIEW (atualizado em tempo real)
                         ========================================== -->
                    <div class="mb-4">
                        <label class="form-label">Preview</label>
                        <div class="p-3 bg-light rounded">
                            <span id="tagPreview" class="badge fs-5" style="background-color: <?= old('cor', '#6c757d') ?>;">
                                <i id="previewIcone" class="<?= old('icone') ? e(old('icone')) . ' me-1' : '' ?>"></i>
                                <span id="previewNome"><?= old('nome') ?: 'Nome da Tag' ?></span>
                            </span>
                            <p id="previewDescricao" class="text-muted small mt-2 mb-0" style="display: none;"></p>
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
    
    <!-- Dicas (sidebar) -->
    <div class="col-lg-5">
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
                    <li><strong>Novo:</strong> Adicione uma descrição para documentar o uso da tag</li>
                    <li><strong>Novo:</strong> Escolha um ícone para identificação rápida</li>
                </ul>
                
                <hr>
                
                <h6>Exemplos de tags:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge" style="background-color: #17a2b8;"><i class="bi bi-droplet me-1"></i>Aquarela</span>
                    <span class="badge" style="background-color: #28a745;"><i class="bi bi-tree me-1"></i>Paisagem</span>
                    <span class="badge" style="background-color: #dc3545;"><i class="bi bi-exclamation-triangle me-1"></i>Urgente</span>
                    <span class="badge" style="background-color: #6f42c1;"><i class="bi bi-cart me-1"></i>Encomenda</span>
                    <span class="badge" style="background-color: #fd7e14;"><i class="bi bi-star me-1"></i>Destaque</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     JAVASCRIPT: Preview em tempo real
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos
    const nomeInput = document.getElementById('nome');
    const descricaoInput = document.getElementById('descricao');
    const iconeSelect = document.getElementById('icone');
    const corRadios = document.querySelectorAll('.corRadio');
    const corPersonalizada = document.getElementById('corPersonalizada');
    const corHex = document.getElementById('corHex');
    const tagPreview = document.getElementById('tagPreview');
    const previewNome = document.getElementById('previewNome');
    const previewIcone = document.getElementById('previewIcone');
    const previewDescricao = document.getElementById('previewDescricao');
    const iconePreview = document.getElementById('iconePreview');
    const descricaoCount = document.getElementById('descricaoCount');
    
    // === Atualiza preview do badge ===
    function updatePreview() {
        const cor = corHex.value || '#6c757d';
        tagPreview.style.backgroundColor = cor;
        
        // Calcula cor de texto contrastante
        const hex = cor.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        tagPreview.style.color = luminance > 0.5 ? '#000000' : '#ffffff';
    }
    
    // === Nome: atualiza texto no preview ===
    nomeInput.addEventListener('input', function() {
        previewNome.textContent = this.value || 'Nome da Tag';
    });
    
    // === Descrição: contador de caracteres + preview ===
    descricaoInput.addEventListener('input', function() {
        descricaoCount.textContent = this.value.length;
        if (this.value.trim()) {
            previewDescricao.textContent = this.value;
            previewDescricao.style.display = 'block';
        } else {
            previewDescricao.style.display = 'none';
        }
    });
    
    // === Ícone: atualiza preview no badge e no input group ===
    iconeSelect.addEventListener('change', function() {
        const classe = this.value;
        if (classe) {
            previewIcone.className = classe + ' me-1';
            iconePreview.className = classe;
        } else {
            previewIcone.className = '';
            iconePreview.className = 'bi bi-tag';
        }
    });
    
    // === Cor: radio buttons ===
    corRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            corHex.value = this.value;
            corPersonalizada.value = this.value;
            updatePreview();
        });
    });
    
    // === Cor: picker personalizado ===
    corPersonalizada.addEventListener('input', function() {
        corHex.value = this.value;
        corRadios.forEach(r => r.checked = false);
        updatePreview();
    });
    
    // === Cor: input hex manual ===
    corHex.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            corPersonalizada.value = this.value;
            corRadios.forEach(r => r.checked = false);
            updatePreview();
        }
    });
    
    // Inicializa preview
    updatePreview();
    descricaoCount.textContent = descricaoInput.value.length;
});
</script>
