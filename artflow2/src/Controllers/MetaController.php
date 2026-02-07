<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\MetaService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * META CONTROLLER
 * ============================================
 * 
 * Controller responsável pelas operações de Metas Mensais.
 * 
 * MELHORIA 2 + 3 (05/02/2026): index() passa estatísticasAno e desempenhoAnual
 * MELHORIA 5 (06/02/2026): store() suporta criação recorrente
 */
class MetaController extends BaseController
{
    private MetaService $metaService;
    
    public function __construct(MetaService $metaService)
    {
        $this->metaService = $metaService;
    }
    
    /**
     * Lista todas as metas
     * GET /metas
     * 
     * MELHORIA 2+3: Passa estatísticasAno e desempenhoAnual para a view
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'ano' => $request->get('ano', date('Y')),
            'limite' => $request->get('limite', 12)
        ];
        
        $anoSelecionado = (int) $filtros['ano'];
        
        $metas = $this->metaService->listar($filtros);
        $estatisticas = $this->metaService->getEstatisticas();
        
        // MELHORIA 2: Estatísticas do ano selecionado (cards)
        $estatisticasAno = $this->metaService->getEstatisticasAno($anoSelecionado);
        
        // MELHORIA 3: Dados para gráfico de evolução anual
        $desempenhoAnual = $this->metaService->getDesempenhoAnual($anoSelecionado);
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(fn($m) => $m->toArray(), $metas),
                'estatisticas' => $estatisticas,
                'estatisticasAno' => $estatisticasAno,
                'desempenhoAnual' => $desempenhoAnual
            ]);
        }
        
        return $this->view('metas/index', [
            'titulo' => 'Metas Mensais',
            'metas' => $metas,
            'estatisticas' => $estatisticas,
            'anoSelecionado' => $anoSelecionado,
            'anosDisponiveis' => $this->getAnosDisponiveis(),
            'estatisticasAno' => $estatisticasAno,       // MELHORIA 2
            'desempenhoAnual' => $desempenhoAnual         // MELHORIA 3
        ]);
    }
    
    /**
     * Formulário de nova meta
     * GET /metas/criar
     */
    public function create(Request $request): Response
    {
        return $this->view('metas/create', [
            'titulo' => 'Nova Meta',
            'meses' => $this->getMesesDisponiveis()
        ]);
    }
    
    /**
     * Salva nova meta (simples ou recorrente)
     * POST /metas
     * 
     * MELHORIA 5: Suporta criação recorrente quando checkbox 'recorrente'
     * está marcado e quantidade_meses > 1. Meses já existentes são ignorados.
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            // Dados base da meta
            $dados = $request->only([
                'mes_ano', 'valor_meta', 
                'horas_diarias_ideal', 'dias_trabalho_semana'
            ]);
            
            // =====================================================
            // MELHORIA 5: Verifica se é criação recorrente
            // Usa $_POST diretamente porque checkbox desmarcado
            // não envia campo, e $request->get() pode não capturá-lo
            // corretamente no merge GET+POST.
            // =====================================================
            $recorrente = isset($_POST['recorrente']) && $_POST['recorrente'] === '1';
            $quantidadeMeses = isset($_POST['quantidade_meses']) 
                ? (int) $_POST['quantidade_meses'] 
                : 1;
            
            if ($recorrente && $quantidadeMeses > 1) {
                // --- CRIAÇÃO RECORRENTE ---
                $resultado = $this->metaService->criarRecorrente($dados, $quantidadeMeses);
                
                // Monta mensagem de feedback detalhada
                $totalCriadas = count($resultado['criadas']);
                $totalIgnoradas = count($resultado['ignoradas']);
                $totalErros = count($resultado['erros']);
                
                // Monta mensagem de feedback detalhada
                // Nota: usa flashSuccess para todos os cenários porque
                // flashWarning não é exibido corretamente em todas as views.
                if ($totalCriadas > 0) {
                    $mensagem = sprintf('✅ %d meta(s) criada(s) com sucesso.', $totalCriadas);
                    
                    if ($totalIgnoradas > 0) {
                        $mensagem .= sprintf(
                            ' ⚠️ %d mês(es) ignorado(s) (já existiam).', 
                            $totalIgnoradas
                        );
                    }
                    
                    if ($totalErros > 0) {
                        $mensagem .= sprintf(' ❌ %d erro(s) encontrado(s).', $totalErros);
                    }
                    
                    $this->flashSuccess($mensagem);
                    
                } elseif ($totalIgnoradas > 0) {
                    // Todos os meses já existem
                    $this->flashSuccess(
                        '⚠️ Nenhuma meta criada. Todos os ' . $totalIgnoradas 
                        . ' meses selecionados já possuem meta definida.'
                    );
                    
                } else {
                    // Nenhuma criada e nenhuma ignorada = erro
                    $this->flashError(
                        'Não foi possível criar as metas. Verifique os dados informados.'
                    );
                }
                
                if ($request->wantsJson()) {
                    return $this->json([
                        'success' => $totalCriadas > 0,
                        'data' => $resultado
                    ]);
                }
                
                return $this->redirectTo('/metas');
             
                
                
            } else {
                // --- CRIAÇÃO SIMPLES (comportamento original) ---
                $meta = $this->metaService->criar($dados);
                
                if ($request->wantsJson()) {
                    return $this->success('Meta criada!', ['id' => $meta->getId()]);
                }
                
                $this->flashSuccess(
                    'Meta de R$ ' . number_format($meta->getValorMeta(), 2, ',', '.') . ' criada!'
                );
                return $this->redirectTo('/metas');
            }
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
            
        } catch (\Exception $e) {
            // Captura qualquer exceção inesperada (DateTime, PDO, etc.)
            $this->flashError('Erro ao criar meta: ' . $e->getMessage());
            return $this->redirectTo('/metas/criar');
        }
    }
    
    /**
     * Exibe detalhes da meta com projeções e histórico de transições
     * GET /metas/{id}
     * 
     * MELHORIA 6: Agora inclui historicoTransicoes na view
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $meta = $this->metaService->buscar($id);
            $projecao = $this->metaService->calcularProjecao($meta);
            $horasNecessarias = $this->metaService->calcularHorasNecessarias($meta);
            
            // MELHORIA 6: Buscar histórico de transições de status
            $historicoTransicoes = $this->metaService->getHistoricoTransicoes($id);
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'data' => $meta->toArray(),
                    'projecao' => $projecao,
                    'horas_necessarias' => $horasNecessarias,
                    'historico_transicoes' => $historicoTransicoes  // MELHORIA 6
                ]);
            }
            
            return $this->view('metas/show', [
                'titulo' => 'Meta: ' . $this->formatarMesAno($meta->getMesAno()),
                'meta' => $meta,
                'projecao' => $projecao,
                'horasNecessarias' => $horasNecessarias,
                'historicoTransicoes' => $historicoTransicoes  // MELHORIA 6
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Meta não encontrada');
        }
    }
    
    /**
     * Formulário de edição
     * GET /metas/{id}/editar
     */
    public function edit(Request $request, int $id): Response
    {
        try {
            $meta = $this->metaService->buscar($id);
            
            return $this->view('metas/edit', [
                'titulo' => 'Editar Meta',
                'meta' => $meta
            ]);
            
        } catch (NotFoundException $e) {
            $this->flashError('Meta não encontrada');
            return $this->redirectTo('/metas');
        }
    }
    
