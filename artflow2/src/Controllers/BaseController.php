<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * ============================================
 * BASE CONTROLLER
 * ============================================
 * 
 * Classe base para todos os controllers.
 * Fornece métodos auxiliares para renderização,
 * redirecionamento e respostas JSON.
 * 
 * Controllers específicos herdam desta classe:
 * class ArteController extends BaseController { ... }
 */
abstract class BaseController
{
    /**
     * Layout padrão para views
     */
    protected ?string $layout = 'layouts/main';
    
    // ==========================================
    // MÉTODOS DE RENDERIZAÇÃO
    // ==========================================
    
    /**
     * Renderiza uma view
     * 
     * @param string $view Caminho da view (ex: 'artes/index')
     * @param array $data Dados para a view
     * @return Response
     */
    protected function view(string $view, array $data = []): Response
    {
        return View::make($view, $data, $this->layout);
    }
    
    /**
     * Renderiza view sem layout
     * 
     * @param string $view
     * @param array $data
     * @return Response
     */
    protected function partial(string $view, array $data = []): Response
    {
        return View::make($view, $data, false);
    }
    
    // ==========================================
    // MÉTODOS DE REDIRECIONAMENTO
    // ==========================================
    
    /**
     * Redireciona para URL
     * 
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return (new Response())->redirect($url, $status);
    }
    
    /**
     * Redireciona para rota nomeada
     * 
     * @param string $path
     * @param array $params
     * @return Response
     */
    protected function redirectTo(string $path, array $params = []): Response
    {
        $url = View::url($path);
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->redirect($url);
    }
    
    /**
     * Volta para página anterior
     * 
     * @return Response
     */
    protected function back(): Response
    {
        return (new Response())->back();
    }
    
    // ==========================================
    // MÉTODOS JSON
    // ==========================================
    
    /**
     * Retorna resposta JSON
     * 
     * @param array $data
     * @param int $statusCode
     * @return Response
     */
    protected function json(array $data, int $statusCode = 200): Response
    {
        return (new Response())->json($data, $statusCode);
    }
    
    /**
     * Retorna JSON de sucesso
     * 
     * @param mixed $data
     * @param string $message
     * @return Response
     */
    protected function success($data = null, string $message = 'Sucesso'): Response
    {
        return Response::success($data, $message);
    }
    
    /**
     * Retorna JSON de erro
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return Response
     */
    protected function error(string $message, int $statusCode = 400, array $errors = []): Response
    {
        return Response::error($message, $statusCode, $errors);
    }
    
    // ==========================================
    // MÉTODOS DE FLASH MESSAGES
    // ==========================================
    
    /**
     * Define mensagem de sucesso
     * 
     * @param string $message
     */
    protected function flashSuccess(string $message): void
    {
        $this->flash('success', $message);
    }
    
    /**
     * Define mensagem de erro
     * 
     * @param string $message
     */
    protected function flashError(string $message): void
    {
        $this->flash('error', $message);
    }
    
    /**
     * Define flash message genérica
     * 
     * @param string $key
     * @param mixed $value
     */
    protected function flash(string $key, $value): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['_flash'][$key] = $value;
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    /**
     * Verifica se é requisição AJAX
     * 
     * @param Request $request
     * @return bool
     */
    protected function isAjax(Request $request): bool
    {
        return $request->isAjax() || $request->wantsJson();
    }
    
    /**
     * Valida CSRF token
     * 
     * @param Request $request
     * @return bool
     */
    protected function validateCsrf(Request $request): bool
    {
        $token = $request->get('_token') ?? $request->get('csrf_token');
        $sessionToken = $_SESSION['_csrf_token'] ?? null;
        
        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Gera CSRF token
     * 
     * @return string
     */
    protected function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
        
        return $token;
    }
    
    /**
     * Obtém CSRF token atual ou gera novo
     * 
     * @return string
     */
    protected function getCsrfToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            return $this->generateCsrfToken();
        }
        
        return $_SESSION['_csrf_token'];
    }
    
    /**
     * Retorna resposta 404
     * 
     * @param string $message
     * @return Response
     */
    protected function notFound(string $message = 'Recurso não encontrado'): Response
    {
        return (new Response())->notFound($message);
    }
    
    /**
     * Retorna resposta 403
     * 
     * @param string $message
     * @return Response
     */
    protected function forbidden(string $message = 'Acesso negado'): Response
    {
        return (new Response())->forbidden($message);
    }
}
