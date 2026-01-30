<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ARTFLOW 2.0 - SCRIPT DE TESTES AUTOMATIZADOS
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Este script verifica:
 * 1. Estrutura de arquivos
 * 2. Conexão com banco de dados
 * 3. Tabelas existentes
 * 4. Classes carregáveis
 * 5. Rotas definidas
 * 6. Formulários (via cURL)
 * 
 * COMO USAR:
 * 1. Copie este arquivo para: C:\xampp\htdocs\artflow2\test_forms.php
 * 2. Acesse: http://localhost/artflow2/test_forms.php
 * 3. Ou execute via CLI: php test_forms.php
 * 
 * @author Claude AI
 * @date 26/01/2026
 */

// ═══════════════════════════════════════════════════════════════════════════
// CONFIGURAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define se está rodando via CLI ou Web
$isCli = php_sapi_name() === 'cli';

// Cores para output CLI
$colors = [
    'reset'   => "\033[0m",
    'red'     => "\033[31m",
    'green'   => "\033[32m",
    'yellow'  => "\033[33m",
    'blue'    => "\033[34m",
    'magenta' => "\033[35m",
    'cyan'    => "\033[36m",
    'white'   => "\033[37m",
    'bold'    => "\033[1m",
];

// ═══════════════════════════════════════════════════════════════════════════
// FUNÇÕES AUXILIARES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Imprime linha formatada
 */
function printLine($message, $type = 'info', $indent = 0) {
    global $isCli, $colors;
    
    $prefix = str_repeat('  ', $indent);
    $icon = '';
    $color = '';
    
    switch ($type) {
        case 'success':
            $icon = $isCli ? '✅' : '✅';
            $color = $colors['green'];
            break;
        case 'error':
            $icon = $isCli ? '❌' : '❌';
            $color = $colors['red'];
            break;
        case 'warning':
            $icon = $isCli ? '⚠️' : '⚠️';
            $color = $colors['yellow'];
            break;
        case 'info':
            $icon = $isCli ? 'ℹ️' : 'ℹ️';
            $color = $colors['cyan'];
            break;
        case 'test':
            $icon = $isCli ? '🧪' : '🧪';
            $color = $colors['magenta'];
            break;
        case 'header':
            $icon = $isCli ? '═══' : '═══';
            $color = $colors['bold'];
            break;
    }
    
    if ($isCli) {
        echo "{$color}{$prefix}{$icon} {$message}{$colors['reset']}\n";
    } else {
        $bgColor = match($type) {
            'success' => '#d4edda',
            'error' => '#f8d7da',
            'warning' => '#fff3cd',
            'info' => '#d1ecf1',
            'test' => '#e2d5f1',
            'header' => '#343a40',
            default => '#f8f9fa'
        };
        $textColor = $type === 'header' ? '#fff' : '#212529';
        echo "<div style='padding: 8px 16px; margin: 4px 0; background: {$bgColor}; color: {$textColor}; border-radius: 4px; margin-left: {$indent}0px; font-family: monospace;'>{$icon} {$message}</div>";
    }
}

/**
 * Imprime header de seção
 */
function printHeader($title) {
    global $isCli;
    
    if ($isCli) {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║  {$title}" . str_repeat(' ', 60 - strlen($title)) . "║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    } else {
        echo "<h2 style='background: #343a40; color: #fff; padding: 15px 20px; border-radius: 8px; margin: 30px 0 20px 0;'>📋 {$title}</h2>";
    }
}

/**
 * Imprime tabela de resultados
 */
function printTable($headers, $rows) {
    global $isCli;
    
    if ($isCli) {
        // Calcular larguras das colunas
        $widths = array_map('strlen', $headers);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen(strip_tags($cell)));
            }
        }
        
        // Imprimir header
        $line = '+' . implode('+', array_map(fn($w) => str_repeat('-', $w + 2), $widths)) . '+';
        echo "{$line}\n";
        echo '|';
        foreach ($headers as $i => $h) {
            echo ' ' . str_pad($h, $widths[$i]) . ' |';
        }
        echo "\n{$line}\n";
        
        // Imprimir linhas
        foreach ($rows as $row) {
            echo '|';
            foreach ($row as $i => $cell) {
                $clean = strip_tags($cell);
                echo ' ' . str_pad($clean, $widths[$i]) . ' |';
            }
            echo "\n";
        }
        echo "{$line}\n";
    } else {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-family: monospace;'>";
        echo "<thead><tr style='background: #495057; color: #fff;'>";
        foreach ($headers as $h) {
            echo "<th style='padding: 12px; text-align: left; border: 1px solid #dee2e6;'>{$h}</th>";
        }
        echo "</tr></thead><tbody>";
        $alt = false;
        foreach ($rows as $row) {
            $bg = $alt ? '#f8f9fa' : '#fff';
            echo "<tr style='background: {$bg};'>";
            foreach ($row as $cell) {
                echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$cell}</td>";
            }
            echo "</tr>";
            $alt = !$alt;
        }
        echo "</tbody></table>";
    }
}

