<?php

namespace App\Core;

/**
 * ============================================
 * RESPONSE - Objeto de Resposta HTTP
 * ============================================
 * 
 * Encapsula a resposta HTTP enviada ao cliente:
 * - Conteúdo (HTML, JSON, etc)
 * - Status code (200, 404, 500, etc)
 * - Headers (Content-Type, Location, etc)
 * 
 * BENEFÍCIOS:
 * - Fluent interface (encadeamento de métodos)
 * - Métodos auxiliares para JSON, redirect, etc
 * - Fácil testar respostas
 * 
 * USO:
 * return (new Response())
 *     ->setContent('<h1>Hello</h1>')
 *     ->setStatusCode(200)
 *     ->header('X-Custom', 'value');
 */
class Response
{
    /**
     * Conteúdo da resposta
     */
    private string $content;
    
    /**
     * Código de status HTTP
     */
    private int $statusCode;
    
    /**
     * Headers HTTP
     */
    private array $headers;
    
    /**
     * Dados para flash na sessão
     */
    private array $flashData = [];
    
    /**
     * Construtor
     * 
     * @param string $content Conteúdo da resposta
     * @param int $statusCode Código HTTP (200, 404, etc)
     * @param array $headers Headers adicionais
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    // ==========================================
    // SETTERS (Fluent Interface)
    // ==========================================
    
    /**
     * Define conteúdo
     * 
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Define status code
     * 
     * @param int $code
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * Adiciona header
     * 
     * @param string $key
     * @param string $value
     * @return self
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }
    
    // ==========================================
    // GETTERS
    // ==========================================
    
    /**
     * Obtém conteúdo
     * 
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
    
    /**
     * Obtém status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * Obtém headers
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    // ==========================================
    // MÉTODOS DE RESPOSTA
    // ==========================================
    
    /**
     * Cria resposta JSON
     * 
     * @param array|object $data Dados a serem codificados
     * @param int $statusCode Código HTTP
     * @return self
     */
    public function json($data, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->header('Content-Type', 'application/json; charset=utf-8');
        $this->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        return $this;
    }
    
    /**
     * Cria resposta de redirect
     * 
     * @param string $url URL de destino
     * @param int $statusCode 302 (temporário) ou 301 (permanente)
     * @return self
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->setStatusCode($statusCode);
        $this->header('Location', $url);
        
        return $this;
    }
    
    /**
     * Volta para página anterior
     * 
     * @return self
     */
    public function back(): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this->redirect($referer);
    }
    
    /**
     * Adiciona dados flash para a sessão
     * 
     * Flash data fica disponível apenas na próxima requisição.
     * Útil para mensagens de sucesso/erro após redirect.
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function with(string $key, $value): self
    {
        $this->flashData[$key] = $value;
        return $this;
    }
    
    /**
     * Adiciona erros de validação ao flash
     * 
     * @param array $errors
     * @return self
     */
    public function withErrors(array $errors): self
    {
        return $this->with('errors', $errors);
    }
    
    /**
     * Adiciona dados do input ao flash (para repopular form)
     * 
     * @return self
     */
    public function withInput(): self
    {
        return $this->with('old', $_POST);
    }
    
    /**
     * Define resposta de erro 404
     * 
     * @param string $message
     * @return self
     */
    public function notFound(string $message = 'Página não encontrada'): self
    {
        $this->setStatusCode(404);
        $this->setContent($this->errorTemplate(404, $message));
        
        return $this;
    }
    
    /**
     * Define resposta de erro 500
     * 
     * @param string $message
     * @return self
     */
    public function serverError(string $message = 'Erro interno do servidor'): self
    {
        $this->setStatusCode(500);
        $this->setContent($this->errorTemplate(500, $message));
        
        return $this;
    }
    
    /**
     * Define resposta de erro 403
     * 
     * @param string $message
     * @return self
     */
    public function forbidden(string $message = 'Acesso negado'): self
    {
        $this->setStatusCode(403);
        $this->setContent($this->errorTemplate(403, $message));
        
        return $this;
    }
    
    // ==========================================
    // ENVIO DA RESPOSTA
    // ==========================================
    
    /**
     * Envia a resposta ao cliente
     * 
     * Este método:
     * 1. Define status code
     * 2. Envia headers
     * 3. Salva flash data na sessão
     * 4. Envia conteúdo
     */
    public function send(): void
    {
        // Salva flash data na sessão ANTES de enviar headers
        if (!empty($this->flashData)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['_flash'] = $this->flashData;
        }
        
        // Define status code
        http_response_code($this->statusCode);
        
        // Envia headers
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        // Envia conteúdo
        echo $this->content;
    }
    
    // ==========================================
    // HELPERS PRIVADOS
    // ==========================================
    
    /**
     * Template básico de erro
     * 
     * @param int $code
     * @param string $message
     * @return string
     */
    private function errorTemplate(int $code, string $message): string
    {
        $titles = [
            400 => 'Requisição Inválida',
            401 => 'Não Autorizado',
            403 => 'Acesso Negado',
            404 => 'Não Encontrado',
            500 => 'Erro do Servidor',
        ];
        
        $title = $titles[$code] ?? 'Erro';
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$code} - {$title}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .error-container {
                    background: white;
                    padding: 60px;
                    border-radius: 16px;
                    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
                    text-align: center;
                    max-width: 500px;
                }
                .error-code {
                    font-size: 120px;
                    font-weight: bold;
                    color: #764ba2;
                    line-height: 1;
                }
                .error-title {
                    font-size: 24px;
                    color: #333;
                    margin: 20px 0 10px;
                }
                .error-message {
                    color: #666;
                    margin-bottom: 30px;
                }
                .back-link {
                    display: inline-block;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 500;
                    transition: transform 0.2s;
                }
                .back-link:hover {
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-code">{$code}</div>
                <h1 class="error-title">{$title}</h1>
                <p class="error-message">{$message}</p>
                <a href="javascript:history.back()" class="back-link">← Voltar</a>
            </div>
        </body>
        </html>
        HTML;
    }
    
    // ==========================================
    // MÉTODOS ESTÁTICOS (Factory)
    // ==========================================
    
    /**
     * Cria resposta HTML
     * 
     * @param string $content
     * @param int $statusCode
     * @return self
     */
    public static function html(string $content, int $statusCode = 200): self
    {
        return (new self($content, $statusCode))
            ->header('Content-Type', 'text/html; charset=utf-8');
    }
    
    /**
     * Cria resposta de sucesso JSON
     * 
     * @param mixed $data
     * @param string $message
     * @return self
     */
    public static function success($data = null, string $message = 'Sucesso'): self
    {
        return (new self())->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Cria resposta de erro JSON
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return self
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): self
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return (new self())->json($response, $statusCode);
    }
}
