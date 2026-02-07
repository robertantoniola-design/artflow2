<?php

namespace App\Core;

use Exception;

/**
 * ============================================
 * ROUTER - Sistema de Rotas
 * ============================================
 * 
 * Responsável por:
 * - Registrar rotas (GET, POST, etc)
 * - Fazer match entre URL e rota
 * - Extrair parâmetros da URL
 * - Chamar o handler correto (Controller@method)
 * 
 * SUPORTA:
 * - Rotas estáticas: /artes
 * - Parâmetros dinâmicos: /artes/{id}
 * - Grupos de rotas (futuro)
 * 
 * USO:
 * $router->get('/artes', [ArteController::class, 'index']);
 * $router->get('/artes/{id}', [ArteController::class, 'show']);
 * $router->post('/artes', [ArteController::class, 'store']);
 */
class Router
{
    /**
     * Rotas registradas
     * Estrutura: [method => [uri => handler]]
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => []
    ];
    
    /**
     * Container de DI
     */
    private Container $container;
    
    /**
     * Prefixo de grupo atual
     */
    private string $groupPrefix = '';
    
    /**
     * Construtor
     * 
     * @param Container $container Container de DI
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    // ==========================================
    // REGISTRO DE ROTAS
    // ==========================================
    
    /**
     * Registra rota GET
     * 
     * @param string $uri
     * @param array|callable $handler
     * @return self
     */
    public function get(string $uri, $handler): self
    {
        return $this->addRoute('GET', $uri, $handler);
    }
    
    /**
     * Registra rota POST
     * 
     * @param string $uri
     * @param array|callable $handler
     * @return self
     */
    public function post(string $uri, $handler): self
    {
        return $this->addRoute('POST', $uri, $handler);
    }
    
    /**
     * Registra rota PUT
     * 
     * @param string $uri
     * @param array|callable $handler
     * @return self
     */
    public function put(string $uri, $handler): self
    {
        return $this->addRoute('PUT', $uri, $handler);
    }
    
    /**
     * Registra rota DELETE
     * 
     * @param string $uri
     * @param array|callable $handler
     * @return self
     */
    public function delete(string $uri, $handler): self
    {
        return $this->addRoute('DELETE', $uri, $handler);
    }
    
    /**
     * Registra rota PATCH
     * 
     * @param string $uri
     * @param array|callable $handler
     * @return self
     */
    public function patch(string $uri, $handler): self
    {
        return $this->addRoute('PATCH', $uri, $handler);
    }
    
    /**
     * Adiciona rota internamente
     * 
     * @param string $method
     * @param string $uri
     * @param array|callable $handler
     * @return self
     */
    private function addRoute(string $method, string $uri, $handler): self
    {
        // Aplica prefixo de grupo se existir
        $uri = $this->groupPrefix . '/' . ltrim($uri, '/');
        $uri = '/' . ltrim($uri, '/'); // Garante / no início
        
        $this->routes[$method][$uri] = $handler;
        
        return $this;
    }
    
    /**
     * Agrupa rotas com prefixo comum
     * 
     * EXEMPLO:
     * $router->group('/admin', function($router) {
     *     $router->get('/users', ...);  // /admin/users
     *     $router->get('/posts', ...);  // /admin/posts
     * });
     * 
     * @param string $prefix
     * @param callable $callback
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix .= $prefix;
        
        $callback($this);
        
        $this->groupPrefix = $previousPrefix;
    }
    
    /**
     * Registra rotas de recurso (CRUD completo)
     * 
     * GERA:
     * GET    /artes           -> index
     * GET    /artes/criar     -> create
     * POST   /artes           -> store
     * GET    /artes/{id}      -> show
     * GET    /artes/{id}/editar -> edit
     * PUT    /artes/{id}      -> update (via POST com _method=PUT)
     * DELETE /artes/{id}      -> destroy (via POST com _method=DELETE)
     * 
     * @param string $uri
     * @param string $controller
     */
    public function resource(string $uri, string $controller): void
    {
        $uri = '/' . trim($uri, '/');
        
        $this->get($uri, [$controller, 'index']);
        $this->get("{$uri}/criar", [$controller, 'create']);
        $this->post($uri, [$controller, 'store']);
        $this->get("{$uri}/{id}", [$controller, 'show']);
        $this->get("{$uri}/{id}/editar", [$controller, 'edit']);
        
        // Update: aceita tanto PUT quanto POST (para compatibilidade)
        $this->put("{$uri}/{id}", [$controller, 'update']);
        $this->post("{$uri}/{id}/atualizar", [$controller, 'update']);
        
        // Delete: aceita tanto DELETE quanto POST (para compatibilidade)
        $this->delete("{$uri}/{id}", [$controller, 'destroy']);
        $this->post("{$uri}/{id}/deletar", [$controller, 'destroy']);
    }
    
