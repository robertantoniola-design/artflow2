<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ArtFlow - Sistema de Gestão Artística">
    
    <title><?= $titulo ?? 'ArtFlow 2.0' ?> - ArtFlow</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js (para gráficos) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSS Customizado -->
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
    
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <!-- ========================================
         SIDEBAR (MENU LATERAL)
         ======================================== -->
    <aside class="sidebar" id="sidebar">
        <!-- Logo -->
        <div class="sidebar-header">
            <a href="<?= url('/') ?>" class="sidebar-logo">
                <i class="bi bi-palette2"></i>
                <span>ArtFlow</span>
            </a>
            <button class="sidebar-toggle d-lg-none" id="sidebarToggle">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <!-- Menu de Navegação -->
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= url('/') ?>" class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- Artes -->
                <li class="nav-item">
                    <a href="<?= url('/artes') ?>" class="nav-link <?= ($currentPage ?? '') === 'artes' ? 'active' : '' ?>">
                        <i class="bi bi-brush"></i>
                        <span>Artes</span>
                    </a>
                </li>
                
                <!-- Clientes -->
                <li class="nav-item">
                    <a href="<?= url('/clientes') ?>" class="nav-link <?= ($currentPage ?? '') === 'clientes' ? 'active' : '' ?>">
                        <i class="bi bi-people"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                
                <!-- Vendas -->
                <li class="nav-item">
                    <a href="<?= url('/vendas') ?>" class="nav-link <?= ($currentPage ?? '') === 'vendas' ? 'active' : '' ?>">
                        <i class="bi bi-cart3"></i>
                        <span>Vendas</span>
                    </a>
                </li>
                
                <!-- Metas -->
                <li class="nav-item">
                    <a href="<?= url('/metas') ?>" class="nav-link <?= ($currentPage ?? '') === 'metas' ? 'active' : '' ?>">
                        <i class="bi bi-bullseye"></i>
                        <span>Metas</span>
                    </a>
                </li>
                
                <!-- Tags -->
                <li class="nav-item">
                    <a href="<?= url('/tags') ?>" class="nav-link <?= ($currentPage ?? '') === 'tags' ? 'active' : '' ?>">
                        <i class="bi bi-tags"></i>
                        <span>Tags</span>
                    </a>
                </li>
            </ul>
            
            <!-- Separador -->
            <hr class="sidebar-divider">
            
            <!-- Links Secundários -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="<?= url('/vendas/relatorio') ?>" class="nav-link">
                        <i class="bi bi-graph-up"></i>
                        <span>Relatórios</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Footer do Sidebar -->
        <div class="sidebar-footer">
            <button class="btn btn-outline-light btn-sm w-100" id="darkModeToggle">
                <i class="bi bi-moon-stars"></i>
                <span>Modo Escuro</span>
            </button>
        </div>
    </aside>
    
    <!-- ========================================
         CONTEÚDO PRINCIPAL
         ======================================== -->
    <main class="main-content" id="mainContent">
        <!-- Header -->
        <header class="main-header">
            <div class="d-flex align-items-center">
                <!-- Botão Menu Mobile -->
                <button class="btn btn-link d-lg-none me-2" id="menuToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                
                <!-- Breadcrumb / Título -->
                <div>
                    <h1 class="h4 mb-0"><?= $titulo ?? 'Dashboard' ?></h1>
                    <?php if (isset($subtitulo)): ?>
                        <small class="text-muted"><?= $subtitulo ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Busca Global -->
                <div class="search-box d-none d-md-block">
                    <form action="<?= url('/dashboard/busca') ?>" method="GET" class="position-relative">
                        <input type="search" 
                               name="termo" 
                               class="form-control form-control-sm" 
                               placeholder="Buscar..." 
                               id="globalSearch"
                               autocomplete="off">
                        <i class="bi bi-search search-icon"></i>
                    </form>
                    <!-- Resultados da busca (dropdown) -->
                    <div class="search-results" id="searchResults"></div>
                </div>
                
                <!-- Data Atual -->
                <span class="text-muted d-none d-md-inline">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d/m/Y') ?>
                </span>
            </div>
        </header>
        
        <!-- Alertas Flash -->
        <?php if ($flash = flash()): ?>
            <div class="container-fluid">
                <?php if (isset($flash['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= e($flash['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($flash['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= e($flash['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($flash['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= e($flash['warning']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($flash['info'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <?= e($flash['info']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Conteúdo da Página -->
        <div class="container-fluid py-3">
            <?= $content ?? '' ?>
        </div>
    </main>
    
    <!-- ========================================
         SCRIPTS
         ======================================== -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript Customizado -->
    <script src="<?= asset('js/app.js') ?>"></script>
    
    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
