<?php

namespace App\Services;

use PDO;
use PDOException;

/**
 * ============================================
 * TEST SERVICE - SERVIÇO DE TESTES E DIAGNÓSTICO
 * ============================================
 * 
 * Responsável por executar todos os testes do sistema.
 * Verifica: ambiente, banco, rotas, segurança, módulos.
 * 
 * @author Claude AI
 * @version 1.0.0
 * @date 30/01/2026
 */
class TestService
{
    private ?PDO $pdo = null;
    private string $baseUrl;
    private array $results = [];
    
    public function __construct()
    {
        $this->baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/artflow2', '/');
    }
    
    // ==========================================
    // MÉTODOS PRINCIPAIS
    // ==========================================
    
    /**
     * Executa todos os testes
     */
    public function runAllTests(): array
    {
        return [
            'ambiente' => $this->testAmbiente(),
            'banco' => $this->testBancoDados(),
            'rotas' => $this->testRotas(),
            'seguranca' => $this->testSeguranca(),
            'modulos' => $this->testModulos(),
            'helpers' => $this->testHelpers(),
            'views' => $this->testViews(),
            'resumo' => $this->getResumo(),
        ];
    }
    
    /**
     * Executa teste específico
     */
    public function runTest(string $categoria): array
    {
        return match($categoria) {
            'ambiente' => $this->testAmbiente(),
            'banco' => $this->testBancoDados(),
            'rotas' => $this->testRotas(),
            'seguranca' => $this->testSeguranca(),
            'modulos' => $this->testModulos(),
            'helpers' => $this->testHelpers(),
            'views' => $this->testViews(),
            default => ['error' => 'Categoria não encontrada']
        };
    }
    
    // ==========================================
    // TESTE DE AMBIENTE
    // ==========================================
    
    public function testAmbiente(): array
    {
        $testes = [];
        
        // PHP Version
        $phpVersion = phpversion();
        $testes['php_version'] = [
            'nome' => 'Versão do PHP',
            'valor' => $phpVersion,
            'status' => version_compare($phpVersion, '8.1.0', '>=') ? 'pass' : 'fail',
            'mensagem' => version_compare($phpVersion, '8.1.0', '>=') 
                ? "PHP {$phpVersion} (OK)" 
                : "PHP {$phpVersion} - Requer 8.1+"
        ];
        
        // Extensões obrigatórias
        $extensoes = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'curl', 'fileinfo'];
        foreach ($extensoes as $ext) {
            $testes["ext_{$ext}"] = [
                'nome' => "Extensão {$ext}",
                'status' => extension_loaded($ext) ? 'pass' : 'fail',
                'mensagem' => extension_loaded($ext) ? 'Instalada' : 'Não encontrada'
            ];
        }
        
        // Diretórios e permissões
        $diretorios = [
            'storage' => BASE_PATH . '/storage',
            'storage/logs' => BASE_PATH . '/storage/logs',
            'storage/cache' => BASE_PATH . '/storage/cache',
            'public/uploads' => BASE_PATH . '/public/uploads',
        ];
        
        foreach ($diretorios as $nome => $path) {
            $existe = is_dir($path);
            $escrita = $existe && is_writable($path);
            
            $testes["dir_{$nome}"] = [
                'nome' => "Diretório {$nome}",
                'status' => ($existe && $escrita) ? 'pass' : ($existe ? 'warn' : 'fail'),
                'mensagem' => $existe 
                    ? ($escrita ? 'OK (escrita)' : 'Sem permissão de escrita')
                    : 'Não existe'
            ];
        }
        
        // Arquivos de configuração
        $arquivos = [
            '.env' => BASE_PATH . '/.env',
            'config/routes.php' => BASE_PATH . '/config/routes.php',
            '.htaccess' => BASE_PATH . '/.htaccess',
            'public/.htaccess' => BASE_PATH . '/public/.htaccess',
            'vendor/autoload.php' => BASE_PATH . '/vendor/autoload.php',
        ];
        
        foreach ($arquivos as $nome => $path) {
            $testes["file_{$nome}"] = [
                'nome' => "Arquivo {$nome}",
                'status' => file_exists($path) ? 'pass' : 'fail',
                'mensagem' => file_exists($path) ? 'Existe' : 'Não encontrado'
            ];
        }
        
