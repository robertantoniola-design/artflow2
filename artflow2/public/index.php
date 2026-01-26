<?php

/**
 * ============================================
 * ARTFLOW 2.0 - PONTO DE ENTRADA ÚNICO
 * ============================================
 * 
 * Todas as requisições passam por aqui (via .htaccess).
 * Este arquivo:
 * 1. Carrega autoloader do Composer
 * 2. Inicializa a aplicação
 * 3. Carrega rotas
 * 4. Executa
 */

// ==========================================
// 1. AUTOLOADER DO COMPOSER
// ==========================================
$autoloader = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloader)) {
    die('
        <h1>❌ Composer não instalado</h1>
        <p>Execute no terminal:</p>
        <pre>cd ' . dirname(__DIR__) . ' && composer install</pre>
    ');
}

require_once $autoloader;

// ==========================================
// 2. INICIALIZA APLICAÇÃO
// ==========================================
use App\Core\Application;

// Caminho base é o diretório pai do public
$basePath = dirname(__DIR__);

$app = new Application($basePath);

// ==========================================
// 3. CARREGA ROTAS
// ==========================================
$router = $app->getRouter();

// Carrega arquivo de definição de rotas
$routesFile = $basePath . '/config/routes.php';

if (file_exists($routesFile)) {
    require_once $routesFile;
}

// ==========================================
// 4. EXECUTA APLICAÇÃO
// ==========================================
$app->run();
