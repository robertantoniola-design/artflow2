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
 * ATUALIZAÇÃO (01/02/2026):
 * - index(): Agora auto-finaliza metas passadas ao carregar
 * - index(): Anos dinâmicos vindos do banco (não mais fixos)
 * - index(): Default é ano atual (não 'todos')
 * - getAnosDisponiveis(): Usa MetaService em vez de gerar fixo
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
     * GET /metas?ano=2025
     * 
     * ATUALIZADO:
     * - Auto-finaliza metas de meses passados
     * - Busca anos reais do banco para navegação
     * - Default: ano atual
     */
    public function index(Request $request): Response
    {
        // NOVO: Auto-finaliza metas de meses passados
        // Garante que metas antigas tenham status = 'finalizado'
        $this->metaService->finalizarMetasPassadas();
        
        // Busca anos disponíveis (do banco + ano atual)
        $anosDisponiveis = $this->metaService->getAnosDisponiveis();
        
        // Determina o ano selecionado
        // - Se veio ?ano=XXXX na URL, usa esse
        // - Se não, usa o ano atual como padrão
        $anoSelecionado = $request->get('ano');
        
        if (empty($anoSelecionado) || !is_numeric($anoSelecionado)) {
            // Default: ano atual
            $anoSelecionado = (int) date('Y');
        } else {
            $anoSelecionado = (int) $anoSelecionado;
        }
        
        // Filtros para o service
        $filtros = [
            'ano' => $anoSelecionado,
            'limite' => $request->get('limite', 12)
        ];
        
        $metas = $this->metaService->listar($filtros);
        $estatisticas = $this->metaService->getEstatisticas();
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(fn($m) => $m->toArray(), $metas),
                'estatisticas' => $estatisticas,
                'ano_selecionado' => $anoSelecionado,
                'anos_disponiveis' => $anosDisponiveis
            ]);
        }
        
        return $this->view('metas/index', [
            'titulo' => 'Metas Mensais',
            'metas' => $metas,
            'estatisticas' => $estatisticas,
            'anoSelecionado' => $anoSelecionado,
            'anos' => $anosDisponiveis   // Agora é array de inteiros do banco
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
     * Salva nova meta
     * POST /metas
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only([
                'mes_ano', 'valor_meta', 
                'horas_diarias_ideal', 'dias_trabalho_semana'
            ]);
            
            $meta = $this->metaService->criar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Meta criada!', ['id' => $meta->getId()]);
            }
            
            $this->flashSuccess('Meta de R$ ' . number_format($meta->getValorMeta(), 2, ',', '.') . ' criada com sucesso!');
            return $this->redirectTo('/metas');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
        }
    }
    
    /**
     * Detalhes da meta
     * GET /metas/{id}
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $meta = $this->metaService->buscar($id);
            $projecao = $this->metaService->calcularProjecao($meta);
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'data' => $meta->toArray(),
                    'projecao' => $projecao
                ]);
            }
            
            return $this->view('metas/show', [
                'titulo' => 'Meta - ' . $meta->getMesAnoFormatado(),
                'meta' => $meta,
                'projecao' => $projecao
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
            return $this->notFound('Meta não encontrada');
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
                return $this->success('Meta atualizada!', $meta->toArray());
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
                    'porcentagem' => $meta->getPorcentagemAtingida(),
                    'status' => $meta->getStatus()
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
    
    /**
     * REMOVIDO: getAnosDisponiveis() antigo que gerava anos fixos
     * Agora usa MetaService::getAnosDisponiveis() que consulta o banco
     */
    
    private function getMesesDisponiveis(): array
    {
        $meses = [];
        $dataAtual = new \DateTime();
        
        // Próximos 12 meses a partir do atual
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