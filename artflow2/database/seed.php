<?php
/**
 * ============================================
 * EXECUTOR DE SEEDERS
 * ============================================
 * 
 * Uso:
 * php database/seed.php           # Executa todos os seeders
 * php database/seed.php tag       # Executa apenas TagSeeder
 * php database/seed.php demo      # Executa apenas DemoSeeder
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║     🌱 ARTFLOW 2.0 - SEED DATABASE       ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// Ordem de execução
$seedersOrdenados = [
    'TagSeeder.php',
    'MetaSeeder.php',
    'DemoSeeder.php'
];

// Obtém seeder específico (se fornecido)
$seederEspecifico = $argv[1] ?? null;

// Lista de seeders a executar
$seedersParaExecutar = [];

if ($seederEspecifico) {
    // Encontra seeder pelo nome parcial
    foreach ($seedersOrdenados as $seeder) {
        if (stripos($seeder, $seederEspecifico) !== false) {
            $seedersParaExecutar[] = $seeder;
            break;
        }
    }
    
    if (empty($seedersParaExecutar)) {
        echo "❌ Seeder '{$seederEspecifico}' não encontrado.\n";
        echo "\nSeeders disponíveis:\n";
        foreach ($seedersOrdenados as $s) {
            echo "  - " . str_replace('.php', '', $s) . "\n";
        }
        exit(1);
    }
} else {
    $seedersParaExecutar = $seedersOrdenados;
}

// Executa seeders
foreach ($seedersParaExecutar as $seederFile) {
    $path = __DIR__ . '/seeds/' . $seederFile;
    
    if (!file_exists($path)) {
        echo "⚠️  Seeder '{$seederFile}' não existe, pulando...\n\n";
        continue;
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "▶️  Executando: {$seederFile}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    try {
        $seeder = require $path;
        $seeder->run();
    } catch (\Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 SEED CONCLUÍDO!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
