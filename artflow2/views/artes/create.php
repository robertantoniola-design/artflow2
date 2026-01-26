<?php
/**
 * Artes - Criar
 * 
 * Variáveis:
 * - $tags: Tags disponíveis para seleção
 */
$currentPage = 'artes';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/artes') ?>">Artes</a></li>
        <li class="breadcrumb-item active">Nova Arte</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Cadastrar Nova Arte
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= url('/artes') ?>" method="POST" id="formArte">
                    <!-- Token CSRF -->
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    
                    <!-- Nome -->
                    <div class="mb-3">
                        <label for="nome" class="form-label">
                            Nome da Arte <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control <?= has_error('nome') ? 'is-invalid' : '' ?>"
                               id="nome" 
                               name="nome" 
                               value="<?= old('nome') ?>"
                               placeholder="Ex: Retrato em Aquarela"
                               required
                               autofocus>
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
                                  placeholder="Descreva a arte, técnicas utilizadas, materiais..."><?= old('descricao') ?></textarea>
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
                                   value="<?= old('tempo_medio_horas') ?>"
                                   step="0.5"
                                   min="0.5"
                                   placeholder="Ex: 8.5"
                                   required>
                            <?php if (has_error('tempo_medio_horas')): ?>
                                <div class="invalid-feedback"><?= errors('tempo_medio_horas') ?></div>
                            <?php endif; ?>
                            <div class="form-text">Estimativa de horas para concluir</div>
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
                                <option value="">Selecione...</option>
                                <option value="baixa" <?= old('complexidade') === 'baixa' ? 'selected' : '' ?>>
                                    Baixa - Simples, técnicas básicas
                                </option>
                                <option value="media" <?= old('complexidade') === 'media' ? 'selected' : '' ?>>
                                    Média - Técnicas intermediárias
                                </option>
                                <option value="alta" <?= old('complexidade') === 'alta' ? 'selected' : '' ?>>
                                    Alta - Técnicas avançadas, detalhada
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
                                   value="<?= old('preco_custo', '0.00') ?>"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00">
                            <?php if (has_error('preco_custo')): ?>
                                <div class="invalid-feedback"><?= errors('preco_custo') ?></div>
                            <?php endif; ?>
                            <div class="form-text">Custo de materiais e insumos</div>
                        </div>
                        
                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>"
                                    id="status" 
                                    name="status">
                                <option value="disponivel" <?= old('status', 'disponivel') === 'disponivel' ? 'selected' : '' ?>>
                                    Disponível para venda
                                </option>
                                <option value="em_producao" <?= old('status') === 'em_producao' ? 'selected' : '' ?>>
                                    Em Produção
                                </option>
                            </select>
                            <?php if (has_error('status')): ?>
                                <div class="invalid-feedback"><?= errors('status') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                    <div class="mb-4">
                        <label class="form-label">Tags</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php 
                            $selectedTags = old('tags', []);
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
                    
                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Salvar Arte
                        </button>
                        <a href="<?= url('/artes') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
