<?php
/**
 * ============================================
 * ARTFLOW 2.0 - INSTALAÇÃO E VERIFICAÇÃO
 * ============================================
 * 
 * Execute: php install.php
 * 
 * Este script:
 * 1. Verifica requisitos do sistema
 * 2. Cria banco de dados
 * 3. Executa migrations
 * 4. Cria dados iniciais (seeds)
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║         🎨 ARTFLOW 2.0 - INSTALAÇÃO                      ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ============================================
// 1. VERIFICAR REQUISITOS
// ============================================
echo "📋 VERIFICANDO REQUISITOS...\n";
echo "─────────────────────────────\n";

$requisitosOk = true;

// PHP Version
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '8.0.0', '>=');
echo ($phpOk ? '✅' : '❌') . " PHP: {$phpVersion} (mínimo 8.0)\n";
$requisitosOk = $requisitosOk && $phpOk;

// PDO Extension
$pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
echo ($pdoOk ? '✅' : '❌') . " PDO MySQL: " . ($pdoOk ? 'Instalado' : 'NÃO ENCONTRADO') . "\n";
$requisitosOk = $requisitosOk && $pdoOk;

// JSON Extension
$jsonOk = extension_loaded('json');
echo ($jsonOk ? '✅' : '❌') . " JSON: " . ($jsonOk ? 'Instalado' : 'NÃO ENCONTRADO') . "\n";
$requisitosOk = $requisitosOk && $jsonOk;

// Composer autoload
$composerOk = file_exists(__DIR__ . '/vendor/autoload.php');
echo ($composerOk ? '✅' : '❌') . " Composer: " . ($composerOk ? 'Instalado' : 'Execute: composer install') . "\n";
$requisitosOk = $requisitosOk && $composerOk;

// .env file
$envOk = file_exists(__DIR__ . '/.env');
echo ($envOk ? '✅' : '⚠️') . " .env: " . ($envOk ? 'Encontrado' : 'Criando a partir de .env.example...') . "\n";

if (!$envOk && file_exists(__DIR__ . '/.env.example')) {
    copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
    echo "   └─ ✅ .env criado com sucesso\n";
    $envOk = true;
}

// Storage directories
$storageDirs = ['storage/logs', 'storage/cache'];
foreach ($storageDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    $writable = is_writable($path);
    echo ($writable ? '✅' : '❌') . " {$dir}: " . ($writable ? 'Gravável' : 'SEM PERMISSÃO') . "\n";
}

echo "\n";

if (!$requisitosOk) {
    echo "❌ ERRO: Requisitos não atendidos. Corrija os problemas acima.\n\n";
    exit(1);
}

if (!$composerOk) {
    echo "⚠️  Execute 'composer install' primeiro e depois rode este script novamente.\n\n";
    exit(1);
}

// ============================================
// CARREGAR AMBIENTE
// ============================================
require_once __DIR__ . '/vendor/autoload.php';

// Carrega .env
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// ============================================
// 2. CRIAR BANCO DE DADOS
// ============================================
echo "🗄️  VERIFICANDO BANCO DE DADOS...\n";
echo "─────────────────────────────────\n";

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? 'artflow2_db';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    // Conecta sem selecionar banco
    $pdo = new PDO("mysql:host={$host}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verifica se banco existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbname}'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "📦 Criando banco de dados '{$dbname}'...\n";
        $pdo->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Banco criado com sucesso!\n";
    } else {
        echo "✅ Banco '{$dbname}' já existe\n";
    }
    
    // Conecta ao banco
    $pdo->exec("USE `{$dbname}`");
    
} catch (PDOException $e) {
    echo "❌ ERRO de conexão: " . $e->getMessage() . "\n";
    echo "\n   Verifique:\n";
    echo "   - XAMPP/MySQL está rodando?\n";
    echo "   - Credenciais no .env estão corretas?\n\n";
    exit(1);
}

echo "\n";

// ============================================
// 3. EXECUTAR MIGRATIONS
// ============================================
echo "🔄 EXECUTANDO MIGRATIONS...\n";
echo "───────────────────────────\n";

// Cria tabela de controle
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Lista migrations
$migrationsPath = __DIR__ . '/database/migrations';
$files = glob($migrationsPath . '/*.php');
sort($files);

$executadas = 0;
$puladas = 0;

foreach ($files as $file) {
    $migrationName = basename($file);
    
    // Verifica se já foi executada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
    $stmt->execute([$migrationName]);
    
    if ($stmt->fetchColumn() > 0) {
        echo "⏭️  {$migrationName} (já executada)\n";
        $puladas++;
        continue;
    }
    
    echo "▶️  {$migrationName}...";
    
    try {
        // Carrega e executa migration
        $migration = require $file;
        
        // Injeta conexão
        $reflection = new ReflectionClass($migration);
        $constructor = $reflection->getConstructor();
        
        if ($constructor) {
            $db = \App\Core\Database::getInstance();
            $migration = $reflection->newInstanceArgs([$db]);
        }
        
        $migration->up();
        
        // Registra como executada
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationName]);
        
        echo " ✅\n";
        $executadas++;
        
    } catch (Exception $e) {
        echo " ❌ ERRO: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Migrations: {$executadas} executadas, {$puladas} puladas\n\n";

// ============================================
// 4. CRIAR DADOS INICIAIS
// ============================================
echo "🌱 CRIANDO DADOS INICIAIS...\n";
echo "────────────────────────────\n";

// Verifica se já tem dados
$stmt = $pdo->query("SELECT COUNT(*) FROM tags");
$temDados = $stmt->fetchColumn() > 0;

if ($temDados) {
    echo "ℹ️  Banco já possui dados. Pulando seeds.\n";
} else {
    // Tags iniciais
    $tags = [
        ['Aquarela', '#3b82f6'],
        ['Óleo', '#8b5cf6'],
        ['Digital', '#06b6d4'],
        ['Retrato', '#f59e0b'],
        ['Paisagem', '#10b981'],
        ['Abstrato', '#ec4899'],
        ['Encomenda', '#6366f1'],
        ['Favorito', '#ef4444']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO tags (nome, cor) VALUES (?, ?)");
    foreach ($tags as $tag) {
        try {
            $stmt->execute($tag);
        } catch (Exception $e) {}
    }
    echo "✅ Tags iniciais criadas\n";
    
    // Meta do mês atual
    $mesAtual = date('Y-m-01');
    try {
        $stmt = $pdo->prepare("INSERT INTO metas (mes_ano, valor_meta, horas_diarias_ideal, dias_trabalho_semana) VALUES (?, ?, ?, ?)");
        $stmt->execute([$mesAtual, 5000.00, 8, 5]);
        echo "✅ Meta do mês criada (R$ 5.000,00)\n";
    } catch (Exception $e) {}
}

echo "\n";

// ============================================
// 5. RESUMO FINAL
// ============================================
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║         🎉 INSTALAÇÃO CONCLUÍDA!                        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📍 Acesse: http://localhost/artflow2/\n\n";

echo "📋 ESTRUTURA DO SISTEMA:\n";
echo "   ├── /              → Dashboard principal\n";
echo "   ├── /artes         → Gerenciar artes\n";
echo "   ├── /clientes      → Gerenciar clientes\n";
echo "   ├── /vendas        → Registrar vendas\n";
echo "   ├── /metas         → Acompanhar metas\n";
echo "   └── /tags          → Organizar tags\n\n";

echo "📚 PRÓXIMOS PASSOS:\n";
echo "   1. Acesse o sistema no navegador\n";
echo "   2. Cadastre suas primeiras artes\n";
echo "   3. Defina suas metas mensais\n";
echo "   4. Comece a registrar vendas!\n\n";

echo "🔧 COMANDOS ÚTEIS:\n";
echo "   php install.php        → Reinstalar sistema\n";
echo "   php database/migrate.php       → Executar migrations\n";
echo "   php database/migrate.php fresh → Resetar banco\n\n";

echo "Bom trabalho! 🎨\n\n";
