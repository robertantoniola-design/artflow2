<?php

/**
 * ============================================
 * FUNÇÕES HELPERS GLOBAIS
 * ============================================
 * 
 * Funções utilitárias disponíveis em qualquer lugar da aplicação.
 * Carregadas automaticamente via composer (autoload files).
 */

use App\Core\Application;
use App\Core\View;
use App\Core\Response;

// ==========================================
// HELPERS DE APLICAÇÃO
// ==========================================

if (!function_exists('app')) {
    /**
     * Obtém instância da aplicação ou resolve do container
     * 
     * @param string|null $abstract Classe a resolver
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
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Converte valores especiais
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value
        };
    }
}

if (!function_exists('config')) {
    /**
     * Obtém valor de configuração
     * 
     * @param string $key Chave no formato 'arquivo.chave'
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        static $configs = [];
        
        $parts = explode('.', $key, 2);
        $file = $parts[0];
        $configKey = $parts[1] ?? null;
        
        // Carrega arquivo de configuração se ainda não carregado
        if (!isset($configs[$file])) {
            $path = app()->getBasePath() . "/config/{$file}.php";
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
     * Obtém valor antigo do input (para repopular forms)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, $default = '')
    {
        return $_SESSION['_flash']['old'][$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    /**
     * Obtém erros de validação
     * 
     * @param string|null $key
     * @return mixed
     */
    function errors(?string $key = null)
    {
        $errors = $_SESSION['_flash']['errors'] ?? [];
        
        if ($key === null) {
            return $errors;
        }
        
        return $errors[$key] ?? null;
    }
}

if (!function_exists('has_error')) {
    /**
     * Verifica se campo tem erro
     * 
     * @param string $key
     * @return bool
     */
    function has_error(string $key): bool
    {
        return isset($_SESSION['_flash']['errors'][$key]);
    }
}

// ==========================================
// HELPERS DE DEBUG
// ==========================================

if (!function_exists('dd')) {
    /**
     * Dump and die - imprime e para execução
     * 
     * @param mixed ...$vars
     */
    function dd(...$vars): void
    {
        echo "<pre style='background:#1e1e1e;color:#d4d4d4;padding:20px;margin:10px;border-radius:8px;font-size:14px;'>";
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n";
        }
        echo "</pre>";
        exit;
    }
}

if (!function_exists('dump')) {
    /**
     * Dump - imprime sem parar execução
     * 
     * @param mixed ...$vars
     */
    function dump(...$vars): void
    {
        echo "<pre style='background:#1e1e1e;color:#d4d4d4;padding:20px;margin:10px;border-radius:8px;font-size:14px;'>";
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n";
        }
        echo "</pre>";
    }
}

// ==========================================
// HELPERS DE STRING
// ==========================================

if (!function_exists('str_slug')) {
    /**
     * Converte string para slug (URL amigável)
     * 
     * @param string $text
     * @param string $separator
     * @return string
     */
    function str_slug(string $text, string $separator = '-'): string
    {
        // Remove acentos
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        
        // Converte para minúsculas
        $text = strtolower($text);
        
        // Remove caracteres especiais
        $text = preg_replace('/[^a-z0-9\-\s]/', '', $text);
        
        // Substitui espaços por separador
        $text = preg_replace('/[\s\-]+/', $separator, $text);
        
        // Remove separadores do início e fim
        return trim($text, $separator);
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limita tamanho da string
     * 
     * @param string $text
     * @param int $limit
     * @param string $end
     * @return string
     */
    function str_limit(string $text, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        
        return mb_substr($text, 0, $limit) . $end;
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
     * @return string
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
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
