<?php
/**
 * VIEW: Criar Cliente
 * GET /clientes/criar
 * 
 * MELHORIA 3: Adicionados campos de endereço, cidade, estado e observações
 * MELHORIA 6: Atributos HTML5 no telefone + script inline removido (14/02/2026)
 * Data: 13/02/2026
 */
$currentPage = 'clientes';

// Lista de UFs brasileiras para o select de estado
$ufs = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
    'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
];
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-person-plus text-primary"></i> Novo Cliente
        </h2>
        <p class="text-muted mb-0">Cadastre um novo cliente</p>
    </div>
    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- Formulário -->
<div class="card">
    <div class="card-body">
        <form action="<?= url('/clientes') ?>" method="POST" id="formCliente">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            
            <!-- ==========================================
                 SEÇÃO 1: DADOS BÁSICOS
                 ========================================== -->
            <h6 class="text-muted mb-3">
                <i class="bi bi-person"></i> Dados Básicos
            </h6>
            
            <div class="row g-3">
                <!-- Nome (obrigatório) -->
                <div class="col-md-6">
                    <label for="nome" class="form-label">
                        Nome <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           name="nome" 
                           id="nome"
                           class="form-control <?= has_error('nome') ? 'is-invalid' : '' ?>" 
                           value="<?= old('nome') ?>"
                           placeholder="Nome completo do cliente"
                           maxlength="150"
                           required
                           autofocus>
                    <?php if (has_error('nome')): ?>
                        <div class="invalid-feedback"><?= errors('nome') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Empresa -->
                <div class="col-md-6">
                    <label for="empresa" class="form-label">Empresa</label>
                    <input type="text" 
                           name="empresa" 
                           id="empresa"
                           class="form-control <?= has_error('empresa') ? 'is-invalid' : '' ?>" 
                           value="<?= old('empresa') ?>"
                           placeholder="Nome da empresa (opcional)"
                           maxlength="100">
                    <?php if (has_error('empresa')): ?>
                        <div class="invalid-feedback"><?= errors('empresa') ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ==========================================
                 SEÇÃO 2: CONTATO
                 ========================================== -->
            <h6 class="text-muted mb-3 mt-4">
                <i class="bi bi-telephone"></i> Contato
            </h6>
            
            <div class="row g-3">
                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" 
                           value="<?= old('email') ?>"
                           placeholder="email@exemplo.com"
                           maxlength="150">
                    <?php if (has_error('email')): ?>
                        <div class="invalid-feedback"><?= errors('email') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Telefone [MELHORIA 6] Adicionados: pattern, maxlength, minlength, title, autocomplete -->
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="tel" 
                           name="telefone" 
                           id="telefone"
                           class="form-control <?= has_error('telefone') ? 'is-invalid' : '' ?>" 
                           value="<?= old('telefone') ?>"
                           placeholder="(00) 00000-0000"
                           data-mask="telefone"
                           pattern="\(\d{2}\)\s?\d{4,5}-\d{4}"
                           maxlength="15"
                           minlength="14"
                           title="Formato: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX"
                           autocomplete="tel">
                    <?php if (has_error('telefone')): ?>
                        <div class="invalid-feedback"><?= errors('telefone') ?></div>
                    <?php endif; ?>
                    <small class="text-muted">Formato: (00) 00000-0000</small>
                </div>
            </div>
            
            <!-- ==========================================
                 SEÇÃO 3: ENDEREÇO (NOVOS CAMPOS)
                 ========================================== -->
            <h6 class="text-muted mb-3 mt-4">
                <i class="bi bi-geo-alt"></i> Endereço
            </h6>
            
            <div class="row g-3">
                <!-- Endereço (rua, número, complemento) -->
                <div class="col-12">
                    <label for="endereco" class="form-label">Endereço</label>
                    <input type="text" 
                           name="endereco" 
                           id="endereco"
                           class="form-control <?= has_error('endereco') ? 'is-invalid' : '' ?>" 
                           value="<?= old('endereco') ?>"
                           placeholder="Rua, número, complemento"
                           maxlength="255">
                    <?php if (has_error('endereco')): ?>
                        <div class="invalid-feedback"><?= errors('endereco') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Cidade -->
                <div class="col-md-8">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" 
                           name="cidade" 
                           id="cidade"
                           class="form-control <?= has_error('cidade') ? 'is-invalid' : '' ?>" 
                           value="<?= old('cidade') ?>"
                           placeholder="Nome da cidade"
                           maxlength="100">
                    <?php if (has_error('cidade')): ?>
                        <div class="invalid-feedback"><?= errors('cidade') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Estado (UF) -->
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" 
                            id="estado"
                            class="form-select <?= has_error('estado') ? 'is-invalid' : '' ?>">
                        <option value="">Selecione...</option>
                        <?php foreach ($ufs as $sigla => $nome): ?>
                            <option value="<?= $sigla ?>" 
                                    <?= old('estado') === $sigla ? 'selected' : '' ?>>
                                <?= $sigla ?> - <?= $nome ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('estado')): ?>
                        <div class="invalid-feedback"><?= errors('estado') ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ==========================================
                 SEÇÃO 4: OBSERVAÇÕES
                 ========================================== -->
            <h6 class="text-muted mb-3 mt-4">
                <i class="bi bi-chat-left-text"></i> Informações Adicionais
            </h6>
            
            <div class="row g-3">
                <!-- Observações (textarea) -->
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" 
                              id="observacoes"
                              class="form-control <?= has_error('observacoes') ? 'is-invalid' : '' ?>" 
                              rows="3"
                              placeholder="Anotações sobre o cliente, preferências, histórico de contato..."><?= old('observacoes') ?></textarea>
                    <?php if (has_error('observacoes')): ?>
                        <div class="invalid-feedback"><?= errors('observacoes') ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ==========================================
                 BOTÕES DE AÇÃO
                 ========================================== -->
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Salvar Cliente
                </button>
                <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- [MELHORIA 6] Script inline removido — máscara centralizada no app.js -->