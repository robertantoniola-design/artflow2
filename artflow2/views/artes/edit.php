<?php
/**
 * VIEW: Editar Arte
 * GET /artes/{id}/editar
 * 
 * Variáveis:
 * - $arte: Objeto Arte para edição
 * - $tags: Tags disponíveis
 * - $arteTags: IDs das tags da arte atual
 * - $complexidades: Lista de complexidades (do controller)
 * - $statusList: Lista de status (do controller)
 * 
 * CORREÇÕES Fase 1 (15/02/2026):
 * - Status dropdown agora inclui todos os 4 status incluindo "reservada"
 * - Usa variável $statusList do controller com fallback hardcoded
 * - maxlength nome atualizado para 150 (consistente com VARCHAR(150))
 * - Token CSRF padronizado para _token
 */
$currentPage = 'artes';

// Obtém IDs das tags da arte (para pré-selecionar)
$arteTagIds = [];
if (!empty($arteTags)) {
    foreach ($arteTags as $tag) {
        $arteTagIds[] = is_object($tag) ? $tag->getId() : $tag;
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/artes') ?>">Artes</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/artes/' . $arte->getId()) ?>"><?= e($arte->getNome()) ?></a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Arte
                </h5>
                <?php
                // CORREÇÃO: Incluído 'reservada' no match de status
                $statusClass = match($arte->getStatus()) {
                    'disponivel'  => 'success',
                    'em_producao' => 'warning',
                    'vendida'     => 'info',
                    'reservada'   => 'primary',
                    default       => 'secondary'
                };
                ?>
                <span class="badge bg-<?= $statusClass ?>">
                    <?= ucfirst(str_replace('_', ' ', $arte->getStatus())) ?>
                </span>
            </div>
            
            <!-- [MELHORIA 4] JavaScript para preview de imagem + toggle remoção -->
<script>
function previewImagem(input) {
    const container = document.getElementById('preview-container');
    const preview = document.getElementById('preview-imagem');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 2 * 1024 * 1024;
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!tiposPermitidos.includes(file.type)) {
            alert('Formato não suportado. Use JPG, PNG ou WEBP.');
            input.value = '';
            container.classList.add('d-none');
            return;
        }
        
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
            alert('A imagem tem ' + sizeMB + 'MB. O máximo é 2MB.');
            input.value = '';
            container.classList.add('d-none');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        container.classList.add('d-none');
    }
}

function limparPreview() {
    const input = document.getElementById('imagem');
    const container = document.getElementById('preview-container');
    input.value = '';
    container.classList.add('d-none');
}

/**
 * Quando "Remover imagem" é marcado, desabilita o campo de upload
 * (não faz sentido enviar nova imagem E pedir remoção ao mesmo tempo)
 */
