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
 * Controller base com métodos utilitários.
 * Todos os controllers herdam desta classe.
 * 
 * CORREÇÃO (29/01/2026):
 * - validateCsrf() agora aceita _csrf, _token e csrf_token
 * - Garante compatibilidade com todas as views
 */
abstract class BaseController
{
    // ==========================================
    // MÉTODOS DE RENDERIZAÇÃO
    // ==========================================
    
    /**
     * Renderiza uma view
     * 
     * @param string $view Nome da view (ex: 'artes/index')
     * @param array $data Dados para a view
     * @return Response
     */
    protected function view(string $view, array $data = []): Response
    {
        $content = View::render($view, $data);
        return new Response($content);
    }
    
    /**
     * Redireciona para URL
     * 
     * @param string $url
     * @return Response
     */
    protected function redirect(string $url): Response
    {
        return (new Response())->redirect($url);
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
        $url = url($path);
        
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
     * Define mensagem de aviso
     * 
     * @param string $message
     */
    protected function flashWarning(string $message): void
    {
        $this->flash('warning', $message);
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
     * CORREÇÃO: Agora aceita múltiplos nomes de campo para compatibilidade:
     * - _token (padrão recomendado)
     * - _csrf (usado em algumas views)
     * - csrf_token (alternativo)
     * 
     * @param Request $request
     * @return bool
     */
    protected function validateCsrf(Request $request): bool
    {
        // CORREÇÃO: Busca token em múltiplos campos possíveis
        // Ordem de prioridade: _token > _csrf > csrf_token
        $token = $request->get('_token') 
              ?? $request->get('_csrf') 
              ?? $request->get('csrf_token');
        
        // Obtém token da sessão
        $sessionToken = $_SESSION['_csrf_token'] ?? null;
        
        // Valida: ambos devem existir e ser iguais (timing-safe)
        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Gera CSRF token
     * 
     * @return string
     */
    protected function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
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
    
    /**
     * Retorna resposta 500
     * 
     * @param string $message
     * @return Response
     */
    protected function serverError(string $message = 'Erro interno do servidor'): Response
    {
        return (new Response())->serverError($message);
    }
}
