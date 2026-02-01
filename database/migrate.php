<?php
/**
 * ============================================
 * ARTFLOW 2.0 - EXECUTOR DE MIGRATIONS
 * ============================================
 * 
 * Uso via terminal (CMD no Windows):
 * 
 *   php database/migrate.php           # Executa migrations pendentes
 *   php database/migrate.php fresh     # Apaga tudo e recria (CUIDADO!)
 *   php database/migrate.php rollback  # Reverte última migration
 *   php database/migrate.php status    # Mostra status das migrations
 *   php database/migrate.php reset     # Reverte TODAS as migrations
 * 
 * IMPORTANTE: Execute a partir da pasta raiz do projeto!
 *   cd C:\xampp\htdocs\artflow2
 *   php database/migrate.php
 */

// Carrega autoloader
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("❌ Execute 'composer install' primeiro!\n");
}
require_once $autoload;

use App\Core\Database;

// ============================================
// CARREGA VARIÁVEIS DE AMBIENTE
// ============================================
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignora comentários e linhas vazias
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
} else {
    die("❌ Arquivo .env não encontrado!\n");
}

// ============================================
// CONFIGURAÇÕES
// ============================================
$migrationsPath = __DIR__ . '/migrations';

// ============================================
// OBTÉM COMANDO
// ============================================
$command = $argv[1] ?? 'up';

// ============================================
// BANNER
// ============================================
echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║     🎨 ARTFLOW 2.0 - MIGRATIONS        ║\n";
echo "╚════════════════════════════════════════╝\n\n";

// ============================================
// EXECUTA COMANDO
// ============================================
try {
    $db = Database::getInstance();
    createMigrationsTable($db);
    
    switch ($command) {
        case 'fresh':
            echo "⚠️  ATENÇÃO: Isso vai APAGAR TODOS OS DADOS!\n";
            echo "Digite 'yes' para confirmar: ";
            $handle = fopen("php://stdin", "r");
            $confirmation = trim(fgets($handle));
            fclose($handle);
            
            if ($confirmation === 'yes') {
                dropAllTables($db);
                runMigrations($db, $migrationsPath);
            } else {
                echo "❌ Operação cancelada.\n";
            }
            break;
            
        case 'rollback':
            rollbackLastMigration($db, $migrationsPath);
            break;
            
        case 'reset':
            echo "⚠️  ATENÇÃO: Isso vai REVERTER TODAS as migrations!\n";
            echo "Digite 'yes' para confirmar: ";
            $handle = fopen("php://stdin", "r");
            $confirmation = trim(fgets($handle));
            fclose($handle);
            
            if ($confirmation === 'yes') {
                resetMigrations($db, $migrationsPath);
            } else {
                echo "❌ Operação cancelada.\n";
            }
            break;
            
        case 'status':
            showStatus($db, $migrationsPath);
            break;
            
        case 'up':
        default:
            runMigrations($db, $migrationsPath);
            break;
    }
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    exit(1);
}

echo "\n";
exit(0);

// ============================================
// FUNÇÕES
// ============================================

/**
 * Cria tabela de controle de migrations (se não existir)
 */
function createMigrationsTable(Database $db): void
{
    $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `batch` INT NOT NULL DEFAULT 1,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->getConnection()->exec($sql);
}

/**
 * Executa migrations pendentes
 */
function runMigrations(Database $db, string $path): void
{
    echo "🚀 Executando migrations...\n\n";
    
    // Lista arquivos de migration
    $files = glob($path . '/*.php');
    sort($files); // Ordem alfabética/numérica
    
    if (empty($files)) {
        echo "ℹ️  Nenhuma migration encontrada em: {$path}\n";
        return;
    }
    
    // Obtém próximo batch
    $batch = getNextBatch($db);
    $executed = 0;
    
    foreach ($files as $file) {
        $migrationName = basename($file);
        
        // Verifica se já foi executada
        if (wasExecuted($db, $migrationName)) {
            echo "⏭️  Pulando: {$migrationName} (já executada)\n";
            continue;
        }
        
        echo "▶️  Executando: {$migrationName}...\n";
        
        try {
            // Carrega e executa migration
            $migration = require $file;
            
            // Verifica se tem método up()
            if (!method_exists($migration, 'up')) {
                throw new \Exception("Migration não tem método up()");
            }
            
            $migration->up();
            
            // Registra como executada
            markAsExecuted($db, $migrationName, $batch);
            
            echo "   ✅ Concluída!\n";
            $executed++;
            
        } catch (\Exception $e) {
            echo "   ❌ ERRO: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    echo "\n";
    if ($executed > 0) {
        echo "🎉 {$executed} migration(s) executada(s) com sucesso!\n";
    } else {
        echo "ℹ️  Nenhuma migration pendente.\n";
    }
}

/**
 * Reverte última migration
 */
function rollbackLastMigration(Database $db, string $path): void
{
    echo "⏪ Revertendo última migration...\n\n";
    
    // Busca última migration
    $pdo = $db->getConnection();
    $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
    $lastMigration = $stmt->fetchColumn();
    
    if (!$lastMigration) {
        echo "ℹ️  Nenhuma migration para reverter.\n";
        return;
    }
    
    $file = $path . '/' . $lastMigration;
    
    if (!file_exists($file)) {
        echo "❌ Arquivo não encontrado: {$lastMigration}\n";
        return;
    }
    
    echo "▶️  Revertendo: {$lastMigration}...\n";
    
    try {
        $migration = require $file;
        
        if (method_exists($migration, 'down')) {
            $migration->down();
        }
        
        // Remove registro
        $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$lastMigration]);
        
        echo "   ✅ Revertida com sucesso!\n";
        
    } catch (\Exception $e) {
        echo "   ❌ ERRO: " . $e->getMessage() . "\n";
        throw $e;
    }
}

