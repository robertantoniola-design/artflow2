<?php
/**
 * VIEW: Criar Arte
 * GET /artes/criar
 * 
 * Variáveis:
 * - $tags: Tags disponíveis para seleção
 * - $complexidades: Lista de níveis de complexidade
 * - $statusList: Lista de status disponíveis
 * 
 * CORREÇÕES Fase 1 (15/02/2026):
 * - Status dropdown agora inclui todos os 4 status: disponivel, em_producao, vendida, reservada
 * - Usa variável $statusList do controller (dinâmico) com fallback hardcoded
 * - Token CSRF padronizado para _token
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
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
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
                               maxlength="150"
                               required
                               autofocus>
                        <?php if (has_error('nome')): ?>
                            <div class="invalid-feedback"><?= errors('nome') ?></div>
                        <?php endif; ?>
                        <small class="text-muted">3 a 150 caracteres</small>
                    </div>
                    
                    <!-- Descrição -->
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control <?= has_error('descricao') ? 'is-invalid' : '' ?>"
                                  id="descricao" 
                                  name="descricao" 
                                  rows="3"
                                  placeholder="Detalhes sobre a arte..."><?= old('descricao') ?></textarea>
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
                                   value="<?= old('tempo_medio_horas') ?>"
                                   min="0"
                                   step="0.5"
                                   placeholder="Ex: 10">
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
                                <option value="baixa" <?= old('complexidade') === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                                <option value="media" <?= old('complexidade') === 'media' ? 'selected' : '' ?>>Média</option>
                                <option value="alta" <?= old('complexidade') === 'alta' ? 'selected' : '' ?>>Alta</option>
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
                                       value="<?= old('preco_custo', '0.00') ?>"
                                       min="0"
                                       step="0.01"
                                       placeholder="0,00">
                            </div>
                            <?php if (has_error('preco_custo')): ?>
                                <div class="invalid-feedback"><?= errors('preco_custo') ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Custo de materiais</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Horas Trabalhadas -->
                        <div class="col-md-6 mb-3">
                            <label for="horas_trabalhadas" class="form-label">Horas Já Trabalhadas</label>
                            <input type="number" 
                                   class="form-control <?= has_error('horas_trabalhadas') ? 'is-invalid' : '' ?>"
                                   id="horas_trabalhadas" 
                                   name="horas_trabalhadas" 
                                   value="<?= old('horas_trabalhadas', '0') ?>"
                                   min="0"
                                   step="0.5"
                                   placeholder="0">
                            <?php if (has_error('horas_trabalhadas')): ?>
                                <div class="invalid-feedback"><?= errors('horas_trabalhadas') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- CORREÇÃO Fase 1: Status dropdown com TODOS os 4 status -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status Inicial</label>
                            <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>"
                                    id="status" 
                                    name="status">
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
                                            <?= old('status', 'disponivel') === $valor ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (has_error('status')): ?>
                                <div class="invalid-feedback"><?= errors('status') ?></div>
                            <?php endif; ?>
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
                                               <?= in_array($tag->getId(), old('tags', [])) ? 'checked' : '' ?>>
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
                        <a href="<?= url('/artes') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Salvar Arte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>