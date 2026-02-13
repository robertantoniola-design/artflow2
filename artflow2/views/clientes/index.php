<?php
/**
 * VIEW: Listagem de Clientes
 * GET /clientes
 * 
 * MELHORIA 1 (13/02/2026): Paginação com 12 itens por página
 * MELHORIA 3 (13/02/2026): Exibe localização nos cards
 * 
 * Variáveis esperadas:
 * - $clientes: array de objetos Cliente
 * - $paginacao: array com dados de paginação (OPCIONAL - para compatibilidade)
 * - $filtros: array com filtros aplicados
 * - $total: total de registros
 */
$currentPage = 'clientes';

// Compatibilidade: se não houver paginação, cria estrutura padrão
$paginacao = $paginacao ?? [
    'total'        => $total ?? count($clientes ?? []),
    'porPagina'    => 12,
    'paginaAtual'  => 1,
    'totalPaginas' => 1,
    'temAnterior'  => false,
    'temProxima'   => false
];
?>

<!-- Header da Página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-people text-primary"></i> Clientes
        </h2>
        <p class="text-muted mb-0">
            <?php if ($paginacao['total'] > 0): ?>
                <?= $paginacao['total'] ?> cliente<?= $paginacao['total'] > 1 ? 's' : '' ?> cadastrado<?= $paginacao['total'] > 1 ? 's' : '' ?>
            <?php else: ?>
                Gerencie sua base de clientes
            <?php endif; ?>
        </p>
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
                           placeholder="Buscar por nome, email, telefone ou cidade..."
                           value="<?= e($filtros['termo'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <?php if (!empty($filtros['termo'])): ?>
                        <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary" title="Limpar busca">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
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
                    <br>
                    <a href="<?= url('/clientes') ?>" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="bi bi-arrow-left"></i> Ver todos os clientes
                    </a>
                <?php else: ?>
                    Comece cadastrando seu primeiro cliente.
                    <br>
                    <a href="<?= url('/clientes/criar') ?>" class="btn btn-primary btn-sm mt-2">
                        <i class="bi bi-plus-lg"></i> Novo Cliente
                    </a>
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <!-- Grid de Clientes -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($clientes as $cliente): ?>
            <div class="col">
                <div class="card h-100 hover-shadow">
                    <div class="card-body">
                        <!-- Nome e Empresa -->
                        <h5 class="card-title mb-1">
                            <a href="<?= url("/clientes/{$cliente->getId()}") ?>" class="text-decoration-none">
                                <?= e($cliente->getNome()) ?>
                            </a>
                        </h5>
                        <?php if ($cliente->getEmpresa()): ?>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-building"></i> <?= e($cliente->getEmpresa()) ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Contatos -->
                        <div class="small">
                            <?php if ($cliente->getEmail()): ?>
                                <div class="text-truncate mb-1">
                                    <i class="bi bi-envelope text-muted"></i>
                                    <a href="mailto:<?= e($cliente->getEmail()) ?>" class="text-decoration-none">
                                        <?= e($cliente->getEmail()) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($cliente->getTelefone()): ?>
                                <div class="mb-1">
                                    <i class="bi bi-telephone text-muted"></i>
                                    <a href="tel:<?= e($cliente->getTelefone()) ?>" class="text-decoration-none">
                                        <?= e($cliente->getTelefoneFormatado()) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($cliente->getCidade() || $cliente->getEstado()): ?>
                                <div class="text-muted">
                                    <i class="bi bi-geo-alt"></i>
                                    <?= e($cliente->getLocalizacao()) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Footer com Ações -->
                    <div class="card-footer bg-transparent border-top-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i>
                                <?= date_br($cliente->getCreatedAt()) ?>
                            </small>
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
                                        onclick="confirmarExclusao(<?= $cliente->getId() ?>, '<?= e(addslashes($cliente->getNome())) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- ==========================================
         PAGINAÇÃO (só exibe se houver mais de 1 página)
         ========================================== -->
    <?php if ($paginacao['totalPaginas'] > 1): ?>
        <nav aria-label="Navegação de páginas" class="mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Info da Paginação -->
                <small class="text-muted">
                    Página <?= $paginacao['paginaAtual'] ?> de <?= $paginacao['totalPaginas'] ?>
                    (<?= $paginacao['total'] ?> clientes)
                </small>
                
                <!-- Controles de Paginação -->
                <ul class="pagination pagination-sm mb-0">
                    <!-- Primeira Página -->
                    <li class="page-item <?= !$paginacao['temAnterior'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= url('/clientes?' . http_build_query(array_merge($filtros, ['pagina' => 1]))) ?>"
                           title="Primeira página">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Página Anterior -->
                    <li class="page-item <?= !$paginacao['temAnterior'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= url('/clientes?' . http_build_query(array_merge($filtros, ['pagina' => $paginacao['paginaAtual'] - 1]))) ?>"
                           title="Página anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // Calcula range de páginas a exibir (máximo 5)
                    $inicio = max(1, $paginacao['paginaAtual'] - 2);
                    $fim = min($paginacao['totalPaginas'], $paginacao['paginaAtual'] + 2);
                    
                    // Ajusta para sempre mostrar 5 páginas se possível
                    if ($fim - $inicio < 4 && $paginacao['totalPaginas'] >= 5) {
                        if ($inicio == 1) {
                            $fim = min(5, $paginacao['totalPaginas']);
                        } else {
                            $inicio = max(1, $paginacao['totalPaginas'] - 4);
                        }
                    }
                    ?>
                    
                    <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                        <li class="page-item <?= $i == $paginacao['paginaAtual'] ? 'active' : '' ?>">
                            <a class="page-link" 
                               href="<?= url('/clientes?' . http_build_query(array_merge($filtros, ['pagina' => $i]))) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Próxima Página -->
                    <li class="page-item <?= !$paginacao['temProxima'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= url('/clientes?' . http_build_query(array_merge($filtros, ['pagina' => $paginacao['paginaAtual'] + 1]))) ?>"
                           title="Próxima página">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    
                    <!-- Última Página -->
                    <li class="page-item <?= !$paginacao['temProxima'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="<?= url('/clientes?' . http_build_query(array_merge($filtros, ['pagina' => $paginacao['totalPaginas']]))) ?>"
                           title="Última página">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>
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
                    Esta ação não pode ser desfeita.
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

<!-- Script para Modal de Exclusão -->
<script>
function confirmarExclusao(id, nome) {
    document.getElementById('nomeClienteExcluir').textContent = nome;
    document.getElementById('formExcluir').action = '<?= url('/clientes/') ?>' + id;
    
    var modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
    modal.show();
}
</script>

<style>
/* Efeito hover nos cards */
.hover-shadow {
    transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}
</style>