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
 * MELHORIA 2 + 3 (05/02/2026): getEstatisticasAno(), getDesempenhoAnual()
 * MELHORIA 4 (06/02/2026): getMetasEmRisco()
 * MELHORIA 5 (06/02/2026): criarRecorrente()
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
    
    public function listar(array $filtros = []): array
    {
        if (!empty($filtros['ano'])) {
            return $this->metaRepository->findByAno((int) $filtros['ano']);
        }
        return $this->metaRepository->getRecentes($filtros['limite'] ?? 12);
    }
    
    public function buscar(int $id): Meta
    {
        return $this->metaRepository->findOrFail($id);
    }
    
    public function buscarMesAtual(): ?Meta
    {
        return $this->metaRepository->findMesAtual();
    }
    
    /**
     * Cria nova meta (criação simples, 1 mês)
     */
    public function criar(array $dados): Meta
    {
        $this->validator->validate($dados);
        
        $dados['mes_ano'] = MetaValidator::normalizeMesAno($dados['mes_ano']);
        
        if ($this->metaRepository->existsMesAno($dados['mes_ano'])) {
            throw new ValidationException([
                'mes_ano' => 'Já existe uma meta definida para este mês'
            ]);
        }
        
        $dados['horas_diarias_ideal'] = $dados['horas_diarias_ideal'] ?? 8;
        $dados['dias_trabalho_semana'] = $dados['dias_trabalho_semana'] ?? 5;
        $dados['valor_realizado'] = 0;
        $dados['porcentagem_atingida'] = 0;
        
        $meta = $this->metaRepository->create($dados);
        $this->recalcularRealizado($meta->getId());
        
        return $this->metaRepository->find($meta->getId());
    }
    
    /**
     * =====================================================
     * MELHORIA 5: Cria metas recorrentes para múltiplos meses
     * =====================================================
     * 
     * Cria a mesma meta (mesmo valor, horas, dias) para N meses
     * consecutivos a partir do mês inicial selecionado.
     * 
     * Meses que já possuem meta são ignorados (sem erro).
     * Usa o método criar() internamente, que já faz validação
     * e recalcula vendas existentes do mês.
     * 
     * @param array $dados  Dados base da meta (mes_ano, valor_meta, etc.)
     * @param int $quantidadeMeses Quantidade de meses (1-12)
     * @return array ['criadas' => Meta[], 'ignoradas' => [], 'erros' => []]
     */
    public function criarRecorrente(array $dados, int $quantidadeMeses): array
    {
        $resultado = [
            'criadas' => [],
            'ignoradas' => [],
            'erros' => []
        ];
        
        // Limita entre 1 e 12 meses (segurança)
        $quantidadeMeses = max(1, min(12, $quantidadeMeses));
        
        // Normaliza data inicial para DateTime
        // O input type="month" envia "YYYY-MM", precisamos "YYYY-MM-01"
        $mesAnoInput = $dados['mes_ano'];
        if (strlen($mesAnoInput) === 7) {
            $mesAnoInput .= '-01';
        }
        $mesInicial = new \DateTime($mesAnoInput);
        
        for ($i = 0; $i < $quantidadeMeses; $i++) {
            $mesAno = $mesInicial->format('Y-m-01');
            
            // Verifica se já existe meta para este mês
            // Usa existsMesAno() que aceita tanto 'Y-m-01' quanto 'Y-m'
            if ($this->metaRepository->existsMesAno($mesAno)) {
                // Mês já tem meta → ignora sem erro
                $resultado['ignoradas'][] = [
                    'mes_ano' => $mesAno,
                    'motivo' => 'Já existe meta para este mês'
                ];
            } else {
                try {
                    // Monta dados para este mês específico
                    // Usa mes_ano no formato 'Y-m' para compatibilidade com criar()
                    $dadosMeta = array_merge($dados, [
                        'mes_ano' => $mesInicial->format('Y-m')
                    ]);
                    
                    // Chama criar() que faz validação, normalização e recálculo
                    $meta = $this->criar($dadosMeta);
                    $resultado['criadas'][] = $meta;
                    
                } catch (\Exception $e) {
                    // Captura qualquer erro (validação, DB, etc.)
                    $resultado['erros'][] = [
                        'mes_ano' => $mesAno,
                        'erro' => $e->getMessage()
                    ];
                }
            }
            
            // Avança 1 mês
            $mesInicial->modify('+1 month');
        }
        
        return $resultado;
    }
    
    public function atualizar(int $id, array $dados): Meta
    {
        $meta = $this->metaRepository->findOrFail($id);
        
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
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
        
        $this->metaRepository->update($id, $dados);
        
        if (isset($dados['valor_meta'])) {
            $this->recalcularPorcentagem($id);
        }
        
        return $this->metaRepository->find($id);
    }
    
    public function remover(int $id): bool
    {
        $this->metaRepository->findOrFail($id);
        return $this->metaRepository->delete($id);
    }
    
    // ==========================================
    // RECÁLCULOS
    // ==========================================
    
    public function recalcularRealizado(int $metaId): Meta
    {
        $meta = $this->metaRepository->findOrFail($metaId);
        
        $mesAno = substr($meta->getMesAno(), 0, 7);
        $totalVendas = $this->vendaRepository->getTotalVendasMes($mesAno);
        
        $this->metaRepository->atualizarProgresso($metaId, $totalVendas);
        
        return $this->metaRepository->find($metaId);
    }
    
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
    
    public function calcularProjecao(Meta $meta): array
    {
        $mesAno = $meta->getMesAno();
        $diaAtual = date('d');
        $diasNoMes = date('t', strtotime($mesAno));
        
        $mediaDiaria = $diaAtual > 0 
            ? $meta->getValorRealizado() / $diaAtual 
            : 0;
        
        $projecaoTotal = $mediaDiaria * $diasNoMes;
        $faltaVender = max(0, $meta->getValorMeta() - $meta->getValorRealizado());
        $diasRestantes = $diasNoMes - $diaAtual;
        
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
    
    public function calcularHorasNecessarias(Meta $meta, float $valorHora = 50): array
    {
        $faltaVender = max(0, $meta->getValorMeta() - $meta->getValorRealizado());
        $horasNecessarias = $valorHora > 0 ? $faltaVender / $valorHora : 0;
        
        $diasNoMes = date('t', strtotime($meta->getMesAno()));
        $diasRestantes = max(1, $diasNoMes - date('d'));
        $horasPorDia = $horasNecessarias / $diasRestantes;
        
        return [
            'horas_totais_necessarias' => round($horasNecessarias, 1),
            'horas_por_dia' => round($horasPorDia, 1),
            'dias_restantes' => $diasRestantes,
            'viavel' => $horasPorDia <= ($meta->getHorasDiariasIdeal() ?? 8)
        ];
    }
    
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
    
    public function getEstatisticas(): array
    {
        return $this->metaRepository->getEstatisticas();
    }
    
    /** MELHORIA 2 */
    public function getEstatisticasAno(int $ano): array
    {
        return $this->metaRepository->getEstatisticasAno($ano);
    }
    
    /** MELHORIA 3 */
    public function getDesempenhoAnual(int $ano): array
    {
        return $this->metaRepository->getDesempenhoAnual($ano);
    }
    
    /**
     * MELHORIA 4: Verifica se meta atual está em risco
     */
    public function getMetasEmRisco(): array
    {
        $metaAtual = $this->buscarMesAtual();
        
        if (!$metaAtual) {
            return ['alerta' => false, 'motivo' => 'sem_meta'];
        }
        
        if ($metaAtual->getPorcentagemAtingida() >= 100) {
            return ['alerta' => false, 'motivo' => 'meta_batida'];
        }
        
        $projecao = $this->calcularProjecao($metaAtual);
        
        if (!$projecao['vai_bater_meta']) {
            return [
                'alerta' => true,
                'meta' => [
                    'id'                   => $metaAtual->getId(),
                    'mes_ano'              => $metaAtual->getMesAno(),
                    'valor_meta'           => $metaAtual->getValorMeta(),
                    'valor_realizado'      => $metaAtual->getValorRealizado(),
                    'porcentagem_atingida' => $metaAtual->getPorcentagemAtingida()
                ],
                'projecao' => $projecao,
                'mensagem' => sprintf(
                    'Projeção: R$ %s (%.1f%%). Faltam R$ %s em %d dias (R$ %s/dia necessário).',
                    number_format($projecao['projecao_total'], 2, ',', '.'),
                    $projecao['porcentagem_projetada'],
                    number_format($projecao['falta_vender'], 2, ',', '.'),
                    $projecao['dias_restantes'],
                    number_format($projecao['media_diaria_necessaria'], 2, ',', '.')
                )
            ];
        }
        
        return ['alerta' => false, 'motivo' => 'projecao_ok'];
    }
    
    public function getHistoricoDesempenho(int $meses = 12): array
    {
        return $this->metaRepository->getDesempenhoMensal($meses);
    }
    
    public function criarOuObterMesAtual(float $valorSugerido = 5000): Meta
    {
        $mesAno = date('Y-m-01');
        
        return $this->metaRepository->findOrCreate($mesAno, [
            'valor_meta' => $valorSugerido
        ]);
    }
}