<?php
/**
 * ============================================
 * ARTFLOW 2.0 - REDIRECIONADOR
 * ============================================
 * 
 * Este arquivo encaminha todas as requisições 
 * para public/index.php (ponto de entrada real).
 * 
 * Necessário quando o servidor aponta para a raiz
 * do projeto ao invés da pasta public/.
 */

// Carrega o ponto de entrada real
require_once __DIR__ . '/public/index.php';
