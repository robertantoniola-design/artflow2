<?php
/**
 * ============================================
 * FUNÇÕES AUXILIARES GLOBAIS
 * ============================================
 * 
 * Funções helper disponíveis em toda a aplicação.
 * Carregadas automaticamente pelo Composer (autoload.files)
 * 
 * CORREÇÃO (29/01/2026):
 * - csrf_field() agora usa _token (padronizado)
 * - Mantém compatibilidade com o BaseController corrigido
 */

use App\Core\Application;
use App\Core\Response;
use App\Core\View;
use App\Core\Request;

// ==========================================
// HELPERS DE APLICAÇÃO
// ==========================================

if (!function_exists('app')) {
    /**
     * Obtém instância da aplicação ou serviço do container
     * 
     * @param string|null $abstract
     * @return mixed
     */
    function app(?string $abstract = null)
    {
        $app = Application::getInstance();
        
        if ($abstract === null) {
            return $app;
        }
        
        return $app->getContainer()->make($abstract);
    }
}

if (!function_exists('env')) {
    /**
     * Obtém variável de ambiente
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Obtém configuração
     * 
     * @param string $key Formato: 'arquivo.chave' ou 'arquivo'
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        static $configs = [];
        
        $parts = explode('.', $key);
        $file = $parts[0];
        $configKey = $parts[1] ?? null;
        
        // Carrega arquivo se não estiver em cache
        if (!isset($configs[$file])) {
            $path = base_path("config/{$file}.php");
            $configs[$file] = file_exists($path) ? require $path : [];
        }
        
        if ($configKey === null) {
            return $configs[$file];
        }
        
        return $configs[$file][$configKey] ?? $default;
    }
}

// ==========================================
// HELPERS DE CAMINHOS
// ==========================================

if (!function_exists('base_path')) {
    /**
     * Obtém caminho base da aplicação
     * 
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return app()->getBasePath() . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Obtém caminho da pasta storage
     * 
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Obtém caminho da pasta public
     * 
     * @param string $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

// ==========================================
// HELPERS DE URL
// ==========================================

if (!function_exists('url')) {
    /**
     * Gera URL completa
     * 
     * @param string $path
     * @return string
     */
    function url(string $path = ''): string
    {
        return View::url($path);
    }
}