/**
 * Reverte TODAS as migrations
 */
function resetMigrations(Database $db, string $path): void
{
    echo "🔄 Revertendo TODAS as migrations...\n\n";
    
    $pdo = $db->getConnection();
    
    // Busca todas as migrations executadas (ordem reversa)
    $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC");
    $migrations = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    foreach ($migrations as $migrationName) {
        $file = $path . '/' . $migrationName;
        
        if (!file_exists($file)) {
            echo "⚠️  Arquivo não encontrado: {$migrationName}\n";
            continue;
        }
        
        echo "▶️  Revertendo: {$migrationName}...\n";
        
        try {
            $migration = require $file;
            
            if (method_exists($migration, 'down')) {
                $migration->down();
            }
            
            $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
            $stmt->execute([$migrationName]);
            
            echo "   ✅ OK\n";
            
        } catch (\Exception $e) {
            echo "   ❌ ERRO: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Todas as migrations foram revertidas!\n";
}

/**
 * Mostra status das migrations
 */
function showStatus(Database $db, string $path): void
{
    echo "📊 Status das Migrations\n";
    echo str_repeat("─", 60) . "\n\n";
    
    $pdo = $db->getConnection();
    
    // Lista arquivos
    $files = glob($path . '/*.php');
    sort($files);
    
    // Busca executadas
    $stmt = $pdo->query("SELECT migration, batch, executed_at FROM migrations ORDER BY id");
    $executed = [];
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $executed[$row['migration']] = $row;
    }
    
    $pending = 0;
    foreach ($files as $file) {
        $name = basename($file);
        
        if (isset($executed[$name])) {
            $info = $executed[$name];
            $date = date('d/m/Y H:i', strtotime($info['executed_at']));
            echo "✅ {$name}\n";
            echo "   Batch: {$info['batch']} | Executada em: {$date}\n\n";
        } else {
            echo "⏳ {$name}\n";
            echo "   Status: PENDENTE\n\n";
            $pending++;
        }
    }
    
    echo str_repeat("─", 60) . "\n";
    echo "Total: " . count($files) . " | Executadas: " . count($executed) . " | Pendentes: {$pending}\n";
}

/**
 * Apaga todas as tabelas do banco
 */
function dropAllTables(Database $db): void
{
    echo "🗑️  Removendo todas as tabelas...\n\n";
    
    $pdo = $db->getConnection();
    
    // Desabilita checagem de FK temporariamente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Lista todas as tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        echo "   ✅ Tabela '{$table}' removida\n";
    }
    
    // Reabilita checagem de FK
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n";
}

/**
 * Verifica se migration já foi executada
 */
function wasExecuted(Database $db, string $name): bool
{
    $stmt = $db->getConnection()->prepare(
        "SELECT COUNT(*) FROM migrations WHERE migration = ?"
    );
    $stmt->execute([$name]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Marca migration como executada
 */
function markAsExecuted(Database $db, string $name, int $batch): void
{
    $stmt = $db->getConnection()->prepare(
        "INSERT INTO migrations (migration, batch) VALUES (?, ?)"
    );
    $stmt->execute([$name, $batch]);
}

/**
 * Obtém próximo número de batch
 */
function getNextBatch(Database $db): int
{
    $stmt = $db->getConnection()->query("SELECT MAX(batch) FROM migrations");
    $maxBatch = $stmt->fetchColumn();
    return ($maxBatch ?? 0) + 1;
}