function toggleUploadField(checkbox) {
    const uploadField = document.getElementById('upload-field');
    const inputFile = document.getElementById('imagem');
    const previewContainer = document.getElementById('preview-container');
    
    if (checkbox.checked) {
        // Desabilita upload e limpa seleção
        uploadField.style.opacity = '0.5';
        inputFile.disabled = true;
        inputFile.value = '';
        previewContainer.classList.add('d-none');
    } else {
        // Reabilita upload
        uploadField.style.opacity = '1';
        inputFile.disabled = false;
    }
}
</script>
            
            <div class="card-body">
                <form action="<?= url('/artes/' . $arte->getId()) ?>" method="POST" enctype="multipart/form-data" id="formArte">
                    <!-- Token CSRF e método PUT -->
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <!-- Nome -->
                    <div class="mb-3">
                        <label for="nome" class="form-label">
                            Nome da Arte <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control <?= has_error('nome') ? 'is-invalid' : '' ?>"
                               id="nome" 
                               name="nome" 
                               value="<?= old('nome', $arte->getNome()) ?>"
                               maxlength="150"
                               required>
                        <?php if (has_error('nome')): ?>
                            <div class="invalid-feedback"><?= errors('nome') ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Descrição -->
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control <?= has_error('descricao') ? 'is-invalid' : '' ?>"
                                  id="descricao" 
                                  name="descricao" 
                                  rows="3"><?= old('descricao', $arte->getDescricao()) ?></textarea>
                        <?php if (has_error('descricao')): ?>
                            <div class="invalid-feedback"><?= errors('descricao') ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <!-- Tempo Médio Estimado -->
                        <div class="col-md-4 mb-3">
                            <label for="tempo_medio_horas" class="form-label">Tempo Estimado (h)</label>
                            <input type="number" 
                                   class="form-control <?= has_error('tempo_medio_horas') ? 'is-invalid' : '' ?>"
                                   id="tempo_medio_horas" 
                                   name="tempo_medio_horas" 
                                   value="<?= old('tempo_medio_horas', $arte->getTempoMedioHoras()) ?>"
                                   min="0"
                                   step="0.5">
                            <?php if (has_error('tempo_medio_horas')): ?>
                                <div class="invalid-feedback"><?= errors('tempo_medio_horas') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Complexidade -->
                        <div class="col-md-4 mb-3">
                            <label for="complexidade" class="form-label">
                                Complexidade <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?= has_error('complexidade') ? 'is-invalid' : '' ?>"
                                    id="complexidade" 
                                    name="complexidade"
                                    required>
                                <option value="">Selecione...</option>
                                <option value="baixa" <?= old('complexidade', $arte->getComplexidade()) === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                                <option value="media" <?= old('complexidade', $arte->getComplexidade()) === 'media' ? 'selected' : '' ?>>Média</option>
                                <option value="alta" <?= old('complexidade', $arte->getComplexidade()) === 'alta' ? 'selected' : '' ?>>Alta</option>
                            </select>
                            <?php if (has_error('complexidade')): ?>
                                <div class="invalid-feedback"><?= errors('complexidade') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Preço de Custo -->
                        <div class="col-md-4 mb-3">
                            <label for="preco_custo" class="form-label">Preço de Custo</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" 
                                       class="form-control <?= has_error('preco_custo') ? 'is-invalid' : '' ?>"
                                       id="preco_custo" 
                                       name="preco_custo" 
                                       value="<?= old('preco_custo', $arte->getPrecoCusto()) ?>"
                                       min="0"
                                       step="0.01">
                            </div>
                            <?php if (has_error('preco_custo')): ?>
                                <div class="invalid-feedback"><?= errors('preco_custo') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Horas Trabalhadas -->
                        <div class="col-md-6 mb-3">
                            <label for="horas_trabalhadas" class="form-label">Horas Trabalhadas</label>
                            <input type="number" 
                                   class="form-control <?= has_error('horas_trabalhadas') ? 'is-invalid' : '' ?>"
                                   id="horas_trabalhadas" 
                                   name="horas_trabalhadas" 
                                   value="<?= old('horas_trabalhadas', $arte->getHorasTrabalhadas()) ?>"
                                   min="0"
                                   step="0.5">
                            <?php if (has_error('horas_trabalhadas')): ?>
                                <div class="invalid-feedback"><?= errors('horas_trabalhadas') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- CORREÇÃO Fase 1: Status com TODOS os 4 valores incluindo "reservada" -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>"
                                    id="status" 
                                    name="status"
                                    <?= $arte->getStatus() === 'vendida' ? 'disabled' : '' ?>>
                                <?php
                                // Usa $statusList do controller se disponível, senão fallback
                                $listaStatus = $statusList ?? [
                                    'disponivel'  => 'Disponível',
                                    'em_producao' => 'Em Produção',
                                    'vendida'     => 'Vendida',
                                    'reservada'   => 'Reservada'
                                ];
                                foreach ($listaStatus as $valor => $label):
                                ?>
                                    <option value="<?= $valor ?>" 
                                            <?= old('status', $arte->getStatus()) === $valor ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($arte->getStatus() === 'vendida'): ?>
                                <!-- Campo hidden para enviar o valor mesmo com select disabled -->
                                <input type="hidden" name="status" value="vendida">
                                <small class="text-muted">Artes vendidas não podem ter status alterado</small>
                            <?php endif; ?>
                            <?php if (has_error('status')): ?>
                                <div class="invalid-feedback"><?= errors('status') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                        <!-- ============================================ -->