    /**
     * Atualiza meta
     * PUT /metas/{id}
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only([
                'valor_meta', 'horas_diarias_ideal', 'dias_trabalho_semana'
            ]);
            
            $meta = $this->metaService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Meta atualizada!');
            }
            
            $this->flashSuccess('Meta atualizada com sucesso!');
            return $this->redirectTo('/metas/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Meta não encontrada');
        }
    }
    
    /**
     * Remove meta
     * DELETE /metas/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $this->metaService->remover($id);
            
            if ($request->wantsJson()) {
                return $this->success('Meta removida!');
            }
            
            $this->flashSuccess('Meta removida com sucesso!');
            return $this->redirectTo('/metas');
            
        } catch (NotFoundException $e) {
            return $this->notFound('Meta não encontrada');
        }
    }
    
    /**
     * Recalcula valor realizado baseado nas vendas
     * POST /metas/{id}/recalcular
     */
    public function recalcular(Request $request, int $id): Response
    {
        try {
            $meta = $this->metaService->recalcularRealizado($id);
            
            if ($request->wantsJson()) {
                return $this->success('Valor recalculado!', [
                    'valor_realizado' => $meta->getValorRealizado(),
                    'porcentagem' => $meta->getPorcentagemAtingida()
                ]);
            }
            
            $this->flashSuccess('Valor recalculado com sucesso!');
            return $this->back();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Meta não encontrada');
        }
    }
    
    /**
     * Retorna resumo para dashboard (AJAX)
     * GET /metas/resumo
     */
    public function resumo(Request $request): Response
    {
        $resumo = $this->metaService->getResumoDashboard();
        return $this->json($resumo);
    }
    
    // ==========================================
    // HELPERS
    // ==========================================
    
    private function getAnosDisponiveis(): array
    {
        $anoAtual = (int) date('Y');
        $anos = [];
        
        for ($i = $anoAtual - 2; $i <= $anoAtual + 1; $i++) {
            $anos[$i] = $i;
        }
        
        return $anos;
    }
    
    private function getMesesDisponiveis(): array
    {
        $meses = [];
        $dataAtual = new \DateTime();
        
        for ($i = 0; $i < 12; $i++) {
            $data = clone $dataAtual;
            $data->modify("+{$i} months");
            
            $chave = $data->format('Y-m');
            $valor = $this->formatarMesAno($data->format('Y-m-01'));
            
            $meses[$chave] = $valor;
        }
        
        return $meses;
    }
    
    private function formatarMesAno(string $mesAno): string
    {
        $mesesNomes = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];
        
        $partes = explode('-', $mesAno);
        $ano = $partes[0];
        $mes = $partes[1];
        
        return ($mesesNomes[$mes] ?? $mes) . '/' . $ano;
    }
}