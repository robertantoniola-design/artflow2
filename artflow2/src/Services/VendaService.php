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
 * 
 * CORREÇÕES (01/02/2026):
 * - registrar(): Agora inclui forma_pagamento e observacoes nos dados da venda
 * - registrar(): Converte strings vazias para null em campos FK (evita erro MySQL)
 * - Adicionado helper sanitizarDados() para limpar campos de entrada
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
     * 1. Sanitiza os dados de entrada
     * 2. Valida os dados
     * 3. Verifica se arte está disponível
     * 4. Calcula lucro e rentabilidade
     * 5. Registra a venda (com TODOS os campos)
     * 6. Atualiza status da arte
     * 7. Atualiza meta do mês
     * 
     * CORREÇÃO (01/02/2026):
     * - Agora inclui forma_pagamento e observacoes nos $dadosVenda
     * - Sanitiza cliente_id: string vazia → null (evita FK violation no MySQL)
     * - Antes, $dadosVenda só tinha 6 campos, agora tem 8
     * 
     * @param array $dados
     * @return Venda
     * @throws ValidationException
     */
    public function registrar(array $dados): Venda
    {
        // 1. CORREÇÃO: Sanitiza dados ANTES da validação
        // Converte strings vazias para null em campos que aceitam null
        // Sem isso, cliente_id="" causa PDOException: "Incorrect integer value"
        $dados = $this->sanitizarDados($dados);
        
        // 2. Validação básica
        $this->validator->validate($dados);
        
        // 3. Busca e valida a arte
        $arte = $this->arteRepository->findOrFail($dados['arte_id']);
        
        // Verifica se arte pode ser vendida (status = 'disponivel')
        if (!$this->validator->validateArteDisponivel($arte->getStatus())) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // 4. Calcula valores financeiros
        $lucro = $this->calcularLucro((float) $dados['valor'], $arte);
        $rentabilidadeHora = $this->calcularRentabilidadePorHora($lucro, $arte);
        
        // 5. CORREÇÃO: Prepara dados COMPLETOS para INSERT
        // Antes faltavam: forma_pagamento, observacoes
        // Isso causava INSERT sem forma_pagamento → MySQL rejeitava se coluna 
        // não tivesse DEFAULT (ou se DB estava em strict mode sem DEFAULT)
        $dadosVenda = [
            'arte_id'            => (int) $dados['arte_id'],
            'cliente_id'         => $dados['cliente_id'],  // já sanitizado: null ou int
            'valor'              => (float) $dados['valor'],
            'data_venda'         => $dados['data_venda'],
            'lucro_calculado'    => $lucro,
            'rentabilidade_hora' => $rentabilidadeHora,
            'forma_pagamento'    => $dados['forma_pagamento'] ?? 'pix',       // ADICIONADO
            'observacoes'        => $dados['observacoes'] ?? null,            // ADICIONADO
        ];
        
        // 6. Registra a venda no banco
        $venda = $this->vendaRepository->create($dadosVenda);
        
        // 7. Atualiza status da arte para "vendida"
        $this->arteRepository->update($arte->getId(), ['status' => 'vendida']);
        
        // 8. Atualiza meta do mês (soma valor realizado)
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
        
        // CORREÇÃO: Sanitiza dados antes de validar
        $dados = $this->sanitizarDados($dados);
        
        // Validação
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Se mudou o valor, recalcula e atualiza meta
        $valorAntigo = $venda->getValor();
        $valorNovo = isset($dados['valor']) ? (float) $dados['valor'] : $valorAntigo;
        
        // Se valor mudou, recalcula lucro
        if (abs($valorNovo - $valorAntigo) > 0.01) {
            $arte = $this->arteRepository->find($venda->getArteId());
            if ($arte) {
                $dados['lucro_calculado'] = $this->calcularLucro($valorNovo, $arte);
                $dados['rentabilidade_hora'] = $this->calcularRentabilidadePorHora(
                    $dados['lucro_calculado'], $arte
                );
            }
        }
        
        // Atualiza venda
        $vendaAtualizada = $this->vendaRepository->update($id, $dados);
        
        // Recalcula meta se valor mudou
        if (abs($valorNovo - $valorAntigo) > 0.01) {
            $dataVenda = $dados['data_venda'] ?? $venda->getDataVenda();
            $this->recalcularMetaMes($dataVenda);
        }
        
        return $vendaAtualizada;
    }
    
    /**
     * Exclui venda e recalcula meta do mês
     * 
     * @param int $id
     * @throws NotFoundException
     */
    public function excluir(int $id): void
    {
        $venda = $this->vendaRepository->findOrFail($id);
        
        // Recupera arte para restaurar status
        $arteId = $venda->getArteId();
        
        // Exclui a venda
        $this->vendaRepository->delete($id);
        
        // Restaura status da arte para 'disponivel' se existir
        if ($arteId) {
            try {
                $this->arteRepository->update($arteId, ['status' => 'disponivel']);
            } catch (\Exception $e) {
                // Arte pode ter sido deletada, ignora
            }
        }
        
        // Recalcula meta do mês da venda
        $this->recalcularMetaMes($venda->getDataVenda());
    }
    
    // ==========================================
    // SANITIZAÇÃO DE DADOS
    // ==========================================
    
    /**
     * Sanitiza dados de entrada
     * 
     * NOVO (01/02/2026):
     * Converte strings vazias para null em campos que aceitam NULL no banco.
     * Isso é necessário porque forms HTML enviam "" para selects não selecionados,
     * e MySQL strict mode rejeita "" em colunas INT (FK violation).
     * 
     * Exemplo do problema:
     *   Form: <option value="">Selecione...</option>  → envia cliente_id=""
     *   PHP:  $dados['cliente_id'] ?? null             → NÃO converte "" para null (?? só atua em null)
     *   MySQL: INSERT ... cliente_id = ''              → ERRO: Incorrect integer value
     * 
     * @param array $dados
     * @return array Dados sanitizados
     */
    private function sanitizarDados(array $dados): array
    {
        // Campos FK que aceitam NULL: strings vazias → null
        $camposFkNullable = ['cliente_id', 'arte_id'];
        
        foreach ($camposFkNullable as $campo) {
            if (isset($dados[$campo]) && $dados[$campo] === '') {
                $dados[$campo] = null;
            }
        }
        
        // Campos de texto opcionais: strings vazias → null
        $camposTextoNullable = ['observacoes'];
        
        foreach ($camposTextoNullable as $campo) {
            if (isset($dados[$campo]) && trim($dados[$campo]) === '') {
                $dados[$campo] = null;
            }
        }
        
        // Garante forma_pagamento com valor padrão
        if (empty($dados['forma_pagamento'])) {
            $dados['forma_pagamento'] = 'pix';
        }
        
        return $dados;
    }
    
    // ==========================================
    // CÁLCULOS FINANCEIROS
    // ==========================================
    
    /**
     * Calcula lucro da venda
     * Lucro = Valor de Venda - Preço de Custo da Arte
     * 
     * @param float $valorVenda
     * @param mixed $arte Objeto Arte ou array
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
    
    /**
     * Recalcula meta de um mês (re-soma todas as vendas)
     * 
     * @param string $data Data de referência (qualquer data do mês)
     */
    private function recalcularMetaMes(string $data): void
    {
        $mesAno = date('Y-m-01', strtotime($data));
        $meta = $this->metaRepository->findByMesAno($mesAno);
        
        if (!$meta) return;
        
        // Re-soma todas as vendas do mês
        $totalMes = $this->vendaRepository->getTotalVendasMes(date('Y-m', strtotime($data)));
        $porcentagem = $meta->getValorMeta() > 0 
            ? round(($totalMes / $meta->getValorMeta()) * 100, 2) 
            : 0;
        
        $this->metaRepository->atualizarProgresso(
            $meta->getId(),
            $totalMes,
            $porcentagem
        );
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
            'ticket_medio' => count($vendas) > 0 ? round($total / count($vendas), 2) : 0,
            'vendas' => $vendas
        ];
    }
    
    /**
     * Retorna dados de vendas mensais para gráficos
     * 
     * @param int $meses Quantidade de meses para retornar
     * @return array
     */
    public function getVendasMensais(int $meses = 6): array
    {
        return $this->vendaRepository->getVendasMensais($meses);
    }
    
    /**
     * Retorna ranking de rentabilidade
     * 
     * @param int $limite
     * @return array
     */
    public function getRankingRentabilidade(int $limite = 10): array
    {
        return $this->vendaRepository->getRankingRentabilidade($limite);
    }
    
    /**
     * Retorna artes disponíveis para venda
     * 
     * @return array
     */
    public function getArtesDisponiveis(): array
    {
        return $this->arteRepository->findDisponiveis();
    }
}