<!-- [MELHORIA 4] Upload de Imagem (Edição)      -->
<!-- ============================================ -->
<div class="mb-3">
    <label for="imagem" class="form-label">
        <i class="bi bi-image"></i> Imagem da Arte
    </label>
    
    <?php if ($arte->getImagem()): ?>
        <!-- ── Imagem atual ── -->
        <div class="mb-2 p-2 border rounded bg-light">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= url('/' . e($arte->getImagem())) ?>" 
                     alt="<?= e($arte->getNome()) ?>" 
                     class="img-thumbnail" 
                     style="max-height: 150px; max-width: 200px;">
                <div>
                    <p class="mb-1 text-muted small">
                        <i class="bi bi-check-circle text-success"></i> Imagem atual
                    </p>
                    <!-- Checkbox para remover imagem existente -->
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="remover_imagem" 
                               name="remover_imagem" 
                               value="1"
                               onchange="toggleUploadField(this)">
                        <label class="form-check-label text-danger small" for="remover_imagem">
                            <i class="bi bi-trash"></i> Remover imagem
                        </label>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Input de arquivo (nova imagem ou substituição) -->
    <div id="upload-field">
        <input type="file" 
               class="form-control <?= has_error('imagem') ? 'is-invalid' : '' ?>" 
               id="imagem" 
               name="imagem" 
               accept=".jpg,.jpeg,.png,.webp"
               onchange="previewImagem(this)">
        
        <?php if (has_error('imagem')): ?>
            <div class="invalid-feedback"><?= errors('imagem') ?></div>
        <?php endif; ?>
        
        <div class="form-text">
            <i class="bi bi-info-circle"></i>
            <?php if ($arte->getImagem()): ?>
                Selecione uma nova imagem para substituir a atual.
            <?php else: ?>
                Formatos: JPG, PNG, WEBP — Tamanho máximo: 2MB
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Preview da nova imagem -->
    <div id="preview-container" class="mt-2 d-none">
        <img id="preview-imagem" 
             src="" 
             alt="Preview" 
             class="img-thumbnail" 
             style="max-height: 200px; max-width: 300px;">
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="limparPreview()">
            <i class="bi bi-x-circle"></i> Cancelar
        </button>
    </div>
</div>

                    <!-- Tags (checkboxes estilizados) -->
                    <div class="mb-4">
                        <label class="form-label">Tags</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($tags)): ?>
                                <?php foreach ($tags as $tag): ?>
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="btn-check" 
                                               name="tags[]" 
                                               value="<?= $tag->getId() ?>"
                                               id="tag_<?= $tag->getId() ?>"
                                               <?= in_array($tag->getId(), old('tags', $arteTagIds)) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-secondary btn-sm" 
                                               for="tag_<?= $tag->getId() ?>"
                                               style="border-color: <?= e($tag->getCor()) ?>; color: <?= e($tag->getCor()) ?>;">
                                            <?php if (method_exists($tag, 'getIcone') && $tag->getIcone()): ?>
                                                <i class="<?= e($tag->getIcone()) ?> me-1"></i>
                                            <?php endif; ?>
                                            <?= e($tag->getNome()) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">
                                    Nenhuma tag cadastrada. 
                                    <a href="<?= url('/tags/criar') ?>">Crie uma tag</a> primeiro.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Botões -->
                    <div class="d-flex justify-content-between">
                        <a href="<?= url('/artes/' . $arte->getId()) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>