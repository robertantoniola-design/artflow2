<?php

namespace App\Services;

use App\Models\Meta;
use App\Repositories\MetaRepository;
use App\Repositories\VendaRepository;
use App\Validators\MetaValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * META SERVICE
 * ============================================
 * 
 * Camada de lógica de negócio para Metas Mensais.
 * 
 * ATUALIZAÇÃO (01/02/2026):
 * - criar(): define status = 'iniciado' ao criar meta
 * - Novo: finalizarMetasPassadas() para auto-finalizar meses anteriores
 * - Novo: getAnosDisponiveis() centraliza lógica de anos
 * - Lógica de transição de status integrada ao fluxo existente
 * 
 * MELHORIA 1 — Status "Superado" (01/02/2026):
 * - recalcularRealizado(): agora detecta transição para 'superado' (>= 120%)
 * - determinarStatusInicial(): para metas retroativas, detecta 'superado'
 * - criar(): verifica se meta retroativa já qualifica para 'superado'
 */
class MetaService
{
    private MetaRepository $metaRepository;
    private VendaRepository $vendaRepository;
    private MetaValidator $validator;
    
    public function __construct(
        MetaRepository $metaRepository,
        VendaRepository $vendaRepository,
        MetaValidator $validator
    ) {
        $this->metaRepository = $metaRepository;
        $this->vendaRepository = $vendaRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // OPERAÇÕES CRUD
    // ==========================================
    
    /**
     * Lista todas as metas
     * 
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        // Filtro por ano
        if (!empty($filtros['ano'])) {
            return $this->metaRepository->findByAno((int) $filtros['ano']);
        }
        
        // Metas recentes (fallback)
        return $this->metaRepository->getRecentes($filtros['limite'] ?? 12);
    }
    
    /**
     * Busca meta por ID
     * 
     * @param int $id
     * @return Meta
     * @throws NotFoundException
     */
    public function buscar(int $id): Meta
    {
        return $this->metaRepository->findOrFail($id);
    }
    
    /**
     * Busca meta do mês atual
     * 
     * @return Meta|null
     */
    public function buscarMesAtual(): ?Meta
    {
        return $this->metaRepository->findMesAtual();
    }
    
    /**
     * Cria nova meta
     * 
     * ATUALIZADO (Melhoria 1 — Superado):
     * Agora ao criar meta retroativa, verifica se já qualifica para 'superado':
     * - Mês passado com vendas >= 120% da meta → status 'superado'
     * - Mês passado com vendas < 120% → status 'finalizado'
     * - Mês atual/futuro → status 'iniciado' (transições via vendas)
     * 
     * @param array $dados
     * @return Meta
     * @throws ValidationException
     */
    public function criar(array $dados): Meta
    {
        // Validação
        $this->validator->validate($dados);
        
        // Normaliza mês/ano
        $dados['mes_ano'] = MetaValidator::normalizeMesAno($dados['mes_ano']);
        
        // Verifica se já existe meta para este mês
        if ($this->metaRepository->existsMesAno($dados['mes_ano'])) {
            throw new ValidationException([
                'mes_ano' => 'Já existe uma meta definida para este mês'
            ]);
        }
        
        // Valores padrão
        $dados['horas_diarias_ideal'] = $dados['horas_diarias_ideal'] ?? 8;
        $dados['dias_trabalho_semana'] = $dados['dias_trabalho_semana'] ?? 5;
        $dados['valor_realizado'] = 0;
        $dados['porcentagem_atingida'] = 0;
        
        // Define status inicial inteligente
        $dados['status'] = $this->determinarStatusInicial($dados['mes_ano']);
        
        // Cria a meta
        $meta = $this->metaRepository->create($dados);
        
        // Recalcula com vendas existentes do mês (pode já ter vendas)
        $this->recalcularRealizado($meta->getId());
        
        // Recarrega para pegar valores atualizados
        $meta = $this->metaRepository->find($meta->getId());
        
        // ATUALIZADO: Após recalcular, determina status correto
        // considerando o threshold de 'superado'
        if ($meta && !$meta->isFinalizado() && !$meta->isSuperado()) {
            if ($meta->getValorRealizado() > 0 && $meta->isIniciado()) {
                // Tem vendas → pelo menos 'em_progresso'
                $this->metaRepository->atualizarStatus($meta->getId(), Meta::STATUS_EM_PROGRESSO);
                
                // Verifica se já cruza threshold de 'superado'
                if ($meta->qualificaParaSuperado()) {
                    $this->metaRepository->atualizarStatus($meta->getId(), Meta::STATUS_SUPERADO);
                }
            } elseif ($meta->getValorRealizado() > 0 && $meta->isEmProgresso()) {
                // Já em progresso, verifica superado
                if ($meta->qualificaParaSuperado()) {
                    $this->metaRepository->atualizarStatus($meta->getId(), Meta::STATUS_SUPERADO);
                }
            }
        }
        
        // Se é mês passado e recalculou, determina status final
        if ($meta && $meta->isMesPassado()) {
            $metaFinal = $this->metaRepository->find($meta->getId());
            if ($metaFinal && !$metaFinal->isFinalizado() && !$metaFinal->isSuperado()) {
                // Mês passado: finaliza ou marca como superado
                $statusFinal = $metaFinal->qualificaParaSuperado() 
                    ? Meta::STATUS_SUPERADO 
                    : Meta::STATUS_FINALIZADO;
                $this->metaRepository->atualizarStatus($metaFinal->getId(), $statusFinal);
            }
        }
        
        return $this->metaRepository->find($meta->getId());
    }
    
    /**
     * Atualiza meta existente
     * 
     * @param int $id
     * @param array $dados
     * @return Meta
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados): Meta
    {
        $meta = $this->metaRepository->findOrFail($id);
        
        // Validação
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Verifica duplicidade se mês/ano mudou
        if (!empty($dados['mes_ano'])) {
            $dados['mes_ano'] = MetaValidator::normalizeMesAno($dados['mes_ano']);
            
            if ($dados['mes_ano'] !== $meta->getMesAno()) {
                if ($this->metaRepository->existsMesAno($dados['mes_ano'])) {
                    throw new ValidationException([
                        'mes_ano' => 'Já existe uma meta definida para este mês'
                    ]);
                }
            }
        }
        
        // Atualiza
        $this->metaRepository->update($id, $dados);
        
        // Recalcula porcentagem se valor_meta mudou
        if (isset($dados['valor_meta'])) {
            $this->recalcularPorcentagem($id);
            
            // NOVO: Após recalcular porcentagem, verifica transição de status
            // (alterar valor_meta pode fazer cruzar ou descruzar o threshold de 120%)
            $this->verificarTransicaoSuperado($id);
        }
        
        return $this->metaRepository->find($id);
    }
    
    /**
     * Remove meta
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function remover(int $id): bool
    {
        $this->metaRepository->findOrFail($id);
        return $this->metaRepository->delete($id);
    }
    
    // ==========================================
    // STATUS
    // ==========================================
    
    /**
     * Determina o status inicial ao criar uma meta
     * 
     * Lógica:
     * - Mês passado → 'finalizado' (meta retroativa, pode virar 'superado' após recálculo)
     * - Mês atual ou futuro → 'iniciado'
     * 
     * Nota: O ajuste para 'em_progresso' ou 'superado' acontece depois,
     * quando recalcularRealizado() detecta vendas existentes.
     * 
     * @param string $mesAno Formato YYYY-MM-DD
     * @return string Status inicial
     */
    private function determinarStatusInicial(string $mesAno): string
    {
        $mesAtual = date('Y-m-01');
        
        // Meta de mês passado → inicia como finalizada
        // (será reavaliada para 'superado' se vendas >= 120%)
        if ($mesAno < $mesAtual) {
            return Meta::STATUS_FINALIZADO;
        }
        
        // Mês atual ou futuro → iniciado
        return Meta::STATUS_INICIADO;
    }
    
    /**
     * Finaliza automaticamente metas de meses passados
     * 
     * Chamado pelo controller ao carregar a listagem.
     * Garante consistência: todo mês passado deve estar 'finalizado' ou 'superado'.
     * 
     * A lógica de diferenciar 'finalizado' vs 'superado' está no Repository.
     * 
     * @return int Quantidade de metas finalizadas/superadas
     */
    public function finalizarMetasPassadas(): int
    {
        return $this->metaRepository->finalizarMetasPassadas();
    }
    
    /**
     * Retorna anos que possuem metas (para navegação por abas)
     * 
     * @return array Lista de anos (ex: [2026, 2025])
     */
    public function getAnosDisponiveis(): array
    {
        return $this->metaRepository->getAnosComMetas();
    }
    
    /**
     * NOVO: Verifica se uma meta deve transicionar para/de 'superado'
     * 
     * Chamado após operações que alteram a porcentagem (editar valor_meta, recalcular).
     * Não afeta metas finalizadas.
     * 
     * @param int $metaId
     * @return void
     */
    private function verificarTransicaoSuperado(int $metaId): void
    {
        $meta = $this->metaRepository->find($metaId);
        if (!$meta || $meta->isFinalizado()) {
            return;
        }
        
        if ($meta->qualificaParaSuperado() && !$meta->isSuperado()) {
            // Cruzou o threshold para cima → promove para 'superado'
            $this->metaRepository->atualizarStatus($metaId, Meta::STATUS_SUPERADO);
        } elseif (!$meta->qualificaParaSuperado() && $meta->isSuperado()) {
            // Caiu abaixo do threshold → reverte para 'em_progresso'
            // (ou 'iniciado' se sem vendas, mas isso é raro com porcentagem > 0)
            $novoStatus = $meta->getValorRealizado() > 0 
                ? Meta::STATUS_EM_PROGRESSO 
                : Meta::STATUS_INICIADO;
            $this->metaRepository->atualizarStatus($metaId, $novoStatus);
        }
    }
    
    // ==========================================
    // CÁLCULOS E RECÁLCULOS
    // ==========================================
    
    /**
     * Recalcula valor realizado baseado nas vendas do mês
     * 
     * ATUALIZADO (Melhoria 1 — Superado):
     * Agora verifica transição para 'superado' após recalcular.
     * 
     * @param int $metaId
     * @return Meta
     */
    public function recalcularRealizado(int $metaId): Meta
    {
        $meta = $this->metaRepository->findOrFail($metaId);
        
        // Busca total de vendas do mês
        $mesAno = substr($meta->getMesAno(), 0, 7); // YYYY-MM
        $totalVendas = $this->vendaRepository->getTotalVendasMes($mesAno);
        
        // Atualiza progresso
        $this->metaRepository->atualizarProgresso($metaId, $totalVendas);
        
        // Recarrega meta com valores atualizados
        $meta = $this->metaRepository->find($metaId);
        
        if ($meta && !$meta->isFinalizado()) {
            if ($totalVendas > 0 && $meta->isIniciado()) {
                // Primeira venda → 'em_progresso'
                $this->metaRepository->atualizarStatus($metaId, Meta::STATUS_EM_PROGRESSO);
                
                // Verifica se já qualifica para 'superado'
                if ($meta->qualificaParaSuperado()) {
                    $this->metaRepository->atualizarStatus($metaId, Meta::STATUS_SUPERADO);
                }
            } elseif ($totalVendas == 0 && ($meta->isEmProgresso() || $meta->isSuperado())) {
                // Sem vendas → volta para 'iniciado'
                $this->metaRepository->atualizarStatus($metaId, Meta::STATUS_INICIADO);
            } elseif ($totalVendas > 0) {
                // NOVO: Verifica transição superado ↔ em_progresso
                $this->verificarTransicaoSuperado($metaId);
            }
        }
        
        return $this->metaRepository->find($metaId);
    }
    
    /**
     * Recalcula apenas a porcentagem (quando valor_meta muda)
     * 
     * @param int $metaId
     */
    private function recalcularPorcentagem(int $metaId): void
    {
        $meta = $this->metaRepository->find($metaId);
        if (!$meta) return;
        
        $porcentagem = $meta->getValorMeta() > 0
            ? ($meta->getValorRealizado() / $meta->getValorMeta()) * 100
            : 0;
        
        $this->metaRepository->update($metaId, [
            'porcentagem_atingida' => round($porcentagem, 2)
        ]);
    }
    
    // ==========================================
    // ANÁLISES E PROJEÇÕES
    // ==========================================
    
    /**
     * Calcula projeção de fechamento do mês
     * Baseado no ritmo atual de vendas
     * 
     * @param Meta $meta
     * @return array
     */
    public function calcularProjecao(Meta $meta): array
    {
        $mesAno = $meta->getMesAno();
        $diaAtual = date('d');
        $diasNoMes = date('t', strtotime($mesAno));
        
        // Valor médio diário até agora
        $mediaDiaria = $diaAtual > 0 
            ? $meta->getValorRealizado() / $diaAtual 
            : 0;
        
        // Projeção para fim do mês
        $projecaoTotal = $mediaDiaria * $diasNoMes;
        
        // Quanto falta vender
        $faltaVender = max(0, $meta->getValorMeta() - $meta->getValorRealizado());
        
        // Dias restantes
        $diasRestantes = $diasNoMes - $diaAtual;
        
        // Média necessária por dia para bater meta
        $mediaNecessaria = $diasRestantes > 0 
            ? $faltaVender / $diasRestantes 
            : 0;
        
        return [
            'projecao_total' => round($projecaoTotal, 2),
            'falta_vender' => round($faltaVender, 2),
            'dias_restantes' => $diasRestantes,
            'media_diaria_atual' => round($mediaDiaria, 2),
            'media_diaria_necessaria' => round($mediaNecessaria, 2),
            'vai_bater_meta' => $projecaoTotal >= $meta->getValorMeta(),
            'porcentagem_projetada' => $meta->getValorMeta() > 0 
                ? round(($projecaoTotal / $meta->getValorMeta()) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * Calcula horas necessárias para bater meta
     */
    public function calcularHorasNecessarias(Meta $meta, float $valorHora = 50): array
    {
        $faltaVender = max(0, $meta->getValorMeta() - $meta->getValorRealizado());
        
        // Horas necessárias baseado no valor/hora
        $horasNecessarias = $valorHora > 0 
            ? $faltaVender / $valorHora : 0;
        
        // Dias restantes no mês
        $diasNoMes = date('t', strtotime($meta->getMesAno()));
        $diasRestantes = max(1, $diasNoMes - date('d'));
        
        // Horas por dia
        $horasPorDia = $horasNecessarias / $diasRestantes;
        
        return [
            'horas_totais_necessarias' => round($horasNecessarias, 1),
            'horas_por_dia' => round($horasPorDia, 1),
            'dias_restantes' => $diasRestantes,
            'viavel' => $horasPorDia <= ($meta->getHorasDiariasIdeal() ?? 8)
        ];
    }
    
    /**
     * Retorna resumo da meta atual para dashboard
     * 
     * @return array
     */
    public function getResumoDashboard(): array
    {
        $meta = $this->buscarMesAtual();
        
        if (!$meta) {
            return [
                'tem_meta' => false,
                'mensagem' => 'Nenhuma meta definida para este mês'
            ];
        }
        
        $projecao = $this->calcularProjecao($meta);
        
        return [
            'tem_meta' => true,
            'valor_meta' => $meta->getValorMeta(),
            'valor_realizado' => $meta->getValorRealizado(),
            'porcentagem' => $meta->getPorcentagemAtingida(),
            'status' => $meta->getStatus(),
            'falta_vender' => $projecao['falta_vender'],
            'vai_bater_meta' => $projecao['vai_bater_meta'],
            'projecao_total' => $projecao['projecao_total'],
            'media_diaria' => $projecao['media_diaria_atual'],
            'dias_restantes' => $projecao['dias_restantes']
        ];
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
    /**
     * Retorna estatísticas gerais de metas
     * 
     * @return array
     */
    public function getEstatisticas(): array
    {
        return $this->metaRepository->getEstatisticas();
    }
    
    /**
     * Retorna histórico de desempenho
     * 
     * @param int $meses
     * @return array
     */
    public function getHistoricoDesempenho(int $meses = 12): array
    {
        return $this->metaRepository->getDesempenhoMensal($meses);
    }
    
    /**
     * Cria meta para o mês atual se não existir
     * Útil para garantir que sempre exista uma meta
     * 
     * @param float $valorSugerido
     * @return Meta
     */
    public function criarOuObterMesAtual(float $valorSugerido = 5000): Meta
    {
        $mesAno = date('Y-m-01');
        
        return $this->metaRepository->findOrCreate($mesAno, [
            'valor_meta' => $valorSugerido
        ]);
    }
}