    // ==========================================
    // DISPATCH (Execução)
    // ==========================================
    
    /**
     * Despacha a requisição para o handler correto
     * 
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->uri();
        
        // Suporte a _method para PUT/DELETE via POST
        if ($method === 'POST' && $request->has('_method')) {
            $method = strtoupper($request->get('_method'));
        }
        
        // Procura match nas rotas
        foreach ($this->routes[$method] ?? [] as $routeUri => $handler) {
            $params = $this->matchRoute($routeUri, $uri);
            
            if ($params !== false) {
                return $this->callHandler($handler, $request, $params);
            }
        }
        
        // Nenhuma rota encontrada
        return (new Response())->notFound("Página '{$uri}' não encontrada");
    }
    
    /**
     * Verifica se URI corresponde à rota e extrai parâmetros
     * 
     * @param string $routeUri Padrão da rota (ex: /artes/{id})
     * @param string $requestUri URI da requisição (ex: /artes/123)
     * @return array|false Array de parâmetros ou false se não match
     */
    private function matchRoute(string $routeUri, string $requestUri): array|false
    {
        // Converte /artes/{id} para regex /artes/([^/]+)
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $routeUri);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $requestUri, $matches)) {
            // Remove primeiro elemento (match completo)
            array_shift($matches);
            
            // Extrai nomes dos parâmetros
            preg_match_all('/\{([a-zA-Z_]+)\}/', $routeUri, $paramNames);
            
            // Combina nomes com valores
            // CORREÇÃO: Converte strings numéricas para int automaticamente
            // Resolve TypeError em controllers que declaram (int $id)
            // Ex: URL /metas/24 → $matches = ["24"] → convertido para int 24
            $params = [];
            foreach ($paramNames[1] as $index => $name) {
                $value = $matches[$index] ?? null;
                
                // Se o valor é puramente numérico, converte para int
                // ctype_digit() retorna true para "123", false para "abc" ou "12a"
                if ($value !== null && ctype_digit($value)) {
                    $value = (int) $value;
                }
                
                $params[$name] = $value;
            }
            
            return $params;
        }
        
        return false;
    }
    
    /**
     * Chama o handler da rota
     * 
     * @param array|callable $handler
     * @param Request $request
     * @param array $params
     * @return Response
     */
    private function callHandler($handler, Request $request, array $params): Response
    {
        try {
            // Se é Closure
            if ($handler instanceof \Closure) {
                $response = $handler($request, ...$params);
            }
            // Se é array [Controller::class, 'method']
            elseif (is_array($handler)) {
                [$controllerClass, $method] = $handler;
                
                // Resolve controller via container (injeção de dependências)
                $controller = $this->container->make($controllerClass);
                
                // Chama método passando request e parâmetros
                $response = $controller->$method($request, ...$params);
            }
            else {
                throw new Exception("Handler de rota inválido");
            }
            
            // Se retornou string, converte para Response
            if (is_string($response)) {
                return new Response($response);
            }
            
            // Se não retornou Response, cria um vazio
            if (!$response instanceof Response) {
                return new Response('');
            }
            
            return $response;
            
        } catch (\App\Exceptions\ValidationException $e) {
            // Erro de validação - volta com erros
            return (new Response())
                ->back()
                ->withErrors($e->getErrors())
                ->withInput();
                
        } catch (\App\Exceptions\NotFoundException $e) {
            return (new Response())->notFound($e->getMessage());
            
        } catch (Exception $e) {
            // Erro genérico
            if ($_ENV['APP_DEBUG'] ?? false) {
                return (new Response())->serverError(
                    "<pre>Erro: {$e->getMessage()}\n\n{$e->getTraceAsString()}</pre>"
                );
            }
            
            return (new Response())->serverError();
        }
    }
    
    // ==========================================
    // DEBUG
    // ==========================================
    
    /**
     * Lista todas as rotas registradas (útil para debug)
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