/**
 * Contador de resultados
 */
class TestResults {
    public int $passed = 0;
    public int $failed = 0;
    public int $warnings = 0;
    public array $errors = [];
    
    public function pass($message = '') {
        $this->passed++;
        if ($message) printLine($message, 'success', 1);
    }
    
    public function fail($message) {
        $this->failed++;
        $this->errors[] = $message;
        printLine($message, 'error', 1);
    }
    
    public function warn($message) {
        $this->warnings++;
        printLine($message, 'warning', 1);
    }
    
    public function summary() {
        printHeader('RESUMO DOS TESTES');
        
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 1) : 0;
        
        printLine("Total de testes: {$total}", 'info');
        printLine("Passou: {$this->passed}", 'success');
        printLine("Falhou: {$this->failed}", 'error');
        printLine("Avisos: {$this->warnings}", 'warning');
        printLine("Taxa de sucesso: {$percentage}%", $percentage >= 80 ? 'success' : 'warning');
        
        if (!empty($this->errors)) {
            printHeader('ERROS ENCONTRADOS');
            foreach ($this->errors as $error) {
                printLine($error, 'error');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// INÍCIO DOS TESTES
// ═══════════════════════════════════════════════════════════════════════════

// Output HTML se via web
if (!$isCli) {
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ArtFlow 2.0 - Testes de Formulários</title>
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                max-width: 1200px; 
                margin: 0 auto; 
                padding: 20px;
                background: #f5f5f5;
            }
            h1 { color: #343a40; border-bottom: 3px solid #007bff; padding-bottom: 15px; }
            .container { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
    <div class="container">
    <h1>🧪 ArtFlow 2.0 - Testes Automatizados</h1>
    <p><strong>Data:</strong> ' . date('d/m/Y H:i:s') . '</p>
    <hr>';
}

$results = new TestResults();

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 1: VERIFICAR ESTRUTURA DE ARQUIVOS
// ═══════════════════════════════════════════════════════════════════════════

printHeader('1. ESTRUTURA DE ARQUIVOS');

$basePath = __DIR__;
$requiredFiles = [
    'public/index.php' => 'Ponto de entrada',
    'public/.htaccess' => 'Rewrite rules',
    '.env' => 'Configuração ambiente',
    'config/routes.php' => 'Definição de rotas',
    'src/Core/Application.php' => 'Classe Application',
    'src/Core/Router.php' => 'Sistema de rotas',
    'src/Core/Database.php' => 'Conexão BD',
    'src/Core/Request.php' => 'Objeto Request',
    'src/Core/Response.php' => 'Objeto Response',
    'src/Core/View.php' => 'Motor de templates',
    'src/Controllers/ArteController.php' => 'Controller Artes',
    'src/Controllers/TagController.php' => 'Controller Tags',
    'src/Controllers/VendaController.php' => 'Controller Vendas',
    'src/Controllers/MetaController.php' => 'Controller Metas',
    'src/Controllers/ClienteController.php' => 'Controller Clientes',
    'src/Services/ArteService.php' => 'Service Artes',
    'src/Services/TagService.php' => 'Service Tags',
    'src/Services/VendaService.php' => 'Service Vendas',
    'src/Repositories/ArteRepository.php' => 'Repository Artes',
    'src/Repositories/TagRepository.php' => 'Repository Tags',
    'views/artes/index.php' => 'View listagem artes',
    'views/artes/create.php' => 'View criar arte',
    'views/artes/edit.php' => 'View editar arte',
    'views/tags/index.php' => 'View listagem tags',
    'views/vendas/index.php' => 'View listagem vendas',
    'views/vendas/create.php' => 'View criar venda',
    'views/metas/index.php' => 'View listagem metas',
    'views/layouts/main.php' => 'Layout principal',
];

$fileRows = [];
foreach ($requiredFiles as $file => $description) {
    $fullPath = $basePath . '/' . $file;
    $exists = file_exists($fullPath);
    
    if ($exists) {
        $results->pass();
        $status = $isCli ? '✅ OK' : '<span style="color: green; font-weight: bold;">✅ OK</span>';
    } else {
        $results->fail("Arquivo não encontrado: {$file}");
        $status = $isCli ? '❌ FALTA' : '<span style="color: red; font-weight: bold;">❌ FALTA</span>';
    }
    
    $fileRows[] = [$file, $description, $status];
}

printTable(['Arquivo', 'Descrição', 'Status'], $fileRows);

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 2: VERIFICAR .ENV E CONFIGURAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

printHeader('2. CONFIGURAÇÕES (.env)');

$envFile = $basePath . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $envLines = explode("\n", $envContent);
    
    $requiredEnvVars = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'APP_URL'];
    $envVars = [];
    
    foreach ($envLines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
    
    foreach ($requiredEnvVars as $var) {
        if (isset($envVars[$var]) && !empty($envVars[$var])) {
            $results->pass("Variável {$var} configurada");
        } else {
            $results->fail("Variável {$var} não configurada ou vazia");
        }
    }
    
    // Exibir configurações (mascarando senha)
    $configRows = [];
    foreach ($envVars as $key => $value) {
        $displayValue = (stripos($key, 'PASSWORD') !== false) ? '********' : $value;
        $configRows[] = [$key, $displayValue];
    }
    printTable(['Variável', 'Valor'], $configRows);
    
} else {
    $results->fail("Arquivo .env não encontrado");
}

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 3: CONEXÃO COM BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

printHeader('3. CONEXÃO COM BANCO DE DADOS');

try {
    // Carregar .env manualmente para teste
    if (isset($envVars)) {
        foreach ($envVars as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? 'artflow2_db';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $results->pass("Conexão com banco de dados estabelecida");
    
    // Verificar tabelas
    printLine("Verificando tabelas...", 'test');
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = ['artes', 'clientes', 'vendas', 'metas', 'tags', 'arte_tags', 'migrations'];
    
    $tableRows = [];
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            $results->pass();
            
            // Contar registros
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            $status = $isCli ? '✅ OK' : '<span style="color: green;">✅ OK</span>';
            $tableRows[] = [$table, $count . ' registros', $status];
        } else {
            $results->fail("Tabela '{$table}' não existe");
            $status = $isCli ? '❌ FALTA' : '<span style="color: red;">❌ FALTA</span>';
            $tableRows[] = [$table, '-', $status];
        }
    }
    
    printTable(['Tabela', 'Registros', 'Status'], $tableRows);
    
} catch (PDOException $e) {
    $results->fail("Erro de conexão: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 4: VERIFICAR AUTOLOADER E CLASSES
// ═══════════════════════════════════════════════════════════════════════════

printHeader('4. AUTOLOADER E CLASSES');

// Tentar carregar autoloader
$autoloaderPaths = [
    $basePath . '/vendor/autoload.php',
];

$autoloaderLoaded = false;
foreach ($autoloaderPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaderLoaded = true;
        $results->pass("Autoloader carregado: " . basename($path));
        break;
    }
}

if (!$autoloaderLoaded) {
    $results->warn("Autoloader composer não encontrado - verificando autoloader manual");
}

// Verificar se classes existem
$classesToCheck = [
    'App\\Core\\Application',
    'App\\Core\\Router',
    'App\\Core\\Request',
    'App\\Core\\Response',
    'App\\Core\\Database',
    'App\\Core\\View',
    'App\\Controllers\\ArteController',
    'App\\Controllers\\TagController',
    'App\\Controllers\\VendaController',
    'App\\Services\\ArteService',
    'App\\Repositories\\ArteRepository',
];

$classRows = [];
foreach ($classesToCheck as $class) {
    if (class_exists($class)) {
        $results->pass();
        $status = $isCli ? '✅ OK' : '<span style="color: green;">✅ OK</span>';
    } else {
        $results->fail("Classe não encontrada: {$class}");
        $status = $isCli ? '❌ FALTA' : '<span style="color: red;">❌ FALTA</span>';
    }
    $classRows[] = [$class, $status];
}

printTable(['Classe', 'Status'], $classRows);

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 5: VERIFICAR ROTAS (via HTTP se possível)
// ═══════════════════════════════════════════════════════════════════════════

printHeader('5. VERIFICAR ROTAS HTTP');

$baseUrl = $_ENV['APP_URL'] ?? 'http://localhost/artflow2';

$routesToTest = [
    ['GET', '/', 'Dashboard'],
    ['GET', '/artes', 'Listagem Artes'],
    ['GET', '/artes/criar', 'Form Criar Arte'],
    ['GET', '/clientes', 'Listagem Clientes'],
    ['GET', '/tags', 'Listagem Tags'],
    ['GET', '/vendas', 'Listagem Vendas'],
    ['GET', '/vendas/criar', 'Form Criar Venda'],
    ['GET', '/metas', 'Listagem Metas'],
    ['GET', '/vendas/relatorio', 'Relatório Vendas'],
];

// Verificar se cURL está disponível
if (function_exists('curl_init')) {
    printLine("Testando rotas via cURL...", 'test');
    
    $routeRows = [];
    foreach ($routesToTest as [$method, $path, $description]) {
        $url = rtrim($baseUrl, '/') . $path;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => false,
            CURLOPT_HTTPHEADER => ['Accept: text/html'],
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $results->fail("Erro ao acessar {$path}: {$error}");
            $status = $isCli ? "❌ Erro" : "<span style='color: red;'>❌ Erro</span>";
        } elseif ($httpCode === 200) {
            $results->pass();
            $status = $isCli ? "✅ 200" : "<span style='color: green;'>✅ 200</span>";
            
            // Verificar se tem erro PHP na resposta
            if (stripos($response, 'Fatal error') !== false || stripos($response, 'Parse error') !== false) {
                $results->fail("Erro PHP detectado em {$path}");
                $status = $isCli ? "⚠️ 200 c/ erro" : "<span style='color: orange;'>⚠️ 200 c/ erro PHP</span>";
            }
        } elseif ($httpCode === 302 || $httpCode === 301) {
            $results->pass();
            $status = $isCli ? "↪️ Redirect" : "<span style='color: blue;'>↪️ {$httpCode}</span>";
        } elseif ($httpCode === 404) {
            $results->fail("Rota não encontrada: {$path}");
            $status = $isCli ? "❌ 404" : "<span style='color: red;'>❌ 404</span>";
        } else {
            $results->warn("HTTP {$httpCode} em {$path}");
            $status = $isCli ? "⚠️ {$httpCode}" : "<span style='color: orange;'>⚠️ {$httpCode}</span>";
        }
        
        $routeRows[] = [$method, $path, $description, $status];
    }
    
    printTable(['Método', 'Rota', 'Descrição', 'Status'], $routeRows);
    
} else {
    $results->warn("cURL não disponível - testes de rota ignorados");
    printLine("Instale a extensão cURL do PHP para testar rotas automaticamente", 'info');
}

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 6: VERIFICAR FORMULÁRIOS (CSRF, campos)
// ═══════════════════════════════════════════════════════════════════════════

printHeader('6. VERIFICAR FORMULÁRIOS');

$formsToCheck = [
    '/artes/criar' => ['nome', 'status'],
    '/tags/criar' => ['nome', 'cor'],
    '/vendas/criar' => ['arte_id', 'cliente_id', 'valor_venda'],
    '/metas/criar' => ['mes', 'ano', 'tipo', 'valor_meta'],
    '/clientes/criar' => ['nome'],
];

if (function_exists('curl_init')) {
    $formRows = [];
    
    foreach ($formsToCheck as $path => $requiredFields) {
        $url = rtrim($baseUrl, '/') . $path;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $formRows[] = [$path, implode(', ', $requiredFields), "❌ HTTP {$httpCode}"];
            $results->fail("Não foi possível acessar formulário: {$path}");
            continue;
        }
        
        // Verificar CSRF
        $hasCSRF = (strpos($html, '_token') !== false || strpos($html, 'csrf') !== false);
        
        // Verificar campos
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (strpos($html, "name=\"{$field}\"") === false && strpos($html, "name='{$field}'") === false) {
                $missingFields[] = $field;
            }
        }
        
        $checks = [];
        if ($hasCSRF) {
            $checks[] = "CSRF ✅";
            $results->pass();
        } else {
            $checks[] = "CSRF ❌";
            $results->fail("CSRF não encontrado em: {$path}");
        }
        
        if (empty($missingFields)) {
            $checks[] = "Campos ✅";
            $results->pass();
        } else {
            $checks[] = "Falta: " . implode(', ', $missingFields);
            $results->warn("Campos faltando em {$path}: " . implode(', ', $missingFields));
        }
        
        $formRows[] = [$path, implode(', ', $requiredFields), implode(' | ', $checks)];
    }
    
    printTable(['Formulário', 'Campos Esperados', 'Verificação'], $formRows);
} else {
    printLine("cURL não disponível - verificação de formulários ignorada", 'warning');
}

// ═══════════════════════════════════════════════════════════════════════════
// RESUMO FINAL
// ═══════════════════════════════════════════════════════════════════════════

$results->summary();

// ═══════════════════════════════════════════════════════════════════════════
// PRÓXIMOS PASSOS
// ═══════════════════════════════════════════════════════════════════════════

printHeader('PRÓXIMOS PASSOS');

printLine("1. Corrija os erros listados acima (se houver)", 'info');
printLine("2. Execute os testes manuais do PLANO_TESTES_FORMULARIOS.md", 'info');
printLine("3. Teste cada formulário individualmente no navegador", 'info');
printLine("4. Verifique o arquivo storage/logs/error.log para erros PHP", 'info');
printLine("5. Use o Console do navegador (F12) para erros JavaScript", 'info');

// Fechar HTML se via web
if (!$isCli) {
    echo '</div></body></html>';
}