        // Memória disponível
        $memLimit = ini_get('memory_limit');
        $testes['memory_limit'] = [
            'nome' => 'Limite de Memória',
            'valor' => $memLimit,
            'status' => 'info',
            'mensagem' => $memLimit
        ];
        
        // Max execution time
        $maxTime = ini_get('max_execution_time');
        $testes['max_execution_time'] = [
            'nome' => 'Tempo Máximo Execução',
            'valor' => $maxTime,
            'status' => 'info',
            'mensagem' => $maxTime . 's'
        ];
        
        return $testes;
    }
    
    // ==========================================
    // TESTE DE BANCO DE DADOS
    // ==========================================
    
    public function testBancoDados(): array
    {
        $testes = [];
        
        // Conexão
        $conexao = $this->testarConexaoBanco();
        $testes['conexao'] = [
            'nome' => 'Conexão com Banco',
            'status' => $conexao['success'] ? 'pass' : 'fail',
            'mensagem' => $conexao['message']
        ];
        
        if (!$conexao['success']) {
            return $testes;
        }
        
        $this->pdo = $conexao['pdo'];
        
        // Tabelas obrigatórias
        $tabelas = ['artes', 'clientes', 'vendas', 'metas', 'tags', 'arte_tags'];
        foreach ($tabelas as $tabela) {
            $existe = $this->tabelaExiste($tabela);
            $count = $existe ? $this->contarRegistros($tabela) : 0;
            
            $testes["tabela_{$tabela}"] = [
                'nome' => "Tabela {$tabela}",
                'status' => $existe ? 'pass' : 'fail',
                'mensagem' => $existe ? "OK ({$count} registros)" : 'Não existe',
                'registros' => $count
            ];
        }
        
        // Estrutura das tabelas (colunas principais)
        $estruturas = [
            'artes' => ['id', 'nome', 'descricao', 'status', 'preco_custo', 'horas_trabalhadas'],
            'clientes' => ['id', 'nome', 'email', 'telefone'],
            'vendas' => ['id', 'arte_id', 'cliente_id', 'valor', 'data_venda', 'lucro_calculado'],
            'metas' => ['id', 'mes_ano', 'valor_meta', 'valor_realizado', 'porcentagem_atingida'],
            'tags' => ['id', 'nome', 'cor'],
        ];
        
        foreach ($estruturas as $tabela => $colunas) {
            if (!$this->tabelaExiste($tabela)) continue;
            
            $colunasExistentes = $this->getColunas($tabela);
            $faltantes = array_diff($colunas, $colunasExistentes);
            
            $testes["estrutura_{$tabela}"] = [
                'nome' => "Estrutura {$tabela}",
                'status' => empty($faltantes) ? 'pass' : 'warn',
                'mensagem' => empty($faltantes) 
                    ? 'Estrutura OK' 
                    : 'Faltam: ' . implode(', ', $faltantes)
            ];
        }
        
        // Integridade referencial
        $integridade = [
            ['nome' => 'Vendas → Artes', 'query' => "SELECT COUNT(*) FROM vendas v LEFT JOIN artes a ON v.arte_id = a.id WHERE a.id IS NULL AND v.arte_id IS NOT NULL"],
            ['nome' => 'Vendas → Clientes', 'query' => "SELECT COUNT(*) FROM vendas v LEFT JOIN clientes c ON v.cliente_id = c.id WHERE c.id IS NULL AND v.cliente_id IS NOT NULL"],
            ['nome' => 'Arte_Tags → Artes', 'query' => "SELECT COUNT(*) FROM arte_tags at LEFT JOIN artes a ON at.arte_id = a.id WHERE a.id IS NULL"],
            ['nome' => 'Arte_Tags → Tags', 'query' => "SELECT COUNT(*) FROM arte_tags at LEFT JOIN tags t ON at.tag_id = t.id WHERE t.id IS NULL"],
        ];
        
        foreach ($integridade as $check) {
            try {
                $orfaos = $this->pdo->query($check['query'])->fetchColumn();
                $testes["integridade_" . strtolower(str_replace(' ', '_', $check['nome']))] = [
                    'nome' => $check['nome'],
                    'status' => $orfaos == 0 ? 'pass' : 'warn',
                    'mensagem' => $orfaos == 0 ? 'OK' : "{$orfaos} registros órfãos"
                ];
            } catch (PDOException $e) {
                $testes["integridade_" . strtolower(str_replace(' ', '_', $check['nome']))] = [
                    'nome' => $check['nome'],
                    'status' => 'skip',
                    'mensagem' => 'Não foi possível verificar'
                ];
            }
        }
        
        return $testes;
    }
    
    // ==========================================
    // TESTE DE ROTAS
    // ==========================================
    
    public function testRotas(): array
    {
        $testes = [];
        
        if (!function_exists('curl_init')) {
            return ['curl_error' => [
                'nome' => 'cURL',
                'status' => 'skip',
                'mensagem' => 'cURL não disponível'
            ]];
        }
        
        $rotas = [
            // Dashboard
            ['GET', '/', 'Dashboard', 200],
            ['GET', '/dashboard', 'Dashboard (alias)', 200],
            ['GET', '/dashboard/refresh', 'API Refresh', 200],
            
            // Artes
            ['GET', '/artes', 'Lista Artes', 200],
            ['GET', '/artes/criar', 'Form Criar Arte', 200],
            
            // Clientes
            ['GET', '/clientes', 'Lista Clientes', 200],
            ['GET', '/clientes/criar', 'Form Criar Cliente', 200],
            
            // Vendas
            ['GET', '/vendas', 'Lista Vendas', 200],
            ['GET', '/vendas/criar', 'Form Criar Venda', 200],
            ['GET', '/vendas/relatorio', 'Relatório Vendas', 200],
            
            // Metas
            ['GET', '/metas', 'Lista Metas', 200],
            ['GET', '/metas/criar', 'Form Criar Meta', 200],
            
            // Tags
            ['GET', '/tags', 'Lista Tags', 200],
            ['GET', '/tags/criar', 'Form Criar Tag', 200],
            
            // 404
            ['GET', '/rota-inexistente-xyz-123', 'Teste 404', 404],
        ];
        
        foreach ($rotas as [$metodo, $path, $descricao, $esperado]) {
            $resultado = $this->testarRotaHttp($this->baseUrl . $path);
            $key = strtolower(str_replace(['/', ' ', '-'], '_', $path)) ?: 'home';
            
            $testes[$key] = [
                'nome' => "{$metodo} {$path}",
                'descricao' => $descricao,
                'status' => $resultado['code'] == $esperado ? 'pass' : 'fail',
                'mensagem' => "HTTP {$resultado['code']}" . ($resultado['code'] != $esperado ? " (esperado: {$esperado})" : ''),
                'tempo' => $resultado['time'] . 'ms'
            ];
        }
        
        return $testes;
    }
    
    // ==========================================
    // TESTE DE SEGURANÇA
    // ==========================================
    
    public function testSeguranca(): array
    {
        $testes = [];
        
        // Sessão
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        
        $testes['sessao'] = [
            'nome' => 'Sessão PHP',
            'status' => session_status() === PHP_SESSION_ACTIVE ? 'pass' : 'fail',
            'mensagem' => session_status() === PHP_SESSION_ACTIVE ? 'Ativa' : 'Inativa'
        ];
        
        // Cookie HttpOnly
        $cookieParams = session_get_cookie_params();
        $testes['cookie_httponly'] = [
            'nome' => 'Cookie HttpOnly',
            'status' => $cookieParams['httponly'] ? 'pass' : 'warn',
            'mensagem' => $cookieParams['httponly'] ? 'Sim' : 'Não (recomendado: sim)'
        ];
        
        // CSRF Token
        $testes['csrf_funcao'] = [
            'nome' => 'Função csrf_token()',
            'status' => function_exists('csrf_token') ? 'pass' : 'fail',
            'mensagem' => function_exists('csrf_token') ? 'Disponível' : 'Não encontrada'
        ];
        
        if (function_exists('csrf_token')) {
            $token = csrf_token();
            $testes['csrf_token'] = [
                'nome' => 'Token CSRF',
                'status' => !empty($token) && strlen($token) >= 32 ? 'pass' : 'warn',
                'mensagem' => !empty($token) ? 'Gerado (' . strlen($token) . ' chars)' : 'Vazio'
            ];
        }
        
        // Arquivos sensíveis (não devem ser acessíveis)
        if (function_exists('curl_init')) {
            $arquivosSensiveis = [
                '.env' => '/.env',
                'config/routes.php' => '/config/routes.php',
                'src/Core/Database.php' => '/src/Core/Database.php',
            ];
            
            foreach ($arquivosSensiveis as $nome => $path) {
                $resultado = $this->testarRotaHttp($this->baseUrl . $path);
                // Arquivo NÃO deve ser acessível (403 ou 404 é bom)
                $protegido = in_array($resultado['code'], [403, 404, 500]);
                
                $testes["arquivo_" . str_replace(['/', '.'], '_', $nome)] = [
                    'nome' => "Arquivo {$nome}",
                    'status' => $protegido ? 'pass' : 'fail',
                    'mensagem' => $protegido 
                        ? "Protegido ({$resultado['code']})" 
                        : "⚠️ EXPOSTO! ({$resultado['code']})"
                ];
            }
        }
        
        // Função de escape
        $testes['escape_html'] = [
            'nome' => 'Função e() (escape)',
            'status' => function_exists('e') ? 'pass' : 'warn',
            'mensagem' => function_exists('e') ? 'Disponível' : 'Não encontrada'
        ];
        
        // Teste de XSS
        if (function_exists('e')) {
            $xssInput = '<script>alert("xss")</script>';
            $escaped = e($xssInput);
            $safe = strpos($escaped, '<script>') === false;
            
            $testes['xss_protecao'] = [
                'nome' => 'Proteção XSS',
                'status' => $safe ? 'pass' : 'fail',
                'mensagem' => $safe ? 'e() sanitiza corretamente' : 'VULNERÁVEL!'
            ];
        }
        
        // Headers de segurança
        $testes['error_display'] = [
            'nome' => 'Display Errors',
            'status' => ini_get('display_errors') ? 'warn' : 'pass',
            'mensagem' => ini_get('display_errors') ? 'Ativo (desativar em produção)' : 'Desativado'
        ];
        
        return $testes;
    }
    
    // ==========================================
    // TESTE DE MÓDULOS
    // ==========================================
    
    public function testModulos(): array
    {
        $testes = [];
        
        $modulos = [
            'Dashboard' => [
                'controller' => 'App\\Controllers\\DashboardController',
            ],
            'Artes' => [
                'controller' => 'App\\Controllers\\ArteController',
                'service' => 'App\\Services\\ArteService',
                'repository' => 'App\\Repositories\\ArteRepository',
                'model' => 'App\\Models\\Arte',
                'validator' => 'App\\Validators\\ArteValidator',
            ],
            'Clientes' => [
                'controller' => 'App\\Controllers\\ClienteController',
                'service' => 'App\\Services\\ClienteService',
                'repository' => 'App\\Repositories\\ClienteRepository',
                'model' => 'App\\Models\\Cliente',
                'validator' => 'App\\Validators\\ClienteValidator',
            ],
            'Vendas' => [
                'controller' => 'App\\Controllers\\VendaController',
                'service' => 'App\\Services\\VendaService',
                'repository' => 'App\\Repositories\\VendaRepository',
                'model' => 'App\\Models\\Venda',
                'validator' => 'App\\Validators\\VendaValidator',
            ],
            'Metas' => [
                'controller' => 'App\\Controllers\\MetaController',
                'service' => 'App\\Services\\MetaService',
                'repository' => 'App\\Repositories\\MetaRepository',
                'model' => 'App\\Models\\Meta',
                'validator' => 'App\\Validators\\MetaValidator',
            ],
            'Tags' => [
                'controller' => 'App\\Controllers\\TagController',
                'service' => 'App\\Services\\TagService',
                'repository' => 'App\\Repositories\\TagRepository',
                'model' => 'App\\Models\\Tag',
                'validator' => 'App\\Validators\\TagValidator',
            ],
        ];
        
        foreach ($modulos as $modulo => $classes) {
            $moduloTestes = [];
            $totalClasses = 0;
            $classesOk = 0;
            
            foreach ($classes as $tipo => $classe) {
                $totalClasses++;
                $existe = class_exists($classe);
                if ($existe) $classesOk++;
                
                $moduloTestes[$tipo] = [
                    'classe' => $classe,
                    'status' => $existe ? 'pass' : 'fail',
                ];
            }
            
            $testes[$modulo] = [
                'nome' => $modulo,
                'status' => $classesOk == $totalClasses ? 'pass' : ($classesOk > 0 ? 'warn' : 'fail'),
                'mensagem' => "{$classesOk}/{$totalClasses} classes encontradas",
                'detalhes' => $moduloTestes
            ];
        }
        
        // Core classes
        $coreClasses = [
            'App\\Core\\Application',
            'App\\Core\\Router',
            'App\\Core\\Request',
            'App\\Core\\Response',
            'App\\Core\\Database',
            'App\\Core\\View',
            'App\\Core\\Container',
        ];
        
        $coreOk = 0;
        $coreDetalhes = [];
        foreach ($coreClasses as $classe) {
            $existe = class_exists($classe);
            if ($existe) $coreOk++;
            $coreDetalhes[basename(str_replace('\\', '/', $classe))] = [
                'classe' => $classe,
                'status' => $existe ? 'pass' : 'fail'
            ];
        }
        
        $testes['Core'] = [
            'nome' => 'Core',
            'status' => $coreOk == count($coreClasses) ? 'pass' : 'fail',
            'mensagem' => "{$coreOk}/" . count($coreClasses) . " classes encontradas",
            'detalhes' => $coreDetalhes
        ];
        
        return $testes;
    }
    
    // ==========================================
    // TESTE DE HELPERS
    // ==========================================
    
    public function testHelpers(): array
    {
        $testes = [];
        
        $helpers = [
            // URL
            'url' => ['categoria' => 'URL', 'teste' => fn() => function_exists('url')],
            'asset' => ['categoria' => 'URL', 'teste' => fn() => function_exists('asset')],
            'redirect' => ['categoria' => 'URL', 'teste' => fn() => function_exists('redirect')],
            
            // Formatação
            'money' => ['categoria' => 'Formatação', 'teste' => fn() => function_exists('money')],
            'date_br' => ['categoria' => 'Formatação', 'teste' => fn() => function_exists('date_br')],
            'datetime_br' => ['categoria' => 'Formatação', 'teste' => fn() => function_exists('datetime_br')],
            'e' => ['categoria' => 'Formatação', 'teste' => fn() => function_exists('e')],
            
            // Formulário
            'csrf_token' => ['categoria' => 'Formulário', 'teste' => fn() => function_exists('csrf_token')],
            'old' => ['categoria' => 'Formulário', 'teste' => fn() => function_exists('old')],
            'has_error' => ['categoria' => 'Formulário', 'teste' => fn() => function_exists('has_error')],
            'errors' => ['categoria' => 'Formulário', 'teste' => fn() => function_exists('errors')],
            
            // Flash
            'flash' => ['categoria' => 'Flash', 'teste' => fn() => function_exists('flash')],
            'flash_success' => ['categoria' => 'Flash', 'teste' => fn() => function_exists('flash_success')],
            'flash_error' => ['categoria' => 'Flash', 'teste' => fn() => function_exists('flash_error')],
        ];
        
        foreach ($helpers as $nome => $config) {
            $existe = $config['teste']();
            $testes[$nome] = [
                'nome' => "{$nome}()",
                'categoria' => $config['categoria'],
                'status' => $existe ? 'pass' : 'warn',
                'mensagem' => $existe ? 'Disponível' : 'Não encontrada'
            ];
        }
        
        // Testes funcionais
        if (function_exists('money')) {
            $resultado = money(1234.56);
            $testes['money_funcional'] = [
                'nome' => 'money() funcional',
                'categoria' => 'Teste',
                'status' => strpos($resultado, '1.234') !== false || strpos($resultado, '1,234') !== false ? 'pass' : 'fail',
                'mensagem' => "money(1234.56) = {$resultado}"
            ];
        }
        
        if (function_exists('date_br')) {
            $resultado = date_br('2026-01-30');
            $testes['date_br_funcional'] = [
                'nome' => 'date_br() funcional',
                'categoria' => 'Teste',
                'status' => $resultado === '30/01/2026' ? 'pass' : 'fail',
                'mensagem' => "date_br('2026-01-30') = {$resultado}"
            ];
        }
        
        if (function_exists('url')) {
            $resultado = url('/artes');
            $testes['url_funcional'] = [
                'nome' => 'url() funcional',
                'categoria' => 'Teste',
                'status' => strpos($resultado, '/artes') !== false ? 'pass' : 'fail',
                'mensagem' => "url('/artes') = {$resultado}"
            ];
        }
        
        return $testes;
    }
    
    // ==========================================
    // TESTE DE VIEWS
    // ==========================================
    
    public function testViews(): array
    {
        $testes = [];
        
        $views = [
            // Layouts
            'layouts/main.php' => 'Layout Principal',
            'layouts/error.php' => 'Layout de Erro',
            
            // Dashboard
            'dashboard/index.php' => 'Dashboard',
            
            // Artes
            'artes/index.php' => 'Lista Artes',
            'artes/create.php' => 'Criar Arte',
            'artes/show.php' => 'Detalhe Arte',
            'artes/edit.php' => 'Editar Arte',
            
            // Clientes
            'clientes/index.php' => 'Lista Clientes',
            'clientes/create.php' => 'Criar Cliente',
            'clientes/show.php' => 'Detalhe Cliente',
            'clientes/edit.php' => 'Editar Cliente',
            
            // Vendas
            'vendas/index.php' => 'Lista Vendas',
            'vendas/create.php' => 'Criar Venda',
            'vendas/show.php' => 'Detalhe Venda',
            'vendas/edit.php' => 'Editar Venda',
            'vendas/relatorio.php' => 'Relatório Vendas',
            
            // Metas
            'metas/index.php' => 'Lista Metas',
            'metas/create.php' => 'Criar Meta',
            'metas/show.php' => 'Detalhe Meta',
            'metas/edit.php' => 'Editar Meta',
            
            // Tags
            'tags/index.php' => 'Lista Tags',
            'tags/create.php' => 'Criar Tag',
            'tags/show.php' => 'Detalhe Tag',
            'tags/edit.php' => 'Editar Tag',
        ];
        
        $viewsPath = BASE_PATH . '/views';
        
        foreach ($views as $arquivo => $descricao) {
            $path = $viewsPath . '/' . $arquivo;
            $existe = file_exists($path);
            
            $testes[str_replace(['/', '.'], '_', $arquivo)] = [
                'nome' => $arquivo,
                'descricao' => $descricao,
                'status' => $existe ? 'pass' : 'fail',
                'mensagem' => $existe ? 'Existe' : 'Não encontrado'
            ];
        }
        
        return $testes;
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    private function testarConexaoBanco(): array
    {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $database = $_ENV['DB_DATABASE'] ?? 'artflow2_db';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            return ['success' => true, 'pdo' => $pdo, 'message' => "Conectado: {$database}@{$host}"];
        } catch (PDOException $e) {
            return ['success' => false, 'pdo' => null, 'message' => $e->getMessage()];
        }
    }
    
    private function tabelaExiste(string $tabela): bool
    {
        if (!$this->pdo) return false;
        try {
            $this->pdo->query("SELECT 1 FROM {$tabela} LIMIT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function contarRegistros(string $tabela): int
    {
        if (!$this->pdo) return 0;
        try {
            return (int) $this->pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    private function getColunas(string $tabela): array
    {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("DESCRIBE {$tabela}");
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (PDOException $e) {
            return [];
        }
    }
    
    private function testarRotaHttp(string $url): array
    {
        $start = microtime(true);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => false,
        ]);
        
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $time = round((microtime(true) - $start) * 1000);
        
        return ['code' => $code, 'time' => $time];
    }
    
    public function getResumo(): array
    {
        $total = 0;
        $passou = 0;
        $falhou = 0;
        $avisos = 0;
        
        // Soma resultados de todas as categorias
        foreach ($this->results as $categoria => $testes) {
            if (!is_array($testes)) continue;
            foreach ($testes as $teste) {
                if (!isset($teste['status'])) continue;
                $total++;
                match($teste['status']) {
                    'pass' => $passou++,
                    'fail' => $falhou++,
                    'warn' => $avisos++,
                    default => null
                };
            }
        }
        
        return [
            'total' => $total,
            'passou' => $passou,
            'falhou' => $falhou,
            'avisos' => $avisos,
            'taxa_sucesso' => $total > 0 ? round(($passou / ($passou + $falhou)) * 100, 1) : 0
        ];
    }
}
