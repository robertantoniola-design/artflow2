<?php
/**
 * VIEW: Editar Tag (Melhoria 3 — + Descrição e Ícone)
 * GET /tags/{id}/editar
 * 
 * Variáveis recebidas do Controller:
 * - $tag: Objeto Tag para edição
 * - $cores: array de cores predefinidas (hex => nome)
 * - $icones: array de ícones disponíveis (classe => nome) — MELHORIA 3
 */
$currentPage = 'tags';
$corAtual = $tag->getCor();
$coresPredefinidas = $cores ?? [];
$iconesDisponiveis = $icones ?? [];
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
    <div class="col-lg-7">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Editar Tag</h1>
                <span class="badge" style="<?= $tag->getStyleInline() ?>; font-size: 1rem;">
                    <?php if ($tag->hasIcone()): ?>
                        <i class="<?= e($tag->getIcone()) ?> me-1"></i>
                    <?php endif; ?>
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
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <!-- ==========================================
                         NOME
                         ========================================== -->
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
                                  placeholder="Descreva o uso desta tag... (opcional)"><?= old('descricao', $tag->getDescricao()) ?></textarea>
                        <?php if (has_error('descricao')): ?>
                            <div class="invalid-feedback"><?= errors('descricao') ?></div>
                        <?php endif; ?>
                        <small class="text-muted">
                            <span id="descricaoCount"><?= mb_strlen(old('descricao', $tag->getDescricao()) ?? '') ?></span>/500 caracteres
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
                                <i id="iconePreview" class="<?= $tag->hasIcone() ? e($tag->getIcone()) : 'bi bi-tag' ?>"></i>
                            </span>
                            <select name="icone" 
                                    id="icone" 
                                    class="form-select <?= has_error('icone') ? 'is-invalid' : '' ?>">
                                <option value="">Sem ícone (padrão)</option>
                                <?php foreach ($iconesDisponiveis as $classe => $nomeIcone): ?>
                                    <option value="<?= e($classe) ?>" 
                                            <?= old('icone', $tag->getIcone()) === $classe ? 'selected' : '' ?>>
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
                         COR
                         ========================================== -->
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
                                           class="btn-check corRadio"
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
                        <div class="input-group" style="max-width: 280px;">
                            <span class="input-group-text">
                                <i class="bi bi-palette"></i>
                            </span>
                            <input type="color" 
                                   id="corCustom" 
                                   class="form-control form-control-color" 
                                   value="<?= old('cor', $corAtual) ?>"
                                   title="Cor customizada">
                            <input type="text" 
                                   name="cor" 
                                   id="corHex"
                                   class="form-control <?= has_error('cor') ? 'is-invalid' : '' ?>"
                                   value="<?= old('cor', $corAtual) ?>"
                                   pattern="^#[0-9A-Fa-f]{6}$"
                                   placeholder="#000000">
                        </div>
                        <?php if (has_error('cor')): ?>
                            <div class="text-danger small mt-1"><?= errors('cor') ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- ==========================================
                         PREVIEW
                         ========================================== -->
                    <div class="mb-4">
                        <label class="form-label">Preview</label>
                        <div class="p-3 bg-light rounded">
                            <span id="previewTag" class="badge fs-5" style="<?= $tag->getStyleInline() ?>">
                                <i id="previewIcone" class="<?= $tag->hasIcone() ? e($tag->getIcone()) . ' me-1' : '' ?>"></i>
                                <span id="previewNome"><?= e($tag->getNome()) ?></span>
                            </span>
                            <?php 
                            $descAtual = old('descricao', $tag->getDescricao());
                            ?>
                            <p id="previewDescricao" class="text-muted small mt-2 mb-0" 
                               style="<?= empty($descAtual) ? 'display:none;' : '' ?>">
                                <?= e($descAtual ?? '') ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Salvar Alterações
                        </button>
                        <a href="<?= url('/tags/' . $tag->getId()) ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Card de exclusão (separado do form de edição) -->
        <div class="card mt-4 border-danger">
            <div class="card-body">
                <h6 class="text-danger mb-3">
                    <i class="bi bi-exclamation-triangle"></i> Zona de Perigo
                </h6>
                <p class="text-muted small mb-3">
                    Excluir esta tag removerá todas as associações com artes. Esta ação não pode ser desfeita.
                </p>
                <form action="<?= url('/tags/' . $tag->getId()) ?>" method="POST" class="d-inline"
                      onsubmit="return confirm('Tem certeza que deseja excluir a tag \'<?= e($tag->getNome()) ?>\'?');">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash"></i> Excluir Tag
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     JAVASCRIPT: Preview em tempo real
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const corCustom = document.getElementById('corCustom');
    const corHex = document.getElementById('corHex');
    const previewTag = document.getElementById('previewTag');
    const previewNome = document.getElementById('previewNome');
    const previewIcone = document.getElementById('previewIcone');
    const previewDescricao = document.getElementById('previewDescricao');
    const nomeInput = document.getElementById('nome');
    const descricaoInput = document.getElementById('descricao');
    const iconeSelect = document.getElementById('icone');
    const iconePreview = document.getElementById('iconePreview');
    const descricaoCount = document.getElementById('descricaoCount');
    const radios = document.querySelectorAll('.corRadio');
    
    // === Atualiza cor do preview ===
    function updatePreview() {
        const cor = corHex.value || '#6c757d';
        previewTag.style.backgroundColor = cor;
        
        // Contraste automático
        const hex = cor.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        previewTag.style.color = luminance > 0.5 ? '#000000' : '#ffffff';
    }
    
    // === Nome ===
    nomeInput.addEventListener('input', function() {
        previewNome.textContent = this.value || 'Tag';
    });
    
    // === Descrição: contador + preview ===
    descricaoInput.addEventListener('input', function() {
        descricaoCount.textContent = this.value.length;
        if (this.value.trim()) {
            previewDescricao.textContent = this.value;
            previewDescricao.style.display = 'block';
        } else {
            previewDescricao.style.display = 'none';
        }
    });
    
    // === Ícone ===
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
    
    // === Cor: picker -> hex ===
    corCustom.addEventListener('input', function() {
        corHex.value = this.value;
        radios.forEach(r => r.checked = false);
        updatePreview();
    });
    
    // === Cor: hex -> picker ===
    corHex.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            corCustom.value = this.value;
            radios.forEach(r => r.checked = false);
            updatePreview();
        }
    });
    
    // === Cor: radio buttons ===
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            corHex.value = this.value;
            corCustom.value = this.value;
            updatePreview();
        });
    });
    
    // Inicializa
    updatePreview();
});
</script>