if (!function_exists('asset')) {
    /**
     * Gera URL para asset
     * 
     * @param string $path
     * @return string
     */
    function asset(string $path): string
    {
        return View::asset($path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Cria resposta de redirect
     * 
     * @param string $url
     * @param int $status
     * @return Response
     */
    function redirect(string $url, int $status = 302): Response
    {
        return (new Response())->redirect($url, $status);
    }
}

if (!function_exists('back')) {
    /**
     * Volta para página anterior
     * 
     * @return Response
     */
    function back(): Response
    {
        return (new Response())->back();
    }
}

// ==========================================
// HELPERS DE VIEW
// ==========================================

if (!function_exists('view')) {
    /**
     * Renderiza uma view
     * 
     * @param string $name
     * @param array $data
     * @param string|null $layout
     * @return Response
     */
    function view(string $name, array $data = [], $layout = null): Response
    {
        return View::make($name, $data, $layout);
    }
}

if (!function_exists('e')) {
    /**
     * Escapa HTML (previne XSS)
     * 
     * @param string|null $value
     * @return string
     */
    function e(?string $value): string
    {
        return View::e($value);
    }
}

// ==========================================
// HELPERS DE FORMATAÇÃO
// ==========================================

if (!function_exists('money')) {
    /**
     * Formata valor monetário (R$)
     * 
     * @param float|null $value
     * @return string
     */
    function money(?float $value): string
    {
        return View::money($value);
    }
}

if (!function_exists('date_br')) {
    /**
     * Formata data no padrão brasileiro
     * 
     * @param string|null $date
     * @param string $format
     * @return string
     */
    function date_br(?string $date, string $format = 'd/m/Y'): string
    {
        return View::date($date, $format);
    }
}

if (!function_exists('datetime_br')) {
    /**
     * Formata data e hora no padrão brasileiro
     * 
     * @param string|null $datetime
     * @return string
     */
    function datetime_br(?string $datetime): string
    {
        return View::datetime($datetime);
    }
}

// ==========================================
// HELPERS DE SESSÃO/FLASH
// ==========================================

if (!function_exists('session')) {
    /**
     * Acessa a sessão
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function session(?string $key = null, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Obtém flash message(s)
     * 
     * @param string|null $key Se null, retorna todas as flash messages
     * @return mixed
     */
    function flash(?string $key = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Se não passou key, retorna todas as flash messages
        if ($key === null) {
            $flash = $_SESSION['_flash'] ?? null;
            unset($_SESSION['_flash']);
            return $flash;
        }
        
        // Retorna flash message específica
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

if (!function_exists('old')) {
    /**
     * Obtém valor antigo do formulário (após erro de validação)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, $default = '')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    /**
     * Obtém erro de validação para um campo
     * 
     * @param string $key
     * @return string|null
     */
    function errors(string $key): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['_errors'][$key] ?? null;
    }
}

if (!function_exists('has_error')) {
    /**
     * Verifica se campo tem erro de validação
     * 
     * @param string $key
     * @return bool
     */
    function has_error(string $key): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['_errors'][$key]);
    }
}

// ==========================================
// HELPERS DE SEGURANÇA (CSRF)
// ==========================================

if (!function_exists('csrf_token')) {
    /**
     * Gera ou obtém token CSRF da sessão
     * 
     * @return string
     */
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Gera campo hidden com token CSRF
     * 
     * CORREÇÃO: Agora usa _token (padrão) em vez de _csrf
     * Isso garante compatibilidade total com o BaseController
     * 
     * @return string
     */
    function csrf_field(): string
    {
        // CORREÇÃO: Usa _token como nome do campo (padrão)
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Verifica se token CSRF é válido
     * 
     * @param string $token
     * @return bool
     */
    function verify_csrf(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
    }
}

// ==========================================
// HELPERS DE DEBUG
// ==========================================

if (!function_exists('dd')) {
    /**
     * Dump and die - para debug
     * 
     * @param mixed ...$vars
     */
    function dd(...$vars): void
    {
        echo '<pre style="background:#1e1e1e;color:#dcdcdc;padding:15px;margin:10px;border-radius:5px;font-family:monospace;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n---\n";
        }
        echo '</pre>';
        die();
    }
}

if (!function_exists('dump')) {
    /**
     * Dump sem die - para debug
     * 
     * @param mixed ...$vars
     */
    function dump(...$vars): void
    {
        echo '<pre style="background:#1e1e1e;color:#dcdcdc;padding:15px;margin:10px;border-radius:5px;font-family:monospace;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n---\n";
        }
        echo '</pre>';
    }
}

if (!function_exists('logger')) {
    /**
     * Log simples para arquivo
     * 
     * @param string $message
     * @param string $level
     */
    function logger(string $message, string $level = 'info'): void
    {
        $logFile = storage_path('logs/app.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
}

// ==========================================
// HELPERS DE TEXTO
// ==========================================

if (!function_exists('str_limit')) {
    /**
     * Limita string a um número de caracteres
     * 
     * @param string|null $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    function str_limit(?string $value, int $limit = 100, string $end = '...'): string
    {
        if ($value === null) {
            return '';
        }
        
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        
        return mb_substr($value, 0, $limit) . $end;
    }
}

if (!function_exists('slug')) {
    /**
     * Gera slug a partir de string
     * 
     * @param string $value
     * @return string
     */
    function slug(string $value): string
    {
        // Remove acentos
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        // Converte para minúsculas
        $slug = strtolower($slug);
        // Remove caracteres especiais
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        // Substitui espaços por hífens
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        // Remove hífens do início e fim
        return trim($slug, '-');
    }
}
