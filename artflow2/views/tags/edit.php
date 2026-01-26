<?php
/**
 * View: Editar Tag
 * Formulário de edição de tag existente
 */

// Variáveis: $tag, $coresPredefinidas
$corAtual = $tag->getCor();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/tags">Tags</a></li>
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
            <a href="/tags" class="btn btn-outline-secondary">
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
                <form method="POST" action="/tags/<?= $tag->getId() ?>" id="formTag">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    
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
                               placeholder="Ex: Aquarela, Retrato, Paisagem..."
                               required>
                        <?php if (has_error('nome')): ?>
                            <div class="invalid-feedback"><?= errors('nome') ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cor -->
                    <div class="mb-4">
                        <label class="form-label">Cor da Tag</label>
                        
                        <!-- Cores Predefinidas -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php 
                            $coresPredefinidas = [
                                '#dc3545' => 'Vermelho',
                                '#fd7e14' => 'Laranja', 
                                '#ffc107' => 'Amarelo',
                                '#28a745' => 'Verde',
                                '#20c997' => 'Turquesa',
                                '#17a2b8' => 'Ciano',
                                '#007bff' => 'Azul',
                                '#6f42c1' => 'Roxo',
                                '#e83e8c' => 'Rosa',
                                '#6c757d' => 'Cinza',
                                '#343a40' => 'Escuro',
                                '#795548' => 'Marrom'
                            ];
                            foreach ($coresPredefinidas as $hex => $nome): 
                            ?>
                                <button type="button" 
                                        class="btn-cor <?= $corAtual === $hex ? 'active' : '' ?>" 
                                        style="background-color: <?= $hex ?>;"
                                        data-cor="<?= $hex ?>"
                                        title="<?= $nome ?>">
                                    <?php if ($corAtual === $hex): ?>
                                        <i class="bi bi-check"></i>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Cor Personalizada -->
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <input type="color" 
                                       class="form-control form-control-color" 
                                       id="corPicker" 
                                       value="<?= old('cor', $corAtual) ?>">
                            </div>
                            <div class="col">
                                <input type="text" 
                                       class="form-control form-control-sm" 
                                       id="cor" 
                                       name="cor" 
                                       value="<?= old('cor', $corAtual) ?>"
                                       pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                       placeholder="#000000">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview -->
                    <div class="mb-4">
                        <label class="form-label">Preview</label>
                        <div class="p-3 bg-light rounded">
                            <span class="badge fs-5" id="tagPreview" 
                                  style="background-color: <?= e($corAtual) ?>">
                                <?= e($tag->getNome()) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Salvar Alterações
                        </button>
                        <a href="/tags" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Informações -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informações</h6>
            </div>
            <div class="card-body small">
                <p class="mb-1">
                    <strong>Criada em:</strong> 
                    <?= date('d/m/Y H:i', strtotime($tag->getCreatedAt())) ?>
                </p>
                <?php if ($tag->getUpdatedAt()): ?>
                <p class="mb-0">
                    <strong>Última alteração:</strong>
                    <?= date('d/m/Y H:i', strtotime($tag->getUpdatedAt())) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Zona de Perigo -->
        <div class="card border-danger mt-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Zona de Perigo</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Excluir esta tag irá remover a associação com todas as artes vinculadas.
                </p>
                <button type="button" class="btn btn-outline-danger btn-sm" 
                        data-bs-toggle="modal" data-bs-target="#modalExcluir">
                    <i class="bi bi-trash"></i> Excluir Tag
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a tag 
                    <span class="badge" style="background-color: <?= e($corAtual) ?>">
                        <?= e($tag->getNome()) ?>
                    </span>?
                </p>
                <p class="text-danger mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Esta ação não pode ser desfeita!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="/tags/<?= $tag->getId() ?>/excluir" class="d-inline">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Sim, Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.btn-cor {
    width: 36px;
    height: 36px;
    border: 2px solid transparent;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}
.btn-cor:hover {
    transform: scale(1.15);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.btn-cor.active {
    border-color: #000;
    transform: scale(1.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nomeInput = document.getElementById('nome');
    const corInput = document.getElementById('cor');
    const corPicker = document.getElementById('corPicker');
    const preview = document.getElementById('tagPreview');
    const btnsCor = document.querySelectorAll('.btn-cor');
    
    // Atualizar preview
    function atualizarPreview() {
        preview.textContent = nomeInput.value || 'Tag';
        preview.style.backgroundColor = corInput.value;
    }
    
    // Sincronizar cor
    function sincronizarCor(cor) {
        corInput.value = cor;
        corPicker.value = cor;
        
        // Atualizar botões ativos
        btnsCor.forEach(btn => {
            btn.classList.remove('active');
            btn.innerHTML = '';
            if (btn.dataset.cor === cor) {
                btn.classList.add('active');
                btn.innerHTML = '<i class="bi bi-check"></i>';
            }
        });
        
        atualizarPreview();
    }
    
    // Events
    nomeInput.addEventListener('input', atualizarPreview);
    
    corInput.addEventListener('input', function() {
        sincronizarCor(this.value);
    });
    
    corPicker.addEventListener('input', function() {
        sincronizarCor(this.value);
    });
    
    btnsCor.forEach(btn => {
        btn.addEventListener('click', function() {
            sincronizarCor(this.dataset.cor);
        });
    });
});
</script>
