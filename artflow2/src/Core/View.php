<?php

namespace App\Core;

use Exception;

/**
 * ============================================
 * VIEW - Motor de Templates
 * ============================================
 * 
 * Renderiza views PHP com suporte a:
 * - Layouts (templates base)
 * - Seções (yield/section)
 * - Passagem de dados
 * - Componentes reutilizáveis
 * 
 * USO:
 * View::render('artes/index', ['artes' => $artes]);
 * View::render('artes/show', ['arte' => $arte], 'layouts/main');
 */
class View
{
    /**
     * Caminho base das views
     */
    private static string $basePath = '';
    
    /**
     * Layout padrão
     */
    private static string $defaultLayout = 'layouts/main';
    
    /**
     * Dados compartilhados com todas as views
     */
    private static array $shared = [];
    
    /**
     * Seções definidas
     */
    private static array $sections = [];
    
    /**
     * Seção sendo capturada
     */
    private static ?string $currentSection = null;
    
    /**
     * Configura caminho base das views
     * 
     * @param string $path
     */
    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }
    
    /**
     * Define layout padrão
     * 
     * @param string $layout
     */
    public static function setDefaultLayout(string $layout): void
    {
        self::$defaultLayout = $layout;
    }
    
    /**
     * Compartilha dados com todas as views
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }
    
    /**
     * Renderiza uma view
     * 
     * @param string $view Caminho da view (ex: 'artes/index')
     * @param array $data Dados para a view
     * @param string|null $layout Layout a usar (null = padrão, false = sem layout)
     * @return string HTML renderizado
     * @throws Exception
     */
    public static function render(string $view, array $data = [], $layout = null): string
    {
        // Resolve caminho do arquivo
        $viewPath = self::resolveViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new Exception("View não encontrada: {$view} ({$viewPath})");
        }
        
        // Mescla dados compartilhados com dados específicos
        $data = array_merge(self::$shared, $data);
        
        // Adiciona helpers úteis
        $data['request'] = new Request();
        $data['old'] = fn($key, $default = '') => $_SESSION['_flash']['old'][$key] ?? $default;
        $data['errors'] = $_SESSION['_flash']['errors'] ?? [];
        $data['success'] = $_SESSION['_flash']['success'] ?? null;
        $data['error'] = $_SESSION['_flash']['error'] ?? null;
        
        // Limpa flash após usar
        unset($_SESSION['_flash']);
        
        // Renderiza view
        $content = self::renderFile($viewPath, $data);
        
        // Se não usar layout, retorna conteúdo direto
        if ($layout === false) {
            return $content;
        }
        
        // Aplica layout
        $layoutName = $layout ?? self::$defaultLayout;
        $layoutPath = self::resolveViewPath($layoutName);
        
        if (file_exists($layoutPath)) {
            // Define conteúdo principal para o layout
            self::$sections['content'] = $content;
            
            // Passa o conteúdo para os dados do layout
            $data['content'] = $content;
            
            // Renderiza layout
            $content = self::renderFile($layoutPath, $data);
        }
        
        return $content;
    }
    
    /**
     * Renderiza e retorna Response
     * 
     * @param string $view
     * @param array $data
     * @param string|null $layout
     * @return Response
     */
    public static function make(string $view, array $data = [], $layout = null): Response
    {
        $content = self::render($view, $data, $layout);
        return new Response($content);
    }
    
    /**
     * Renderiza arquivo PHP em contexto isolado
     * 
     * @param string $filePath
     * @param array $data
     * @return string
     */
    private static function renderFile(string $filePath, array $data): string
    {
        // Extrai dados como variáveis ($data['artes'] vira $artes)
        extract($data);
        
        // Inicia buffer de saída
        ob_start();
        
        // Inclui arquivo (executa PHP)
        include $filePath;
        
        // Retorna conteúdo do buffer
        return ob_get_clean();
    }
    
    /**
     * Resolve caminho completo da view
     * 
     * @param string $view
     * @return string
     */
    private static function resolveViewPath(string $view): string
    {
        // Remove extensão se existir
        $view = preg_replace('/\.php$/', '', $view);
        
        return self::$basePath . '/' . $view . '.php';
    }
    
    // ==========================================
    // SEÇÕES (para layouts)
    // ==========================================
    
    /**
     * Inicia uma seção
     * 
     * @param string $name Nome da seção
     */
    public static function startSection(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }
    
    /**
     * Finaliza seção atual
     */
    public static function endSection(): void
    {
        if (self::$currentSection !== null) {
            self::$sections[self::$currentSection] = ob_get_clean();
            self::$currentSection = null;
        }
    }
    
    /**
     * Exibe conteúdo de uma seção
     * 
     * @param string $name Nome da seção
     * @param string $default Conteúdo padrão se seção não existir
     * @return string
     */
    public static function yield(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }
    
    // ==========================================
    // COMPONENTES
    // ==========================================
    
    /**
     * Inclui um componente
     * 
     * @param string $component Caminho do componente (ex: 'components/alert')
     * @param array $data Dados para o componente
     */
    public static function component(string $component, array $data = []): void
    {
        $path = self::resolveViewPath($component);
        
        if (file_exists($path)) {
            extract($data);
            include $path;
        }
    }
    
    // ==========================================
    // HELPERS PARA VIEWS
    // ==========================================
    
    /**
     * Escapa string para HTML (previne XSS)
     * 
     * @param string|null $value
     * @return string
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Formata valor monetário
     * 
     * @param float|null $value
     * @return string
     */
    public static function money(?float $value): string
    {
        return 'R$ ' . number_format($value ?? 0, 2, ',', '.');
    }
    
    /**
     * Formata data
     * 
     * @param string|null $date
     * @param string $format
     * @return string
     */
    public static function date(?string $date, string $format = 'd/m/Y'): string
    {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }
    
    /**
     * Formata data e hora
     * 
     * @param string|null $datetime
     * @return string
     */
    public static function datetime(?string $datetime): string
    {
        return self::date($datetime, 'd/m/Y H:i');
    }
    
    /**
     * Gera URL completa
     * 
     * @param string $path
     * @return string
     */
    public static function url(string $path = ''): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost/artflow2';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
    
    /**
     * Gera URL para asset (CSS, JS, imagens)
     * 
     * @param string $path
     * @return string
     */
    public static function asset(string $path): string
    {
        return self::url('public/assets/' . ltrim($path, '/'));
    }
    
    /**
     * Verifica se é a rota atual (para menus ativos)
     * 
     * @param string $path
     * @return bool
     */
    public static function isActive(string $path): bool
    {
        $request = new Request();
        $currentUri = $request->uri();
        
        // Match exato ou começa com
        return $currentUri === $path || str_starts_with($currentUri, $path . '/');
    }
    
    /**
     * Retorna classe 'active' se for rota atual
     * 
     * @param string $path
     * @return string
     */
    public static function activeClass(string $path): string
    {
        return self::isActive($path) ? 'active' : '';
    }
}
