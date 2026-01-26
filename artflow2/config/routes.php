<?php
/**
 * ============================================
 * ARTFLOW 2.0 - DEFINIÇÃO DE ROTAS
 * ============================================
 * 
 * Arquivo carregado pelo public/index.php
 * $router é uma instância de App\Core\Router
 * 
 * Métodos disponíveis:
 * - $router->get($uri, $handler)
 * - $router->post($uri, $handler)
 * - $router->put($uri, $handler)
 * - $router->delete($uri, $handler)
 * - $router->resource($uri, $controller) // Gera CRUD completo
 * - $router->group($prefix, $callback)
 */

use App\Core\Request;
use App\Core\Response;
use App\Controllers\DashboardController;
use App\Controllers\ArteController;
use App\Controllers\ClienteController;
use App\Controllers\VendaController;
use App\Controllers\MetaController;
use App\Controllers\TagController;

// ============================================
// ROTA INICIAL - DASHBOARD
// ============================================
$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// API do Dashboard (AJAX)
$router->get('/dashboard/refresh', [DashboardController::class, 'refresh']);
$router->get('/dashboard/artes', [DashboardController::class, 'estatisticasArtes']);
$router->get('/dashboard/vendas', [DashboardController::class, 'estatisticasVendas']);
$router->get('/dashboard/meta', [DashboardController::class, 'progressoMeta']);
$router->get('/dashboard/atividades', [DashboardController::class, 'atividadesRecentes']);
$router->get('/dashboard/busca', [DashboardController::class, 'busca']);

// ============================================
// ROTAS DE ARTES
// ============================================
// Resource gera automaticamente:
// GET    /artes          -> index   (listar)
// GET    /artes/create   -> create  (form criar)
// POST   /artes          -> store   (salvar novo)
// GET    /artes/{id}     -> show    (detalhes)
// GET    /artes/{id}/edit -> edit   (form editar)
// PUT    /artes/{id}     -> update  (atualizar)
// DELETE /artes/{id}     -> destroy (excluir)
$router->resource('/artes', ArteController::class);

// Rotas adicionais de artes
$router->post('/artes/{id}/status', [ArteController::class, 'alterarStatus']);
$router->post('/artes/{id}/horas', [ArteController::class, 'adicionarHoras']);

// ============================================
// ROTAS DE CLIENTES
// ============================================
$router->resource('/clientes', ClienteController::class);

// API de clientes para autocomplete
$router->get('/clientes/buscar', [ClienteController::class, 'buscar']);

// ============================================
// ROTAS DE VENDAS
// ============================================
$router->resource('/vendas', VendaController::class);

// Relatório de vendas
$router->get('/vendas/relatorio', [VendaController::class, 'relatorio']);

// ============================================
// ROTAS DE METAS
// ============================================
$router->resource('/metas', MetaController::class);

// Rotas adicionais de metas
$router->get('/metas/resumo', [MetaController::class, 'resumo']);
$router->post('/metas/{id}/recalcular', [MetaController::class, 'recalcular']);

// ============================================
// ROTAS DE TAGS
// ============================================
$router->resource('/tags', TagController::class);

// API de tags
$router->get('/tags/buscar', [TagController::class, 'buscar']);
$router->get('/tags/select', [TagController::class, 'select']);
$router->post('/tags/rapida', [TagController::class, 'criarRapida']);

// ============================================
// ROTAS DE BUSCA GLOBAL
// ============================================
$router->get('/busca', function(Request $request) {
    $termo = $request->get('q', '');
    // TODO: Implementar SearchController
    return new Response("Busca por: " . htmlspecialchars($termo));
});

// ============================================
// ROTA DE TESTE (remover em produção)
// ============================================
$router->get('/teste', function(Request $request) {
    return new Response('
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>ArtFlow 2.0 - Teste</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    text-align: center; 
                    padding: 50px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    min-height: 100vh;
                    margin: 0;
                }
                h1 { font-size: 48px; margin: 0 0 20px; }
                p { font-size: 20px; margin: 10px 0; }
                .success { 
                    background: rgba(255,255,255,0.2); 
                    padding: 20px; 
                    border-radius: 10px;
                    display: inline-block;
                    margin-top: 20px;
                }
                .check { color: #4ade80; font-size: 24px; }
            </style>
        </head>
        <body>
            <h1>🎨 ArtFlow 2.0</h1>
            <p>Sistema Profissional de Gestão Artística</p>
            <div class="success">
                <p><span class="check">✅</span> Core funcionando</p>
                <p><span class="check">✅</span> Rotas configuradas</p>
                <p><span class="check">✅</span> Autoload PSR-4 ativo</p>
            </div>
        </body>
        </html>
    ');
});
