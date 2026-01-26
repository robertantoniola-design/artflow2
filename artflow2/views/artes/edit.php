<?php
/**
 * Artes - Editar
 * 
 * Variáveis:
 * - $arte: Objeto Arte para edição
 * - $tags: Tags disponíveis
 * - $arteTags: Tags da arte atual
 */
$currentPage = 'artes';
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
                    <!-- Token CSRF e método PUT -->
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
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
                        <!-- Tempo Médio -->
                        <div class="col-md-6 mb-3">
                            <label for="tempo_medio_horas" class="form-label">
                                Tempo Estimado (horas) <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control <?= has_error('tempo_medio_horas') ? 'is-invalid' : '' ?>"
                                   id="tempo_medio_horas" 
                                   name="tempo_medio_horas" 
                                   value="<?= old('tempo_medio_horas', $arte->getTempoMedioHoras()) ?>"
                                   step="0.5"
                                   min="0.5"
                                   required>
                            <?php if (has_error('tempo_medio_horas')): ?>
                                <div class="invalid-feedback"><?= errors('tempo_medio_horas') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Complexidade -->
                        <div class="col-md-6 mb-3">
                            <label for="complexidade" class="form-label">
                                Complexidade <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?= has_error('complexidade') ? 'is-invalid' : '' ?>"
                                    id="complexidade" 
                                    name="complexidade"
                                    required>
                                <option value="baixa" <?= old('complexidade', $arte->getComplexidade()) === 'baixa' ? 'selected' : '' ?>>
                                    Baixa
                                </option>
                                <option value="media" <?= old('complexidade', $arte->getComplexidade()) === 'media' ? 'selected' : '' ?>>
                                    Média
                                </option>
                                <option value="alta" <?= old('complexidade', $arte->getComplexidade()) === 'alta' ? 'selected' : '' ?>>
                                    Alta
                                </option>
                            </select>
                            <?php if (has_error('complexidade')): ?>
                                <div class="invalid-feedback"><?= errors('complexidade') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Preço de Custo -->
                        <div class="col-md-6 mb-3">
                            <label for="preco_custo" class="form-label">Preço de Custo (R$)</label>
                            <input type="number" 
                                   class="form-control <?= has_error('preco_custo') ? 'is-invalid' : '' ?>"
                                   id="preco_custo" 
                                   name="preco_custo" 
                                   value="<?= old('preco_custo', $arte->getPrecoCusto()) ?>"
                                   step="0.01"
                                   min="0">
                            <?php if (has_error('preco_custo')): ?>
                                <div class="invalid-feedback"><?= errors('preco_custo') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Horas Trabalhadas -->
                        <div class="col-md-6 mb-3">
                            <label for="horas_trabalhadas" class="form-label">Horas Trabalhadas</label>
                            <input type="number" 
                                   class="form-control <?= has_error('horas_trabalhadas') ? 'is-invalid' : '' ?>"
                                   id="horas_trabalhadas" 
                                   name="horas_trabalhadas" 
                                   value="<?= old('horas_trabalhadas', $arte->getHorasTrabalhadas()) ?>"
                                   step="0.5"
                                   min="0">
                            <?php if (has_error('horas_trabalhadas')): ?>
                                <div class="invalid-feedback"><?= errors('horas_trabalhadas') ?></div>
                            <?php endif; ?>
                            <div class="form-text">Total de horas já investidas</div>
                        </div>
                    </div>
                    
                    <!-- Status (só se não vendida) -->
                    <?php if ($arte->getStatus() !== 'vendida'): ?>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>"
                                id="status" 
                                name="status">
                            <option value="disponivel" <?= old('status', $arte->getStatus()) === 'disponivel' ? 'selected' : '' ?>>
                                Disponível para venda
                            </option>
                            <option value="em_producao" <?= old('status', $arte->getStatus()) === 'em_producao' ? 'selected' : '' ?>>
                                Em Produção
                            </option>
                        </select>
                        <?php if (has_error('status')): ?>
                            <div class="invalid-feedback"><?= errors('status') ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                    <div class="mb-4">
                        <label class="form-label">Tags</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php 
                            $selectedTags = old('tags', array_column($arteTags ?? [], 'id'));
                            foreach ($tags as $tag): 
                            ?>
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="tag_<?= $tag['id'] ?>"
                                           name="tags[]"
                                           value="<?= $tag['id'] ?>"
                                           <?= in_array($tag['id'], $selectedTags) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tag_<?= $tag['id'] ?>">
                                        <span class="badge" style="background-color: <?= $tag['cor'] ?>">
                                            <?= e($tag['nome']) ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Informações -->
                    <div class="alert alert-light mb-4">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Criada em: <?= date_br($arte->getDataCadastro()) ?>
                        </small>
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex justify-content-between">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                            </button>
                            <a href="<?= url('/artes/' . $arte->getId()) ?>" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                        
                        <?php if ($arte->getStatus() !== 'vendida'): ?>
                        <form action="<?= url('/artes/' . $arte->getId()) ?>" method="POST" class="d-inline">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" 
                                    class="btn btn-outline-danger"
                                    data-confirm="Tem certeza que deseja excluir esta arte?">
                                <i class="bi bi-trash me-1"></i> Excluir
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
