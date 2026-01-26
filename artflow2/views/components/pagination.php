<?php
/**
 * Componente: Paginação
 * 
 * Uso:
 * <?php 
 * include __DIR__ . '/../components/pagination.php';
 * renderPagination($paginaAtual, $totalPaginas, $baseUrl);
 * ?>
 * 
 * Parâmetros:
 * - $paginaAtual: Número da página atual
 * - $totalPaginas: Total de páginas
 * - $baseUrl: URL base (ex: /artes?status=disponivel)
 */

function renderPagination(int $paginaAtual, int $totalPaginas, string $baseUrl = ''): void
{
    if ($totalPaginas <= 1) {
        return;
    }
    
    // Determina separador de query string
    $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
    
    // Quantidade de links visíveis
    $linksVisiveis = 5;
    $metade = floor($linksVisiveis / 2);
    
    // Calcula range de páginas a exibir
    $inicio = max(1, $paginaAtual - $metade);
    $fim = min($totalPaginas, $paginaAtual + $metade);
    
    // Ajusta se estiver no início ou fim
    if ($paginaAtual <= $metade) {
        $fim = min($totalPaginas, $linksVisiveis);
    }
    if ($paginaAtual > $totalPaginas - $metade) {
        $inicio = max(1, $totalPaginas - $linksVisiveis + 1);
    }
    ?>
    
    <nav aria-label="Paginação">
        <ul class="pagination justify-content-center mb-0">
            <!-- Primeira página -->
            <?php if ($paginaAtual > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= e($baseUrl . $separator) ?>pagina=1" title="Primeira">
                        <i class="bi bi-chevron-double-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Anterior -->
            <li class="page-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $paginaAtual > 1 ? e($baseUrl . $separator) . 'pagina=' . ($paginaAtual - 1) : '#' ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            
            <!-- Reticências início -->
            <?php if ($inicio > 1): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            <?php endif; ?>
            
            <!-- Números das páginas -->
            <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($baseUrl . $separator) ?>pagina=<?= $i ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <!-- Reticências fim -->
            <?php if ($fim < $totalPaginas): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            <?php endif; ?>
            
            <!-- Próxima -->
            <li class="page-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $paginaAtual < $totalPaginas ? e($baseUrl . $separator) . 'pagina=' . ($paginaAtual + 1) : '#' ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
            
            <!-- Última página -->
            <?php if ($paginaAtual < $totalPaginas): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= e($baseUrl . $separator) ?>pagina=<?= $totalPaginas ?>" title="Última">
                        <i class="bi bi-chevron-double-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <p class="text-center text-muted small mt-2 mb-0">
        Página <?= $paginaAtual ?> de <?= $totalPaginas ?>
    </p>
    <?php
}
