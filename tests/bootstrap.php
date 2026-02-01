<?php
/**
 * ============================================
 * BOOTSTRAP DE TESTES
 * ============================================
 * 
 * Configurações para PHPUnit
 */

// Carrega autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define ambiente de testes
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';

// Carrega .env.testing se existir, senão .env
$envFile = __DIR__ . '/../.env.testing';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../.env';
}

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Inicia sessão para testes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "✅ Bootstrap de testes carregado\n";
