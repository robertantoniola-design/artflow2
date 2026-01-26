<?php
/**
 * VIEW: Listagem de Clientes
 * GET /clientes
 */
$currentPage = 'clientes';
?>

<!-- Header da Página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-people text-primary"></i> Clientes
        </h2>
        <p class="text-muted mb-0">Gerencie sua base de clientes</p>
    </div>
    <a href="<?= url('/clientes/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Novo Cliente
    </a>
</div>

<!-- Filtros e Busca -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= url('/clientes') ?>" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           name="termo" 
                           class="form-control" 
                           placeholder="Buscar por nome, email ou telefone..."
                           value="<?= e($filtros['termo'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Clientes -->
<?php if (empty($clientes)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-people display-4 text-muted"></i>
            <h5 class="mt-3">Nenhum cliente encontrado</h5>
            <p class="text-muted">
                <?php if (!empty($filtros['termo'])): ?>
                    Não encontramos clientes com o termo "<?= e($filtros['termo']) ?>".
                <?php else: ?>
                    Comece cadastrando seu primeiro cliente.
                <?php endif; ?>
            </p>
            <a href="<?= url('/clientes/criar') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Cadastrar Cliente
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header bg-transparent">
            <span class="text-muted">
                <?= count($clientes) ?> cliente(s) encontrado(s)
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Empresa</th>
                        <th>Cadastro</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td>
                                <a href="<?= url("/clientes/{$cliente->getId()}") ?>" class="fw-medium text-decoration-none">
                                    <?= e($cliente->getNome()) ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($cliente->getEmail()): ?>
                                    <a href="mailto:<?= e($cliente->getEmail()) ?>" class="text-decoration-none">
                                        <?= e($cliente->getEmail()) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cliente->getTelefone()): ?>
                                    <a href="tel:<?= e($cliente->getTelefone()) ?>" class="text-decoration-none">
                                        <?= e($cliente->getTelefoneFormatado()) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($cliente->getEmpresa()) ?: '<span class="text-muted">-</span>' ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date_br($cliente->getCreatedAt()) ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url("/clientes/{$cliente->getId()}") ?>" 
                                       class="btn btn-outline-info" 
                                       title="Ver detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url("/clientes/{$cliente->getId()}/editar") ?>" 
                                       class="btn btn-outline-primary" 
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            title="Excluir"
                                            onclick="confirmarExclusao(<?= $cliente->getId() ?>, '<?= e($cliente->getNome()) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o cliente <strong id="nomeClienteExcluir"></strong>?</p>
                <p class="text-danger small">
                    <i class="bi bi-exclamation-triangle"></i>
                    Esta ação não pode ser desfeita. Vendas associadas perderão a referência ao cliente.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST" class="d-inline">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('nomeClienteExcluir').textContent = nome;
    document.getElementById('formExcluir').action = '<?= url('/clientes') ?>/' + id;
    
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>
