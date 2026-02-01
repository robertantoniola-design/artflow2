<?php
/**
 * ============================================
 * MIGRATION 012: Adicionar Status "Superado"
 * ============================================
 * 
 * MELHORIA 1 — Status "Superado" para metas que excedem 120%
 * 
 * O QUE FAZ:
 * 1. Altera o ENUM do campo 'status' para incluir 'superado'
 * 2. Atualiza metas já finalizadas que ultrapassaram 120% → 'superado'
 * 
 * PRÉ-REQUISITO:
 * - Migration 011_add_status_to_metas.php já executada
 * 
 * COMO EXECUTAR:
 * Acesse via navegador: http://localhost/artflow2/database/migrations/012_add_status_superado.php
 * 
 * ROLLBACK:
 * ALTER TABLE metas MODIFY COLUMN status 
 *   ENUM('iniciado', 'em_progresso', 'finalizado') NOT NULL DEFAULT 'iniciado';
 * UPDATE metas SET status = 'finalizado' WHERE status = 'superado';
 */

// ============================================
// CARREGAR .ENV (mesmo padrão do sistema)
// ============================================
// Sobe dois níveis: database/migrations/ → raiz do projeto
$envPath = __DIR__ . '/../../.env';

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignora comentários e linhas vazias
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Configuração do banco via .env (com fallbacks seguros)
$host     = $_ENV['DB_HOST']     ?? 'localhost';
$dbname   = $_ENV['DB_DATABASE'] ?? 'artflow2_db';  // Nome correto do banco
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "<h2>🏆 Migration 012: Adicionando Status 'Superado'</h2>";
echo "<p><small>Banco: <code>{$dbname}</code> @ <code>{$host}</code></small></p>";
echo "<hr>";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // ==========================================
    // PASSO 1: Verificar se 'superado' já existe no ENUM
    // ==========================================
    echo "<h3>Passo 1: Verificando ENUM atual...</h3>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM metas LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        echo "<p style='color:red'>❌ ERRO: Coluna 'status' não encontrada na tabela metas!</p>";
        echo "<p>Execute primeiro a migration <code>011_add_status_to_metas.php</code></p>";
        exit;
    }
    
    $enumType = $column['Type'];
    echo "<p>ENUM atual: <code>{$enumType}</code></p>";
    
    if (strpos($enumType, 'superado') !== false) {
        echo "<p style='color:orange'>⚠️ Status 'superado' já existe no ENUM. Pulando ALTER TABLE.</p>";
    } else {
        // ==========================================
        // PASSO 2: Alterar ENUM para incluir 'superado'
        // ==========================================
        echo "<h3>Passo 2: Adicionando 'superado' ao ENUM...</h3>";
        
        $sql = "ALTER TABLE metas MODIFY COLUMN status 
                ENUM('iniciado', 'em_progresso', 'finalizado', 'superado') 
                NOT NULL DEFAULT 'iniciado'";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ ENUM atualizado com sucesso!</p>";
        echo "<p>Novo ENUM: <code>ENUM('iniciado', 'em_progresso', 'finalizado', 'superado')</code></p>";
    }
    
    // ==========================================
    // PASSO 3: Atualizar metas existentes que excedem 120%
    // ==========================================
    echo "<h3>Passo 3: Atualizando metas que excedem 120%...</h3>";
    
    // Verificar quantas metas se qualificam
    $stmt = $pdo->query(
        "SELECT id, mes_ano, porcentagem_atingida, status 
         FROM metas 
         WHERE porcentagem_atingida >= 120 
         AND status = 'finalizado'"
    );
    $metasSuperadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($metasSuperadas) > 0) {
        // Atualiza em batch
        $sql = "UPDATE metas 
                SET status = 'superado', updated_at = NOW() 
                WHERE porcentagem_atingida >= 120 
                AND status = 'finalizado'";
        
        $affected = $pdo->exec($sql);
        echo "<p style='color:green'>✅ {$affected} meta(s) atualizada(s) para 'superado':</p>";
        echo "<ul>";
        foreach ($metasSuperadas as $m) {
            echo "<li>{$m['mes_ano']} — {$m['porcentagem_atingida']}%</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>ℹ️ Nenhuma meta finalizada com >= 120%. Nenhuma atualização necessária.</p>";
    }
    
    // ==========================================
    // PASSO 4: Verificação final
    // ==========================================
    echo "<h3>Passo 4: Verificação final</h3>";
    
    // Status do ENUM
    $stmt = $pdo->query("SHOW COLUMNS FROM metas LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>ENUM final: <code>{$column['Type']}</code></p>";
    
    // Contagem por status
    $stmt = $pdo->query(
        "SELECT status, COUNT(*) as total 
         FROM metas 
         GROUP BY status 
         ORDER BY FIELD(status, 'iniciado', 'em_progresso', 'finalizado', 'superado')"
    );
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($statusCounts) > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0'>";
        echo "<tr><th>Status</th><th>Quantidade</th></tr>";
        foreach ($statusCounts as $row) {
            $emoji = match($row['status']) {
                'iniciado'     => '⏳',
                'em_progresso' => '🔄',
                'finalizado'   => '✅',
                'superado'     => '🏆',
                default        => '❓'
            };
            echo "<tr><td>{$emoji} {$row['status']}</td><td>{$row['total']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ℹ️ Nenhuma meta cadastrada ainda.</p>";
    }
    
    echo "<hr>";
    echo "<h3 style='color:green'>✅ Migration 012 concluída com sucesso!</h3>";
    echo "<p><strong>Próximo passo:</strong> Substituir os arquivos PHP atualizados:</p>";
    echo "<ul>";
    echo "<li><code>src/Models/Meta.php</code></li>";
    echo "<li><code>src/Repositories/MetaRepository.php</code></li>";
    echo "<li><code>src/Services/MetaService.php</code></li>";
    echo "<li><code>views/metas/index.php</code></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    $errorMsg = htmlspecialchars($e->getMessage());
    echo "<p style='color:red'>❌ ERRO: {$errorMsg}</p>";
    
    // Diagnóstico específico para erros comuns
    if (strpos($e->getMessage(), '1049') !== false) {
        echo "<p>⚠️ <strong>Banco '{$dbname}' não encontrado.</strong></p>";
        echo "<p>Verifique:</p>";
        echo "<ul>";
        echo "<li>O XAMPP/MySQL está rodando?</li>";
        echo "<li>O arquivo <code>.env</code> tem <code>DB_DATABASE=artflow2_db</code>?</li>";
        echo "<li>O banco foi criado? Execute <code>install.php</code> se necessário.</li>";
        echo "</ul>";
    } elseif (strpos($e->getMessage(), '1045') !== false) {
        echo "<p>⚠️ <strong>Credenciais inválidas.</strong> Verifique DB_USERNAME e DB_PASSWORD no .env</p>";
    } elseif (strpos($e->getMessage(), '2002') !== false) {
        echo "<p>⚠️ <strong>MySQL não está rodando.</strong> Inicie o XAMPP e tente novamente.</p>";
    }
}