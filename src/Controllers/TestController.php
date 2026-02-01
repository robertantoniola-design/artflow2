<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\TestService;

/**
 * ============================================
 * TEST CONTROLLER
 * ============================================
 * 
 * Controller para página de diagnóstico e testes.
 * Acesso via: /testes
 * 
 * IMPORTANTE: Remover ou proteger em produção!
 */
class TestController extends BaseController
{
    private TestService $testService;
    
    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }
    
    /**
     * Página principal de testes
     * GET /testes
     */
    public function index(Request $request): Response
    {
        $modulo = $request->get('modulo', 'all');
        
        // Executa todos os testes ou módulo específico
        if ($modulo === 'all') {
            $resultados = $this->testService->runAllTests();
        } else {
            $resultados = [
                $modulo => $this->testService->runTest($modulo)
            ];
        }
        
        // Calcula resumo
        $resumo = $this->calcularResumo($resultados);
        
        return $this->view('testes/index', [
            'titulo' => 'Sistema de Testes',
            'resultados' => $resultados,
            'resumo' => $resumo,
            'moduloAtual' => $modulo
        ]);
    }
    
    /**
     * API JSON para testes
     * GET /testes/api
     */
    public function api(Request $request): Response
    {
        $modulo = $request->get('modulo', 'all');
        
        if ($modulo === 'all') {
            $resultados = $this->testService->runAllTests();
        } else {
            $resultados = $this->testService->runTest($modulo);
        }
        
        return $this->json([
            'success' => true,
            'modulo' => $modulo,
            'resultados' => $resultados,
            'resumo' => $this->calcularResumo(['data' => $resultados])
        ]);
    }
    
    /**
     * Calcula resumo dos resultados
     */
    private function calcularResumo(array $resultados): array
    {
        $total = 0;
        $passou = 0;
        $falhou = 0;
        $avisos = 0;
        $skip = 0;
        
        foreach ($resultados as $categoria => $testes) {
            if ($categoria === 'resumo' || !is_array($testes)) continue;
            
            foreach ($testes as $teste) {
                if (!isset($teste['status'])) continue;
                $total++;
                
                match($teste['status']) {
                    'pass' => $passou++,
                    'fail' => $falhou++,
                    'warn' => $avisos++,
                    'skip' => $skip++,
                    default => null
                };
            }
        }
        
        $testesReais = $passou + $falhou;
        
        return [
            'total' => $total,
            'passou' => $passou,
            'falhou' => $falhou,
            'avisos' => $avisos,
            'skip' => $skip,
            'taxa_sucesso' => $testesReais > 0 ? round(($passou / $testesReais) * 100, 1) : 0,
            'status_geral' => $falhou === 0 ? 'success' : ($falhou < 5 ? 'warning' : 'danger')
        ];
    }
}
