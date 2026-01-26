<?php

namespace App\Core;

/**
 * ============================================
 * REQUEST - Objeto de Requisição HTTP
 * ============================================
 * 
 * Encapsula todos os dados da requisição HTTP:
 * - $_GET (query string)
 * - $_POST (formulários)
 * - $_FILES (uploads)
 * - $_COOKIE (cookies)
 * - $_SERVER (informações do servidor)
 * 
 * BENEFÍCIOS:
 * - Interface limpa e consistente
 * - Métodos auxiliares úteis
 * - Facilita testes (pode ser mockado)
 * - Sanitização centralizada
 * 
 * USO:
 * $request = new Request();
 * $nome = $request->get('nome');
 * $email = $request->get('email', 'default@email.com');
 */
class Request
{
    /**
     * Dados de query string ($_GET)
     */
    private array $query;
    
    /**
     * Dados de formulário ($_POST)
     */
    private array $request;
    
    /**
     * Informações do servidor ($_SERVER)
     */
    private array $server;
    
    /**
     * Arquivos enviados ($_FILES)
     */
    private array $files;
    
    /**
     * Cookies ($_COOKIE)
     */
    private array $cookies;
    
    /**
     * Dados da sessão (referência)
     */
    private array $session;
    
    /**
     * Corpo da requisição (raw)
     */
    private ?string $content = null;
    
    /**
     * Construtor - captura dados da requisição
     */
    public function __construct()
    {
        $this->query = $_GET ?? [];
        $this->request = $_POST ?? [];
        $this->server = $_SERVER ?? [];
        $this->files = $_FILES ?? [];
        $this->cookies = $_COOKIE ?? [];
        
        // Inicia sessão se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->session = &$_SESSION;
    }
    
    // ==========================================
    // MÉTODOS DE ACESSO A DADOS
    // ==========================================
    
    /**
     * Obtém todos os dados (GET + POST)
     * 
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }
    
    /**
     * Obtém um valor específico
     * 
     * @param string $key Nome do campo
     * @param mixed $default Valor padrão se não existir
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default;
    }
    
    /**
     * Obtém valor do POST
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(string $key, $default = null)
    {
        return $this->request[$key] ?? $default;
    }
    
    /**
     * Obtém valor da query string (GET)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }
    
    /**
     * Verifica se um campo existe
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }
    
    /**
     * Verifica se campo existe e não está vazio
     * 
     * @param string $key
     * @return bool
     */
    public function filled(string $key): bool
    {
        $value = $this->get($key);
        return $value !== null && $value !== '' && $value !== [];
    }
    
    /**
     * Obtém apenas campos específicos
     * 
     * @param array $keys Lista de campos desejados
     * @return array
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }
    
    /**
     * Obtém todos exceto campos específicos
     * 
     * @param array $keys Lista de campos a excluir
     * @return array
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }
    
    /**
     * Obtém valor com sanitização básica (trim + htmlspecialchars)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        $value = $this->get($key, $default);
        
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        
        return $value;
    }
    
    // ==========================================
    // MÉTODOS HTTP
    // ==========================================
    
    /**
     * Obtém método HTTP (GET, POST, PUT, DELETE, etc)
     * 
     * @return string
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }
    
    /**
     * Verifica se é requisição GET
     * 
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }
    
    /**
     * Verifica se é requisição POST
     * 
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }
    
    /**
     * Verifica se é requisição AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
    
    /**
     * Verifica se é requisição JSON
     * 
     * @return bool
     */
    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
    
    // ==========================================
    // MÉTODOS DE URL/URI
    // ==========================================
    
    /**
     * Obtém URI da requisição (sem query string)
     * 
     * @return string
     */
    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove base path se necessário (para XAMPP)
        $basePath = $this->getBasePath();
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Garante que começa com /
        return '/' . ltrim($uri, '/');
    }
    
    /**
     * Obtém URL completa
     * 
     * @return string
     */
    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        return "{$scheme}://{$host}{$uri}";
    }
    
    /**
     * Obtém base path da aplicação
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        // Para XAMPP: /artflow2
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        return dirname($scriptName);
    }
    
    /**
     * Verifica se é conexão segura (HTTPS)
     * 
     * @return bool
     */
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server['SERVER_PORT'] ?? 80) == 443;
    }
    
    // ==========================================
    // MÉTODOS DE ARQUIVOS
    // ==========================================
    
    /**
     * Obtém arquivo enviado
     * 
     * @param string $key Nome do campo
     * @return array|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }
    
    /**
     * Verifica se tem arquivo enviado
     * 
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file !== null && $file['error'] === UPLOAD_ERR_OK;
    }
    
    // ==========================================
    // MÉTODOS DE SESSÃO
    // ==========================================
    
    /**
     * Obtém valor da sessão
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function session(string $key, $default = null)
    {
        return $this->session[$key] ?? $default;
    }
    
    /**
     * Define valor na sessão
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setSession(string $key, $value): void
    {
        $this->session[$key] = $value;
    }
    
    /**
     * Obtém flash message (e remove da sessão)
     * 
     * @param string $key
     * @return mixed
     */
    public function flash(string $key)
    {
        $value = $this->session['_flash'][$key] ?? null;
        unset($this->session['_flash'][$key]);
        return $value;
    }
    
    // ==========================================
    // MÉTODOS DE COOKIES
    // ==========================================
    
    /**
     * Obtém cookie
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }
    
    // ==========================================
    // MÉTODOS DE SERVIDOR
    // ==========================================
    
    /**
     * Obtém IP do cliente
     * 
     * @return string
     */
    public function ip(): string
    {
        // Considera proxy reverso
        return $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['HTTP_CLIENT_IP'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }
    
    /**
     * Obtém User Agent
     * 
     * @return string
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Obtém corpo raw da requisição
     * 
     * @return string
     */
    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input') ?: '';
        }
        return $this->content;
    }
    
    /**
     * Decodifica corpo JSON
     * 
     * @return array
     */
    public function json(): array
    {
        $content = $this->getContent();
        return json_decode($content, true) ?? [];
    }
}
