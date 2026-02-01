<?php

namespace App\Core;

/**
 * ============================================
 * APPLICATION - Bootstrap da Aplicação
 * ============================================
 * 
 * Classe principal que:
 * - Carrega configurações (.env)
 * - Configura timezone, sessão, etc
 * - Inicializa Container de DI
 * - Registra bindings essenciais
 * - Cria e configura Router
 * - Executa a aplicação
 * 
 * USO:
 * $app = new Application(__DIR__);
 * $router = $app->getRouter();
 * // ... registrar rotas ...
 * $app->run();
 */
class Application
{
    /**
     * Caminho base da aplicação
     */
    private string $basePath;
    
    /**
     * Container de DI
     */
    private Container $container;
    
    /**
     * Router
     */
    private Router $router;
    
    /**
     * Instância singleton
     */
    private static ?Application $instance = null;
    
    /**
     * Construtor
     * 
     * @param string $basePath Caminho raiz da aplicação
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        self::$instance = $this;
        
        // 1. Carrega variáveis de ambiente
        $this->loadEnvironment();
        
        // 2. Configura PHP (timezone, erros, etc)
        $this->configure();
        
        // 3. Inicia sessão
        $this->startSession();
        
        // 4. Cria container de DI
        $this->container = new Container();
        
        // 5. Registra bindings essenciais
        $this->registerCoreBindings();
        
        // 6. Configura View
        $this->configureView();
        
        // 7. Cria Router
        $this->router = new Router($this->container);
    }
    
    /**
     * Obtém instância da aplicação
     * 
     * @return self|null
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }
    
    /**
     * Carrega variáveis de ambiente do arquivo .env
     */
    private function loadEnvironment(): void
    {
        $envFile = $this->basePath . '/.env';
        
        if (!file_exists($envFile)) {
            // Tenta .env.example se .env não existir
            $envFile = $this->basePath . '/.env.example';
            if (!file_exists($envFile)) {
                return;
            }
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignora comentários
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            
            // Ignora linhas sem =
            if (!str_contains($line, '=')) {
                continue;
            }
            
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove aspas se existirem
            $value = trim($value, '"\'');
            
            // Define na $_ENV e putenv
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
    
    /**
     * Configura PHP (timezone, erros, etc)
     */
    private function configure(): void
    {
        // Timezone
        $timezone = $_ENV['TIMEZONE'] ?? 'America/Sao_Paulo';
        date_default_timezone_set($timezone);
        
        // Configuração de erros baseada no ambiente
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
        
        // Encoding UTF-8
        mb_internal_encoding('UTF-8');
    }
    
    /**
     * Inicia sessão
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurações de sessão
            $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 120) * 60; // Em segundos
            
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
    }
    
    /**
     * Registra bindings essenciais no container
     */
    private function registerCoreBindings(): void
    {
        // Application como singleton
        $this->container->instance(Application::class, $this);
        
        // Database como singleton
        $this->container->singleton(Database::class, function () {
            return Database::getInstance();
        });
        
        // Request (nova instância a cada requisição)
        $this->container->bind(Request::class, function () {
            return new Request();
        });
    }
    
    /**
     * Configura motor de views
     */
    private function configureView(): void
    {
        View::setBasePath($this->basePath . '/views');
        View::setDefaultLayout('layouts/main');
        
        // Compartilha dados globais com views
        View::share('appName', $_ENV['APP_NAME'] ?? 'ArtFlow');
        View::share('appUrl', $_ENV['APP_URL'] ?? 'http://localhost/artflow2');
    }
    
    // ==========================================
    // GETTERS
    // ==========================================
    
    /**
     * Obtém Router
     * 
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Obtém Container
     * 
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Obtém caminho base
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }
    
    /**
     * Obtém caminho para diretório específico
     * 
     * @param string $path
     * @return string
     */
    public function path(string $path = ''): string
    {
        return $this->basePath . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    // ==========================================
    // EXECUÇÃO
    // ==========================================
    
    /**
     * Executa a aplicação
     * 
     * 1. Cria Request
     * 2. Despacha para Router
     * 3. Envia Response
     */
    public function run(): void
    {
        try {
            // Cria objeto Request
            $request = $this->container->make(Request::class);
            
            // Despacha para Router (retorna Response)
            $response = $this->router->dispatch($request);
            
            // Envia resposta ao cliente
            $response->send();
            
        } catch (\Throwable $e) {
            // Tratamento de erro fatal
            $this->handleException($e);
        }
    }
    
    /**
     * Trata exceções não capturadas
     * CORREÇÃO: Agora exibe mensagem da exceção anterior (previous exception)
        * para que erros como PDOException dentro de DatabaseException sejam visíveis.
        * 
     * @param \Throwable $e
     */
        private function handleException(\Throwable $e): void
    {
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Log do erro (MELHORADO: inclui previous exception)
        $logFile = $this->basePath . '/storage/logs/error.log';
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        // ADICIONADO: Log da exceção anterior (ex: PDOException dentro de DatabaseException)
        if ($e->getPrevious()) {
            $logMessage .= sprintf(
                "  Caused by: %s: %s\n",
                get_class($e->getPrevious()),
                $e->getPrevious()->getMessage()
            );
        }
        
        // ADICIONADO: Log de info extra do DatabaseException
        if (method_exists($e, 'getDebugInfo')) {
            $debugInfo = $e->getDebugInfo();
            if (!empty($debugInfo['query'])) {
                $logMessage .= "  Query: {$debugInfo['query']}\n";
            }
            if (!empty($debugInfo['params'])) {
                $logMessage .= "  Params: " . json_encode($debugInfo['params']) . "\n";
            }
        }
        
        $logMessage .= "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
        
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Resposta de erro
        http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        
        if ($debug) {
            echo "<div style='font-family: monospace; max-width: 900px; margin: 20px auto; padding: 20px;'>";
            echo "<h1 style='color: #dc3545;'>❌ Erro Fatal</h1>";
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 15px; margin-bottom: 15px;'>";
            echo "<p><strong>" . get_class($e) . ":</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            
            // ADICIONADO: Mostra causa original (ex: PDOException)
            if ($e->getPrevious()) {
                echo "<p style='color: #721c24; margin-top: 10px;'>";
                echo "<strong>Causa original (" . get_class($e->getPrevious()) . "):</strong><br>";
                echo htmlspecialchars($e->getPrevious()->getMessage());
                echo "</p>";
            }
            
            echo "</div>";
            
            // ADICIONADO: Mostra query SQL se disponível
            if (method_exists($e, 'getQuery') && $e->getQuery()) {
                echo "<div style='background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 15px; margin-bottom: 15px;'>";
                echo "<strong>Query SQL:</strong><br>";
                echo "<code>" . htmlspecialchars($e->getQuery()) . "</code>";
                echo "</div>";
            }
            
            echo "<p><strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            echo "<h1>Erro do Servidor</h1>";
            echo "<p>Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.</p>";
        }
    }
}
