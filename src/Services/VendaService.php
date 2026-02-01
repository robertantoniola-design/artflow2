<?php

namespace App\Services;

use App\Models\Venda;
use App\Repositories\VendaRepository;
use App\Repositories\ArteRepository;
use App\Repositories\MetaRepository;
use App\Validators\VendaValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * VENDA SERVICE
 * ============================================
 * 
 * Camada de lógica de negócio para Vendas.
 * 
 * Responsabilidades:
 * - Validar dados de entrada
 * - Calcular lucro e rentabilidade
 * - Atualizar status da arte vendida
 * - Atualizar progresso da meta mensal
 * - Coordenar operações entre repositories
 */
class VendaService
{
    private VendaRepository $vendaRepository;
    private ArteRepository $arteRepository;
    private MetaRepository $metaRepository;
    private VendaValidator $validator;
    
    public function __construct(
        VendaRepository $vendaRepository,
        ArteRepository $arteRepository,
        MetaRepository $metaRepository,
        VendaValidator $validator
    ) {
        $this->vendaRepository = $vendaRepository;
        $this->arteRepository = $arteRepository;
        $this->metaRepository = $metaRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // OPERAÇÕES CRUD
    // ==========================================
    
    /**
     * Lista todas as vendas
     * 
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        // Filtro por período
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            return $this->vendaRepository->findByPeriodo(
                $filtros['data_inicio'],
                $filtros['data_fim']
            );
        }
        
        // Filtro por mês
        if (!empty($filtros['mes_ano'])) {
            return $this->vendaRepository->findByMes($filtros['mes_ano']);
        }
        
        // Filtro por cliente
        if (!empty($filtros['cliente_id'])) {
            return $this->vendaRepository->findByCliente((int) $filtros['cliente_id']);
        }
        
        // Lista todas ordenadas por data (mais recente primeiro)
        return $this->vendaRepository->getRecentes(100);
    }
    
    /**
     * Busca venda por ID
     * 
     * @param int $id
     * @return Venda
     * @throws NotFoundException
     */
    public function buscar(int $id): Venda
    {
        return $this->vendaRepository->findOrFail($id);
    }
    
    /**
     * Registra nova venda
     * 
     * Este é o método principal que:
     * 1. Valida os dados
     * 2. Verifica se arte está disponível
     * 3. Calcula lucro e rentabilidade
     * 4. Registra a venda
     * 5. Atualiza status da arte
     * 6. Atualiza meta do mês
     * 
     * @param array $dados
     * @return Venda
     * @throws ValidationException
     */
    public function registrar(array $dados): Venda
    {
        // 1. Validação básica
        $this->validator->validate($dados);
        
        // 2. Busca e valida a arte
        $arte = $this->arteRepository->findOrFail($dados['arte_id']);
        
        // Verifica se arte pode ser vendida
        if (!$this->validator->validateArteDisponivel($arte->getStatus())) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // 3. Calcula valores
        $lucro = $this->calcularLucro((float) $dados['valor'], $arte);
        $rentabilidadeHora = $this->calcularRentabilidadePorHora($lucro, $arte);
        
        // 4. Prepara dados completos
        $dadosVenda = [
            'arte_id' => $dados['arte_id'],
            'cliente_id' => $dados['cliente_id'] ?? null,
            'valor' => (float) $dados['valor'],
            'data_venda' => $dados['data_venda'],
            'lucro_calculado' => $lucro,
            'rentabilidade_hora' => $rentabilidadeHora
        ];
        
        // 5. Registra a venda
        $venda = $this->vendaRepository->create($dadosVenda);
        
        // 6. Atualiza status da arte para "vendida"
        $this->arteRepository->update($arte->getId(), ['status' => 'vendida']);
        
        // 7. Atualiza meta do mês
        $this->atualizarMeta($dados['data_venda'], (float) $dados['valor']);
        
        return $venda;
    }
    
    /**
     * Atualiza venda existente
     * 
     * @param int $id
     * @param array $dados
     * @return Venda
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados): Venda
    {
        // Busca venda atual
        $venda = $this->vendaRepository->findOrFail($id);
        
        // Validação
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Se mudou o valor, recalcula e atualiza meta
        $valorAntigo = $venda->getValor();
        $valorNovo = isset($dados['valor']) ? (float) $dados['valor'] : $valorAntigo;
        
        // Se arte mudou, recalcula tudo
        if (isset($dados['arte_id']) && $dados['arte_id'] != $venda->getArteId()) {
            $arte = $this->arteRepository->findOrFail($dados['arte_id']);
            $dados['lucro_calculado'] = $this->calcularLucro($valorNovo, $arte);
            $dados['rentabilidade_hora'] = $this->calcularRentabilidadePorHora($dados['lucro_calculado'], $arte);
        } elseif (isset($dados['valor'])) {
            // Recalcula com a arte atual
            $arte = $this->arteRepository->find($venda->getArteId());
            if ($arte) {
                $dados['lucro_calculado'] = $this->calcularLucro($valorNovo, $arte);
                $dados['rentabilidade_hora'] = $this->calcularRentabilidadePorHora($dados['lucro_calculado'], $arte);
            }
        }
        
        // Atualiza venda
        $this->vendaRepository->update($id, $dados);
        
        // Atualiza meta se valor mudou
        if ($valorNovo != $valorAntigo) {
            $diferenca = $valorNovo - $valorAntigo;
            $mesAno = isset($dados['data_venda']) ? $dados['data_venda'] : $venda->getDataVenda();
            $this->atualizarMeta($mesAno, $diferenca);
        }
        
        return $this->vendaRepository->find($id);
    }
    
    /**
     * Remove venda
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function remover(int $id): bool
    {
        $venda = $this->vendaRepository->findOrFail($id);
        
        // Decrementa da meta
        $this->metaRepository->decrementarRealizado(
            date('Y-m-01', strtotime($venda->getDataVenda())),
            $venda->getValor()
        );
        
        // Volta status da arte para disponível
        if ($venda->getArteId()) {
            $this->arteRepository->update($venda->getArteId(), ['status' => 'disponivel']);
        }
        
        return $this->vendaRepository->delete($id);
    }
    
    // ==========================================
    // CÁLCULOS
    // ==========================================
    
    /**
     * Calcula lucro da venda
     * Lucro = Valor da Venda - Custo da Arte
     * 
     * @param float $valorVenda
     * @param mixed $arte
     * @return float
     */
    public function calcularLucro(float $valorVenda, $arte): float
    {
        $custo = is_object($arte) ? $arte->getPrecoCusto() : 0;
        return round($valorVenda - $custo, 2);
    }
    
    /**
     * Calcula rentabilidade por hora
     * Rentabilidade = Lucro / Horas Trabalhadas
     * 
     * @param float $lucro
     * @param mixed $arte
     * @return float
     */
    public function calcularRentabilidadePorHora(float $lucro, $arte): float
    {
        $horas = is_object($arte) ? $arte->getHorasTrabalhadas() : 0;
        
        if ($horas <= 0) {
            return 0;
        }
        
        return round($lucro / $horas, 2);
    }
    
    // ==========================================
    // META MENSAL
    // ==========================================
    
    /**
     * Atualiza meta do mês com valor da venda
     * 
     * @param string $dataVenda
     * @param float $valor
     */
    private function atualizarMeta(string $dataVenda, float $valor): void
    {
        $mesAno = date('Y-m-01', strtotime($dataVenda));
        
        // Tenta incrementar. Se não existir meta, não faz nada.
        // (A meta precisa ser criada previamente pelo usuário)
        $this->metaRepository->incrementarRealizado($mesAno, $valor);
    }
    
    // ==========================================
    // ESTATÍSTICAS E RELATÓRIOS
    // ==========================================
    
    /**
     * Retorna estatísticas gerais de vendas
     * 
     * @return array
     */
    public function getEstatisticas(): array
    {
        return $this->vendaRepository->getEstatisticas();
    }
    
    /**
     * Retorna vendas do mês atual
     * 
     * @return array
     */
    public function getVendasMesAtual(): array
    {
        return $this->vendaRepository->findByMes(date('Y-m'));
    }
    
    /**
     * Retorna total vendido no mês
     * 
     * @param string|null $mesAno Formato: YYYY-MM (default: mês atual)
     * @return float
     */
    public function getTotalMes(?string $mesAno = null): float
    {
        $mesAno = $mesAno ?? date('Y-m');
        return $this->vendaRepository->getTotalVendasMes($mesAno);
    }
    
    /**
     * Retorna faturamento por período
     * 
     * @param string $dataInicio
     * @param string $dataFim
     * @return array
     */
    public function getFaturamentoPeriodo(string $dataInicio, string $dataFim): array
    {
        $vendas = $this->vendaRepository->findByPeriodo($dataInicio, $dataFim);
        
        $total = 0;
        $lucroTotal = 0;
        
        foreach ($vendas as $venda) {
            $total += $venda->getValor();
            $lucroTotal += $venda->getLucroCalculado() ?? 0;
        }
        
        return [
            'quantidade' => count($vendas),
            'total' => $total,
            'lucro_total' => $lucroTotal,
            'ticket_medio' => count($vendas) > 0 ? $total / count($vendas) : 0
        ];
    }
    
    /**
     * Retorna dados para gráfico de vendas mensais
     * 
     * @param int $meses
     * @return array
     */
    public function getVendasMensais(int $meses = 12): array
    {
        return $this->vendaRepository->getVendasPorMes($meses);
    }
    
    /**
     * Retorna ranking de artes mais rentáveis
     * 
     * @param int $limit
     * @return array
     */
    public function getRankingRentabilidade(int $limit = 10): array
    {
        return $this->vendaRepository->getMaisRentaveis($limit);
    }
}
