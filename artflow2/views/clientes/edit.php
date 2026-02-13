<?php
/**
 * VIEW: Editar Cliente
 * GET /clientes/{id}/editar
 * 
 * MELHORIA 3: Adicionados campos de endereço, cidade, estado e observações
 * Data: 13/02/2026
 * 
 * Variáveis esperadas:
 * - $cliente: objeto Cliente com dados atuais
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
            <i class="bi bi-person-gear text-primary"></i> Editar Cliente
        </h2>
        <p class="text-muted mb-0"><?= e($cliente->getNome()) ?></p>
    </div>
    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- Formulário -->
<div class="card">
    <div class="card-body">
        <form action="<?= url("/clientes/{$cliente->getId()}") ?>" method="POST">
            <input type="hidden" name="_method" value="PUT">
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
                           value="<?= old('nome', $cliente->getNome()) ?>"
                           maxlength="150"
                           required>
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
                           value="<?= old('empresa', $cliente->getEmpresa()) ?>"
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
                           value="<?= old('email', $cliente->getEmail()) ?>"
                           placeholder="email@exemplo.com"
                           maxlength="150">
                    <?php if (has_error('email')): ?>
                        <div class="invalid-feedback"><?= errors('email') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Telefone -->
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="tel" 
                           name="telefone" 
                           id="telefone"
                           class="form-control <?= has_error('telefone') ? 'is-invalid' : '' ?>" 
                           value="<?= old('telefone', $cliente->getTelefone()) ?>"
                           placeholder="(00) 00000-0000"
                           data-mask="telefone">
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
                           value="<?= old('endereco', $cliente->getEndereco()) ?>"
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
                           value="<?= old('cidade', $cliente->getCidade()) ?>"
                           placeholder="Nome da cidade"
                           maxlength="100">
                    <?php if (has_error('cidade')): ?>
                        <div class="invalid-feedback"><?= errors('cidade') ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Estado (UF) -->
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <?php 
                    // Pega o valor atual: prioriza old(), depois valor do banco
                    $estadoAtual = old('estado', $cliente->getEstado());
                    ?>
                    <select name="estado" 
                            id="estado"
                            class="form-select <?= has_error('estado') ? 'is-invalid' : '' ?>">
                        <option value="">Selecione...</option>
                        <?php foreach ($ufs as $sigla => $nome): ?>
                            <option value="<?= $sigla ?>" 
                                    <?= $estadoAtual === $sigla ? 'selected' : '' ?>>
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
                              placeholder="Anotações sobre o cliente, preferências, histórico de contato..."><?= old('observacoes', $cliente->getObservacoes()) ?></textarea>
                    <?php if (has_error('observacoes')): ?>
                        <div class="invalid-feedback"><?= errors('observacoes') ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ==========================================
                 INFO DE CADASTRO
                 ========================================== -->
            <div class="mt-4 p-3 bg-light rounded">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Cadastrado em <?= datetime_br($cliente->getCreatedAt()) ?>
                    <?php if ($cliente->getUpdatedAt() && $cliente->getUpdatedAt() !== $cliente->getCreatedAt()): ?>
                        | Última atualização: <?= datetime_br($cliente->getUpdatedAt()) ?>
                    <?php endif; ?>
                </small>
            </div>
            
            <!-- ==========================================
                 BOTÕES DE AÇÃO
                 ========================================== -->
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Salvar Alterações
                </button>
                <a href="<?= url("/clientes/{$cliente->getId()}") ?>" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     SCRIPT: Máscara de Telefone
     ========================================== -->
<script>
document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    // Limita a 11 dígitos (DDD + 9 dígitos)
    if (value.length > 11) value = value.substring(0, 11);
    
    // Aplica máscara: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
    if (value.length > 6) {
        value = '(' + value.substring(0,2) + ') ' + value.substring(2,7) + '-' + value.substring(7);
    } else if (value.length > 2) {
        value = '(' + value.substring(0,2) + ') ' + value.substring(2);
    } else if (value.length > 0) {
        value = '(' + value;
    }
    
    e.target.value = value;
});
</script>
