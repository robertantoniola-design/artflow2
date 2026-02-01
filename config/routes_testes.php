<?php
/**
 * ============================================
 * ROTAS DE TESTES - ADICIONAR AO routes.php
 * ============================================
 * 
 * Copie estas rotas para o final do arquivo config/routes.php
 * 
 * IMPORTANTE: Remover ou proteger em produção!
 */

use App\Controllers\TestController;

// ============================================
// ROTAS DE TESTES E DIAGNÓSTICO
// ============================================
// ATENÇÃO: Remover ou proteger com senha em produção!

$router->get('/testes', [TestController::class, 'index']);
$router->get('/testes/api', [TestController::class, 'api']);
