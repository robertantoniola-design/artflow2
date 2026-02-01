<?php
/**
 * ============================================
 * AUTOLOADER MANUAL - SEM COMPOSER
 * ============================================
 * 
 * Este arquivo substitui o autoloader do Composer
 * para instalações onde o Composer não está disponível.
 */

// ==========================================
// 1. AUTOLOADER PSR-4 PARA NAMESPACE App\
// ==========================================
spl_autoload_register(function ($class) {
    // Prefixo do namespace
    $prefix = 'App\\';
    
    // Diretório base das classes
    $base_dir = __DIR__ . '/../src/';
    
    // Verifica se a classe usa o prefixo
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Não é do namespace App\, ignora
        return;
    }
    
    // Remove o prefixo e converte namespace para caminho
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Carrega o arquivo se existir
    if (file_exists($file)) {
        require $file;
    }
});

// ==========================================
// 2. CARREGA HELPERS (funções globais)
// ==========================================
$helpersFile = __DIR__ . '/../src/Helpers/functions.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
}
