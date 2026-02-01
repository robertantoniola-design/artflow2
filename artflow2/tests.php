<?php
/**
 * ============================================
 * ARTFLOW 2.0 - TESTES STANDALONE
 * ============================================
 * 
 * Arquivo de diagnóstico independente.
 * Basta colocar na raiz do projeto e acessar:
 * http://localhost/artflow2/tests.php
 * 
 * NÃO requer integração com o sistema!
 * 
 * @author Claude AI
 * @version 1.0.0
 * @date 30/01/2026
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);
define('TEST_VERSION', '1.0.0');

// Carrega autoloader
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Carrega .env
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Carrega helpers se existir
if (file_exists(BASE_PATH . '/src/Helpers/functions.php')) {
    require_once BASE_PATH . '/src/Helpers/functions.php';
}

// ============================================
// CLASSE DE TESTES
// ============================================
class ArtFlowTests {
    private ?PDO $pdo = null;
    private string $baseUrl;
    private array $resultados = [];
    private int $passou = 0;
    private int $falhou = 0;
    private int $avisos = 0;
    
    public function __construct() {
        $this->baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/artflow2', '/');
    }
    
    // ========== TESTES DE AMBIENTE ==========
    public function testarAmbiente(): array {
        $testes = [];
        
        // PHP
        $phpVersion = phpversion();
        $testes[] = $this->resultado(
            'PHP Version',
            version_compare($phpVersion, '8.1.0', '>='),
            "PHP {$phpVersion}"
        );
        
        // Extensões
        $extensoes = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl', 'session'];
        foreach ($extensoes as $ext) {
            $testes[] = $this->resultado(
                "Extensão {$ext}",
                extension_loaded($ext),
                extension_loaded($ext) ? 'OK' : 'Faltando'
            );
        }
        
        // Diretórios
        $dirs = [
            'storage' => BASE_PATH . '/storage',
            'storage/logs' => BASE_PATH . '/storage/logs',
            'public/uploads' => BASE_PATH . '/public/uploads',
        ];
        foreach ($dirs as $nome => $path) {
            $existe = is_dir($path);
            $escrita = $existe && is_writable($path);
            $testes[] = $this->resultado(
                "Diretório {$nome}",
                $existe && $escrita,
                $existe ? ($escrita ? 'OK' : 'Sem escrita') : 'Não existe',
                $existe && !$escrita ? 'warn' : null
            );
        }
        
        // Arquivos
        $arquivos = ['.env', 'config/routes.php', '.htaccess', 'vendor/autoload.php'];
        foreach ($arquivos as $arq) {
            $testes[] = $this->resultado(
                "Arquivo {$arq}",
                file_exists(BASE_PATH . '/' . $arq),
                file_exists(BASE_PATH . '/' . $arq) ? 'Existe' : 'Não encontrado'
            );
        }
        
        return $testes;
    }
    
    // ========== TESTES DE BANCO ==========
    public function testarBanco(): array {
        $testes = [];
        
        // Conexão
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_DATABASE'] ?? 'artflow2_db'
            );
            $this->pdo = new PDO($dsn, $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $testes[] = $this->resultado('Conexão BD', true, 'Conectado');
        } catch (PDOException $e) {
            $testes[] = $this->resultado('Conexão BD', false, $e->getMessage());
            return $testes;
        }
        
        // Tabelas
        $tabelas = ['artes', 'clientes', 'vendas', 'metas', 'tags', 'arte_tags'];
        foreach ($tabelas as $tabela) {
            try {
                $count = $this->pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn();
                $testes[] = $this->resultado("Tabela {$tabela}", true, "{$count} registros");
            } catch (PDOException $e) {
                $testes[] = $this->resultado("Tabela {$tabela}", false, 'Não existe');
            }
        }
        
        // Integridade
        $checks = [
            ['Vendas→Artes', "SELECT COUNT(*) FROM vendas v LEFT JOIN artes a ON v.arte_id=a.id WHERE a.id IS NULL AND v.arte_id IS NOT NULL"],
            ['Vendas→Clientes', "SELECT COUNT(*) FROM vendas v LEFT JOIN clientes c ON v.cliente_id=c.id WHERE c.id IS NULL AND v.cliente_id IS NOT NULL"],
        ];
        foreach ($checks as [$nome, $sql]) {
            try {
                $orfaos = $this->pdo->query($sql)->fetchColumn();
                $testes[] = $this->resultado($nome, $orfaos == 0, $orfaos == 0 ? 'OK' : "{$orfaos} órfãos", $orfaos > 0 ? 'warn' : null);
            } catch (PDOException $e) {
                $testes[] = $this->resultado($nome, null, 'Não verificado', 'skip');
            }
        }
        
        return $testes;
    }
    
    // ========== TESTES DE ROTAS ==========
    public function testarRotas(): array {
        $testes = [];
        
        if (!function_exists('curl_init')) {
            return [$this->resultado('cURL', false, 'Não disponível')];
        }
        
        $rotas = [
            ['/', 'Dashboard', 200],
            ['/artes', 'Artes', 200],
            ['/artes/criar', 'Criar Arte', 200],
            ['/clientes', 'Clientes', 200],
            ['/clientes/criar', 'Criar Cliente', 200],
            ['/vendas', 'Vendas', 200],
            ['/vendas/criar', 'Criar Venda', 200],
            ['/vendas/relatorio', 'Relatório', 200],
            ['/metas', 'Metas', 200],
            ['/metas/criar', 'Criar Meta', 200],
            ['/tags', 'Tags', 200],
            ['/tags/criar', 'Criar Tag', 200],
            ['/xyz-nao-existe', '404 Test', 404],
        ];
        
        foreach ($rotas as [$path, $desc, $esperado]) {
            $start = microtime(true);
            $ch = curl_init($this->baseUrl . $path);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 10]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $tempo = round((microtime(true) - $start) * 1000);
            
            $testes[] = $this->resultado(
                "GET {$path}",
                $code == $esperado,
                "HTTP {$code} ({$tempo}ms)",
                null,
                $desc
            );
        }
        
        return $testes;
    }
    
    // ========== TESTES DE SEGURANÇA ==========
    public function testarSeguranca(): array {
        $testes = [];
        
        // Sessão
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $testes[] = $this->resultado('Sessão PHP', session_status() === PHP_SESSION_ACTIVE, session_status() === PHP_SESSION_ACTIVE ? 'Ativa' : 'Inativa');
        
        // CSRF
        $testes[] = $this->resultado('csrf_token()', function_exists('csrf_token'), function_exists('csrf_token') ? 'Disponível' : 'Não encontrada');
        
        // Escape
        $testes[] = $this->resultado('e() escape', function_exists('e'), function_exists('e') ? 'Disponível' : 'Não encontrada');
        
        // Arquivos sensíveis
        if (function_exists('curl_init')) {
            $sensiveis = ['/.env', '/config/routes.php'];
            foreach ($sensiveis as $arq) {
                $ch = curl_init($this->baseUrl . $arq);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
                curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $protegido = in_array($code, [403, 404, 500]);
                $testes[] = $this->resultado("Arquivo {$arq}", $protegido, $protegido ? "Protegido ({$code})" : "⚠️ EXPOSTO!");
            }
        }
        
        return $testes;
    }
    
    // ========== TESTES DE MÓDULOS ==========
    public function testarModulos(): array {
        $testes = [];
        
        $modulos = [
            'Core' => ['App\\Core\\Application', 'App\\Core\\Router', 'App\\Core\\Request', 'App\\Core\\Response', 'App\\Core\\Database', 'App\\Core\\View'],
            'Artes' => ['App\\Controllers\\ArteController', 'App\\Services\\ArteService', 'App\\Repositories\\ArteRepository', 'App\\Models\\Arte'],
            'Clientes' => ['App\\Controllers\\ClienteController', 'App\\Services\\ClienteService', 'App\\Repositories\\ClienteRepository', 'App\\Models\\Cliente'],
            'Vendas' => ['App\\Controllers\\VendaController', 'App\\Services\\VendaService', 'App\\Repositories\\VendaRepository', 'App\\Models\\Venda'],
            'Metas' => ['App\\Controllers\\MetaController', 'App\\Services\\MetaService', 'App\\Repositories\\MetaRepository', 'App\\Models\\Meta'],
            'Tags' => ['App\\Controllers\\TagController', 'App\\Services\\TagService', 'App\\Repositories\\TagRepository', 'App\\Models\\Tag'],
        ];
        
        foreach ($modulos as $nome => $classes) {
            $total = count($classes);
            $ok = 0;
            foreach ($classes as $classe) {
                if (class_exists($classe)) $ok++;
            }
            $testes[] = $this->resultado("Módulo {$nome}", $ok == $total, "{$ok}/{$total} classes", $ok > 0 && $ok < $total ? 'warn' : null);
        }
        
        return $testes;
    }
    
    // ========== TESTES DE VIEWS ==========
    public function testarViews(): array {
        $testes = [];
        
        $views = [
            'layouts/main.php', 'dashboard/index.php',
            'artes/index.php', 'artes/create.php', 'artes/show.php', 'artes/edit.php',
            'clientes/index.php', 'clientes/create.php', 'clientes/show.php', 'clientes/edit.php',
            'vendas/index.php', 'vendas/create.php', 'vendas/show.php', 'vendas/edit.php', 'vendas/relatorio.php',
            'metas/index.php', 'metas/create.php', 'metas/show.php', 'metas/edit.php',
            'tags/index.php', 'tags/create.php', 'tags/show.php', 'tags/edit.php',
        ];
        
        foreach ($views as $view) {
            $existe = file_exists(BASE_PATH . '/views/' . $view);
            $testes[] = $this->resultado($view, $existe, $existe ? 'OK' : 'Faltando');
        }
        
        return $testes;
    }
    
    // ========== TESTES DE HELPERS ==========
    public function testarHelpers(): array {
        $testes = [];
        
        $helpers = ['url', 'asset', 'money', 'date_br', 'datetime_br', 'e', 'csrf_token', 'old', 'has_error', 'errors', 'flash'];
        foreach ($helpers as $fn) {
            $existe = function_exists($fn);
            $testes[] = $this->resultado("{$fn}()", $existe, $existe ? 'OK' : 'Não encontrada', $existe ? null : 'warn');
        }
        
        // Testes funcionais
        if (function_exists('money')) {
            $r = money(1234.56);
            $testes[] = $this->resultado('money() teste', strpos($r, '1.234') !== false || strpos($r, '1,234') !== false, $r);
        }
        if (function_exists('date_br')) {
            $r = date_br('2026-01-30');
            $testes[] = $this->resultado('date_br() teste', $r === '30/01/2026', $r);
        }
        
        return $testes;
    }
    
    // ========== HELPERS ==========
    private function resultado(string $nome, ?bool $sucesso, string $msg, ?string $forceStatus = null, string $desc = ''): array {
        $status = $forceStatus ?? ($sucesso === true ? 'pass' : ($sucesso === false ? 'fail' : 'skip'));
        
        if ($status === 'pass') $this->passou++;
        elseif ($status === 'fail') $this->falhou++;
        elseif ($status === 'warn') $this->avisos++;
        
        return ['nome' => $nome, 'status' => $status, 'mensagem' => $msg, 'descricao' => $desc];
    }
    
    public function getResumo(): array {
        $total = $this->passou + $this->falhou;
        return [
            'passou' => $this->passou,
            'falhou' => $this->falhou,
            'avisos' => $this->avisos,
            'taxa' => $total > 0 ? round(($this->passou / $total) * 100, 1) : 0
        ];
    }
}

// ============================================
// EXECUÇÃO
// ============================================
$tester = new ArtFlowTests();
$categoria = $_GET['cat'] ?? 'all';

$resultados = [];
if ($categoria === 'all' || $categoria === 'ambiente') $resultados['ambiente'] = $tester->testarAmbiente();
if ($categoria === 'all' || $categoria === 'banco') $resultados['banco'] = $tester->testarBanco();
if ($categoria === 'all' || $categoria === 'rotas') $resultados['rotas'] = $tester->testarRotas();
if ($categoria === 'all' || $categoria === 'seguranca') $resultados['seguranca'] = $tester->testarSeguranca();
if ($categoria === 'all' || $categoria === 'modulos') $resultados['modulos'] = $tester->testarModulos();
if ($categoria === 'all' || $categoria === 'views') $resultados['views'] = $tester->testarViews();
if ($categoria === 'all' || $categoria === 'helpers') $resultados['helpers'] = $tester->testarHelpers();

$resumo = $tester->getResumo();

// Ícone helper
function icon($status) {
    return match($status) {
        'pass' => '✅',
        'fail' => '❌',
        'warn' => '⚠️',
        'skip' => '⏭️',
        default => '❓'
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtFlow 2.0 - Testes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); min-height: 100vh; color: #e5e7eb; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
        .card-header { background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table { color: #e5e7eb; }
        .table th, .table td { border-color: rgba(255,255,255,0.1); }
        .nav-pills .nav-link { color: #9ca3af; }
        .nav-pills .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-pills .nav-link.active { background: #6366f1; color: white; }
        .stat-card { background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card h2 { font-size: 2.5rem; margin: 0; }
        .progress { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-bug text-warning"></i> ArtFlow 2.0 - Testes</h1>
            <p class="text-muted mb-0">v<?= TEST_VERSION ?> | <?= date('d/m/Y H:i:s') ?></p>
        </div>
        <div>
            <a href="<?= $_ENV['APP_URL'] ?? '/artflow2' ?>" class="btn btn-outline-light"><i class="bi bi-house"></i> Sistema</a>
            <a href="?cat=all" class="btn btn-primary"><i class="bi bi-arrow-clockwise"></i> Executar Todos</a>
        </div>
    </div>

    <!-- Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><h2 class="text-success"><?= $resumo['passou'] ?></h2><small>Passou</small></div></div>
        <div class="col-md-3"><div class="stat-card"><h2 class="text-danger"><?= $resumo['falhou'] ?></h2><small>Falhou</small></div></div>
        <div class="col-md-3"><div class="stat-card"><h2 class="text-warning"><?= $resumo['avisos'] ?></h2><small>Avisos</small></div></div>
        <div class="col-md-3"><div class="stat-card"><h2><?= $resumo['taxa'] ?>%</h2><small>Taxa Sucesso</small></div></div>
    </div>

    <!-- Progress -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="progress" style="height: 10px;">
                <?php $total = max(1, $resumo['passou'] + $resumo['falhou'] + $resumo['avisos']); ?>
                <div class="progress-bar bg-success" style="width: <?= ($resumo['passou']/$total)*100 ?>%"></div>
                <div class="progress-bar bg-danger" style="width: <?= ($resumo['falhou']/$total)*100 ?>%"></div>
                <div class="progress-bar bg-warning" style="width: <?= ($resumo['avisos']/$total)*100 ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item"><a class="nav-link <?= $categoria=='all'?'active':'' ?>" href="?cat=all"><i class="bi bi-grid-3x3-gap"></i> Todos</a></li>
        <li class="nav-item"><a class="nav-link <?= $categoria=='ambiente'?'active':'' ?>" href="?cat=ambiente"><i class="bi bi-gear"></i> Ambiente</a></li>
        <li class="nav-item"><a class="nav-link <?= $categoria=='banco'?'active':'' ?>" href="?cat=banco"><i class="bi bi-database"></i> Banco</a></li>
        <li class="nav-item"><a class="nav-link <?= $categoria=='rotas'?'active':'' ?>" href="?cat=rotas"><i class="bi bi-signpost"></i> Rotas</a></li>
        <li class="nav-item"><a class="nav-link <?= $categoria=='seguranca'?'active':'' ?>" href="?cat=seguranca"><i class="bi bi-shield-lock"></i> Segurança</a></li>
        <li class="nav-item"><a class="nav-link <?= $categoria=='modulos'?'active':'' ?>" href="?cat=modulos"><i class="bi bi-boxes"></i> Módulos</a></li>
        <li class="nav-item"><a class="nav-link <?= $categoria=='views'?'active':'' ?>" href="?cat=views"><i class="bi bi-file-code"></i> Views</a></li>
        <li class="nav-item"><a class="nav-link <?= $categoria=='helpers'?'active':'' ?>" href="?cat=helpers"><i class="bi bi-tools"></i> Helpers</a></li>
    </ul>

    <!-- Resultados -->
    <?php foreach ($resultados as $cat => $testes): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?= ucfirst($cat) ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th width="50">Status</th><th>Teste</th><th>Resultado</th></tr></thead>
                <tbody>
                <?php foreach ($testes as $t): ?>
                    <tr class="<?= $t['status']=='fail'?'table-danger':'' ?>">
                        <td class="text-center"><?= icon($t['status']) ?></td>
                        <td><strong><?= htmlspecialchars($t['nome']) ?></strong><?= $t['descricao'] ? '<br><small class="text-muted">'.htmlspecialchars($t['descricao']).'</small>' : '' ?></td>
                        <td><?= htmlspecialchars($t['mensagem']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Problemas -->
    <?php 
    $problemas = [];
    foreach ($resultados as $cat => $testes) {
        foreach ($testes as $t) {
            if ($t['status'] === 'fail') $problemas[] = ['cat' => $cat, 'nome' => $t['nome'], 'msg' => $t['mensagem']];
        }
    }
    ?>
    <?php if ($problemas): ?>
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white"><h5 class="mb-0">❌ Problemas (<?= count($problemas) ?>)</h5></div>
        <div class="card-body">
            <?php foreach ($problemas as $p): ?>
                <p class="mb-1"><span class="badge bg-secondary"><?= ucfirst($p['cat']) ?></span> <strong><?= htmlspecialchars($p['nome']) ?></strong>: <?= htmlspecialchars($p['msg']) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4"><small class="text-muted">PHP</small><br><strong><?= phpversion() ?></strong></div>
                <div class="col-md-4"><small class="text-muted">Memória</small><br><strong><?= round(memory_get_peak_usage(true)/1024/1024, 2) ?> MB</strong></div>
                <div class="col-md-4"><small class="text-muted">Tempo</small><br><strong><?= round((microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'])*1000) ?>ms</strong></div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning text-center">
        <i class="bi bi-exclamation-triangle"></i> <strong>Atenção:</strong> Remova este arquivo em produção!
    </div>
</div>
</body>
</html>
