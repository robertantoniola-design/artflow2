<?php
/**
 * VIEW: Editar Arte
 * GET /artes/{id}/editar
 * 
 * Variáveis:
 * - $arte: Objeto Arte para edição
 * - $tags: Tags disponíveis
 * - $arteTags: IDs das tags da arte atual
 * 
 * CORREÇÃO (29/01/2026): Campo CSRF padronizado para _token
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
                <span class="badge badge-<?= $arte->getStatus() ?>">
                    <?= ucfirst(str_replace('_', ' ', $arte->getStatus())) ?>
                </span>
            </div>
            <div class="card-body">
                <form action="<?= url('/artes/' . $arte->getId()) ?>" method="POST" id="formArte">
                    <!-- CORREÇÃO: Token CSRF e método PUT padronizados -->
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
                               maxlength="100"
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
                                  rows="3"
                                  maxlength="1000"><?= old('descricao', $arte->getDescricao()) ?></textarea>
                        <?php if (has_error('descricao')): ?>
                            <div class="invalid-feedback"><?= errors('descricao') ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <!-- Tempo Médio (horas) -->
                        <div class="col-md-4 mb-3">
                            <label for="tempo_medio_horas" class="form-label">
                                Tempo Médio (horas) <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control <?= has_error('tempo_medio_horas') ? 'is-invalid' : '' ?>"
                                   id="tempo_medio_horas" 
                                   name="tempo_medio_horas" 
                                   value="<?= old('tempo_medio_horas', $arte->getTempoMedioHoras()) ?>"
                                   min="0.5"
                                   max="1000"
                                   step="0.5"
                                   required>
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
                        
                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>"
                                    id="status" 
                                    name="status"
                                    <?= $arte->getStatus() === 'vendida' ? 'disabled' : '' ?>>
                                <option value="disponivel" <?= old('status', $arte->getStatus()) === 'disponivel' ? 'selected' : '' ?>>
                                    Disponível
                                </option>
                                <option value="em_producao" <?= old('status', $arte->getStatus()) === 'em_producao' ? 'selected' : '' ?>>
                                    Em Produção
                                </option>
                                <?php if ($arte->getStatus() === 'vendida'): ?>
                                    <option value="vendida" selected>Vendida</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($arte->getStatus() === 'vendida'): ?>
                                <small class="text-muted">Artes vendidas não podem ter status alterado</small>
                            <?php endif; ?>
                            <?php if (has_error('status')): ?>
                                <div class="invalid-feedback"><?= errors('status') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tags -->
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
                                            <?= e($tag->getNome()) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">
                                    Nenhuma tag cadastrada. 
                                    <a href="<?= url('/tags/criar') ?>" target="_blank">Criar tag</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                        </button>
                        <a href="<?= url('/artes/' . $arte->getId()) ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <?php if ($arte->getStatus() !== 'vendida'): ?>
                            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#modalExcluir">
                                <i class="bi bi-trash"></i> Excluir
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<?php if ($arte->getStatus() !== 'vendida'): ?>
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a arte <strong><?= e($arte->getNome()) ?></strong>?</p>
                <p class="text-danger mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?= url('/artes/' . $arte->getId()) ?>" method="POST" class="d-inline">
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
<?php endif; ?>
