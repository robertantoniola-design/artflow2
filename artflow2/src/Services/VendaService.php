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
 * VENDA SERVICE — FASE 1 ESTABILIZAÇÃO
 * ============================================
 * 
 * Camada de lógica de negócio para Vendas.
 * Orquestra 3 Repositories: Venda + Arte + Meta.
 * 
 * CORREÇÕES ANTERIORES (05/02/2026):
 * - getVendasMensais(): Usa vendaRepository->getVendasPorMes()
 * - getRankingRentabilidade(): Usa vendaRepository->getMaisRentaveis()
 * - Sanitização de dados + forma_pagamento/observacoes no INSERT
 * 
 * FASE 1 — CORREÇÕES (22/02/2026):
 * - V7: excluir() agora REVERTE status da arte para 'disponivel'
 * - V9: Novo método buscarComRelacionamentos() usando findWithRelations()
 * - V6: atualizar() agora recalcula meta quando o valor muda
 * - Melhoria: Logs detalhados para diagnóstico de bugs cross-module
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
     * Lista todas as vendas com filtros
     * 
     * NOTA SOBRE FILTROS (Bug V4 — documentado, correção na Melhoria 3):
     * Os filtros atualmente são mutuamente exclusivos (if/elseif).
     * Isso significa que período, mês e cliente NÃO combinam.
     * Será corrigido na Melhoria 3 com WHERE dinâmico + AND.
     * 
     * @param array $filtros ['data_inicio','data_fim','mes_ano','cliente_id']
     * @return array de Venda|array (tipo misto — ver nota abaixo)
     */
    public function listar(array $filtros = []): array
    {
        // Filtro por período (data_inicio + data_fim)
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            return $this->vendaRepository->findByPeriodo(
                $filtros['data_inicio'],
                $filtros['data_fim']
            );
        }
        
        // Filtro por mês (formato YYYY-MM)
        if (!empty($filtros['mes_ano'])) {
            return $this->vendaRepository->findByMes($filtros['mes_ano']);
        }
        
        // Filtro por cliente
        if (!empty($filtros['cliente_id'])) {
            return $this->vendaRepository->findByCliente((int) $filtros['cliente_id']);
        }
        
        // Sem filtro: lista todas com relacionamentos (mais recentes primeiro)
        // NOTA: getRecentes() retorna ARRAYS brutos, não objetos Venda.
        // findByPeriodo/findByCliente retornam objetos hydrated.
        // O Controller lida com ambos os tipos no cálculo do resumo.
        return $this->vendaRepository->getRecentes(100);
    }
    
    /**
     * Busca venda por ID (SEM relacionamentos)
     * 
     * Retorna apenas os dados da tabela vendas.
     * Use buscarComRelacionamentos() quando precisar de Arte/Cliente.
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
     * CORREÇÃO V9: Busca venda COM relacionamentos (Arte + Cliente hydrated)
     * 
     * Usa findWithRelations() que faz JOIN com artes e clientes,
     * populando $venda->getArte() e $venda->getCliente() com objetos completos.
     * 
     * Essencial para show.php e edit.php que precisam exibir:
     * - Nome da arte, custo, horas trabalhadas, complexidade
     * - Nome do cliente, email, etc.
     * 
     * @param int $id
     * @return Venda (com Arte e Cliente hydrated)
     * @throws NotFoundException
     */
    public function buscarComRelacionamentos(int $id): Venda
    {
        // findWithRelations() faz JOIN artes + clientes e hydrata os objetos
        $venda = $this->vendaRepository->findWithRelations($id);
        
        if (!$venda) {
            throw new NotFoundException("Venda #{$id} não encontrada");
        }
        
        return $venda;
    }
    
    /**
     * Registra nova venda — FLUXO PRINCIPAL (8 passos)
     * 
     * Este método orquestra operações em 3 tabelas:
     * 1. Sanitiza dados de entrada
     * 2. Valida campos obrigatórios
     * 3. Busca e valida a arte (deve estar disponível)
     * 4. Calcula lucro e rentabilidade
     * 5. Insere registro na tabela vendas
     * 6. Atualiza status da arte → 'vendida'
     * 7. Atualiza meta do mês (incrementa valor_realizado)
     * 
     * @param array $dados Dados do formulário
     * @return Venda
     * @throws ValidationException|NotFoundException
     */
    public function registrar(array $dados): Venda
    {
        // 1. Sanitiza dados ANTES da validação
        $dados = $this->sanitizarDados($dados);
        
        // 2. Validação básica (campos obrigatórios + tipos)
        $this->validator->validate($dados);
        
        // 3. Busca a arte e verifica disponibilidade
        $arte = $this->arteRepository->findOrFail($dados['arte_id']);
        
        // Verifica se arte pode ser vendida (status != 'vendida')
        if (!$this->validator->validateArteDisponivel($arte->getStatus())) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // 4. Calcula valores financeiros
        $lucro = $this->calcularLucro((float) $dados['valor'], $arte);
        $rentabilidadeHora = $this->calcularRentabilidadePorHora($lucro, $arte);
        
        // 5. Prepara dados COMPLETOS para INSERT
        $dadosVenda = [
            'arte_id'            => (int) $dados['arte_id'],
            'cliente_id'         => $dados['cliente_id'],  // já sanitizado: null ou int
            'valor'              => (float) $dados['valor'],
            'data_venda'         => $dados['data_venda'],
            'lucro_calculado'    => $lucro,
            'rentabilidade_hora' => $rentabilidadeHora,
            'forma_pagamento'    => $dados['forma_pagamento'] ?? 'pix',
            'observacoes'        => $dados['observacoes'] ?? null,
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
     * CORREÇÃO V6: Agora recalcula meta quando o valor muda.
     * 
     * NOTA: arte_id NUNCA é alterado na edição.
     * A arte permanece com status 'vendida'.
     * 
     * @param int $id
     * @param array $dados Campos editáveis
     * @return Venda
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados): Venda
    {
        // Busca venda atual para comparar valores
        $venda = $this->vendaRepository->findOrFail($id);
        
        // Sanitiza dados antes de validar
        $dados = $this->sanitizarDados($dados);
        
        // Validação para update (menos restritiva: arte_id não é obrigatório)
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Se mudou o valor, recalcula lucro e rentabilidade
        $valorAntigo = $venda->getValor();
        $valorNovo = isset($dados['valor']) ? (float) $dados['valor'] : $valorAntigo;
        
        if (abs($valorNovo - $valorAntigo) > 0.01) {
            // Busca a arte para recalcular valores financeiros
            $arte = $this->arteRepository->find($venda->getArteId());
            if ($arte) {
                $dados['lucro_calculado'] = $this->calcularLucro($valorNovo, $arte);
                $dados['rentabilidade_hora'] = $this->calcularRentabilidadePorHora(
                    $dados['lucro_calculado'], $arte
                );
            }
        }
        
        // Atualiza venda no banco
        $vendaAtualizada = $this->vendaRepository->update($id, $dados);
        
        // CORREÇÃO V6: Recalcula meta se valor mudou
        // Usa a data_venda (nova ou antiga) para encontrar a meta correta
        if (abs($valorNovo - $valorAntigo) > 0.01) {
            $dataVenda = $dados['data_venda'] ?? $venda->getDataVenda();
            
            // Extrai mês/ano da data da venda (formato YYYY-MM)
            if ($dataVenda instanceof \DateTime) {
                $mesAno = $dataVenda->format('Y-m');
            } else {
                $mesAno = substr($dataVenda, 0, 7); // "2026-02-15" → "2026-02"
            }
            
            $this->recalcularMetaMes($mesAno);
        }
        
        return $vendaAtualizada;
    }
    
    /**
     * Exclui venda e desfaz os efeitos colaterais
     * 
     * CORREÇÃO V7: Agora REVERTE o status da arte para 'disponivel'.
     * Antes, a arte permanecia com status 'vendida' mesmo após excluir a venda.
     * 
     * Efeitos da exclusão:
     * 1. Busca a venda (com arte_id) antes de excluir
     * 2. Exclui o registro da tabela vendas
     * 3. Reverte status da arte → 'disponivel' (SE a arte ainda existe)
     * 4. Recalcula a meta do mês (subtrai valor da venda)
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function excluir(int $id): bool
    {
        // 1. Busca venda ANTES de excluir (precisamos do arte_id e data_venda)
        $venda = $this->vendaRepository->findOrFail($id);
        
        // Guarda dados necessários antes da exclusão
        $arteId = $venda->getArteId();
        $dataVenda = $venda->getDataVenda();
        
        // 2. Exclui a venda do banco
        $resultado = $this->vendaRepository->delete($id);
        
        // 3. CORREÇÃO V7: Reverte status da arte para 'disponivel'
        // Só reverte se a arte_id não é null (a arte pode ter sido excluída via SET NULL)
        if ($arteId) {
            try {
                $arte = $this->arteRepository->find($arteId);
                if ($arte && $arte->getStatus() === 'vendida') {
                    $this->arteRepository->update($arteId, ['status' => 'disponivel']);
                    error_log("[VendaService::excluir] Arte #{$arteId} revertida para 'disponivel'");
                }
            } catch (\Exception $e) {
                // Se a arte não existe mais (SET NULL), apenas loga
                error_log("[VendaService::excluir] Arte #{$arteId} não encontrada: " . $e->getMessage());
            }
        }
        
        // 4. Recalcula a meta do mês da venda excluída
        if ($dataVenda) {
            if ($dataVenda instanceof \DateTime) {
                $mesAno = $dataVenda->format('Y-m');
            } else {
                $mesAno = substr($dataVenda, 0, 7);
            }
            $this->recalcularMetaMes($mesAno);
        }
        
        return $resultado;
    }
    
    // ==========================================
    // CÁLCULOS FINANCEIROS
    // ==========================================
    
    /**
     * Calcula lucro: valor da venda - custo da arte
     * 
     * @param float $valorVenda
     * @param mixed $arte Objeto Arte (com getters)
     * @return float Pode ser negativo (prejuízo)
     */
    private function calcularLucro(float $valorVenda, $arte): float
    {
        $precoCusto = method_exists($arte, 'getPrecoCusto') 
            ? (float) $arte->getPrecoCusto() 
            : (float) ($arte->preco_custo ?? 0);
            
        return round($valorVenda - $precoCusto, 2);
    }
    
    /**
     * Calcula rentabilidade por hora: lucro / horas trabalhadas
     * 
     * @param float $lucro
     * @param mixed $arte
     * @return float 0 se não houver horas registradas
     */
    private function calcularRentabilidadePorHora(float $lucro, $arte): float
    {
        $horasTrabalhadas = method_exists($arte, 'getHorasTrabalhadas')
            ? (float) $arte->getHorasTrabalhadas()
            : (float) ($arte->horas_trabalhadas ?? 0);
            
        if ($horasTrabalhadas <= 0) {
            return 0;
        }
        
        return round($lucro / $horasTrabalhadas, 2);
    }
    
    // ==========================================
    // SANITIZAÇÃO
    // ==========================================
    
    /**
     * Sanitiza dados de entrada
     * Converte strings vazias para null em campos opcionais
     * 
     * @param array $dados
     * @return array
     */
    private function sanitizarDados(array $dados): array
    {
        // cliente_id vazio → null (campo opcional, FK nullable)
        if (isset($dados['cliente_id']) && $dados['cliente_id'] === '') {
            $dados['cliente_id'] = null;
        }
        
        // cliente_id numérico → int
        if (!empty($dados['cliente_id'])) {
            $dados['cliente_id'] = (int) $dados['cliente_id'];
        }
        
        // observacoes vazia → null
        if (isset($dados['observacoes']) && trim($dados['observacoes']) === '') {
            $dados['observacoes'] = null;
        }
        
        return $dados;
    }
    
    // ==========================================
    // METAS — Integração cross-module
    // ==========================================
    
    /**
     * Atualiza meta do mês ao registrar venda
     * 
     * Busca a meta do mês correspondente à data_venda e
     * atualiza o valor_realizado usando atualizarProgresso().
     * 
     * Se não existir meta para o mês, apenas loga (sem erro).
     * 
     * @param string $dataVenda Data no formato Y-m-d
     * @param float $valor Valor a ser somado (não usado diretamente — recalcula total)
     */
    private function atualizarMeta(string $dataVenda, float $valor): void
    {
        try {
            $mesAno = substr($dataVenda, 0, 7); // "2026-02-15" → "2026-02"
            $this->recalcularMetaMes($mesAno);
        } catch (\Exception $e) {
            // Falha na meta NÃO deve impedir a venda
            error_log("[VendaService::atualizarMeta] Erro: " . $e->getMessage());
        }
    }
    
    /**
     * Recalcula a meta de um mês específico
     * 
     * Re-soma TODAS as vendas do mês para obter o valor correto.
     * Isso é mais seguro que incrementar/decrementar, pois evita
     * inconsistências por operações parciais.
     * 
     * @param string $mesAno Formato "YYYY-MM"
     */
    public function recalcularMetaMes(string $mesAno): void
    {
        try {
            // Busca total real de vendas do mês
            $totalVendas = $this->vendaRepository->getTotalVendasMes($mesAno);
            
            // Busca a meta do mês (formato pode ser "YYYY-MM" ou "YYYY-MM-01")
            // O MetaRepository aceita ambos os formatos
            $mesAnoCompleto = strlen($mesAno) === 7 ? $mesAno . '-01' : $mesAno;
            
            // Tenta encontrar a meta e atualizar
            // O atualizarProgresso() do MetaRepository recalcula porcentagem e faz transição de status
            // findByMesAno() aceita "YYYY-MM" ou "YYYY-MM-01" (normaliza internamente)
            $meta = $this->metaRepository->findByMesAno($mesAnoCompleto);
            
            if ($meta) {
                $this->metaRepository->atualizarProgresso($meta->getId(), $totalVendas);
                error_log("[VendaService::recalcularMetaMes] Meta {$mesAno}: R$ {$totalVendas}");
            }
        } catch (\Exception $e) {
            // Falha na meta NÃO deve impedir a operação principal
            error_log("[VendaService::recalcularMetaMes] Erro: " . $e->getMessage());
        }
    }
    
    // ==========================================
    // CONSULTAS (usados por Controller e Dashboard)
    // ==========================================
    
    /**
     * Retorna estatísticas gerais de vendas
     * Delega ao Repository
     * 
     * @return array ['total_vendas', 'valor_total', 'lucro_total', ...]
     */
    public function getEstatisticas(): array
    {
        try {
            return $this->vendaRepository->getEstatisticas();
        } catch (\Exception $e) {
            error_log("[VendaService::getEstatisticas] Erro: " . $e->getMessage());
            return [
                'total_vendas' => 0,
                'valor_total' => 0,
                'lucro_total' => 0,
                'ticket_medio' => 0
            ];
        }
    }
    
    /**
     * Vendas do mês atual
     * Chamado pelo DashboardController
     * 
     * @return array
     */
    public function getVendasMesAtual(): array
    {
        $mesAno = date('Y-m');
        return $this->vendaRepository->findByMes($mesAno);
    }
    
    /**
     * Total de vendas do mês (SUM valor)
     * Chamado pelo DashboardController
     * 
     * @param string|null $mesAno Formato "YYYY-MM" (null = mês atual)
     * @return float
     */
    public function getTotalMes(?string $mesAno = null): float
    {
        $mesAno = $mesAno ?? date('Y-m');
        return $this->vendaRepository->getTotalVendasMes($mesAno);
    }
    
    /**
     * Vendas agrupadas por mês (para gráfico de barras)
     * 
     * CORREÇÃO (05/02/2026): Agora chama getVendasPorMes() (nome correto do método no Repository)
     * 
     * @param int $meses Quantidade de meses para buscar
     * @return array [['mes' => '2026-01', 'total' => 1500.00, 'quantidade' => 3], ...]
     */
    public function getVendasMensais(int $meses = 6): array
    {
        try {
            return $this->vendaRepository->getVendasPorMes($meses);
        } catch (\Exception $e) {
            error_log("[VendaService::getVendasMensais] Erro: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ranking das vendas mais rentáveis
     * 
     * CORREÇÃO (05/02/2026): Agora chama getMaisRentaveis() (nome correto)
     * 
     * @param int $limite
     * @return array Vendas ordenadas por rentabilidade_hora DESC
     */
    public function getRankingRentabilidade(int $limite = 5): array
    {
        try {
            return $this->vendaRepository->getMaisRentaveis($limite);
        } catch (\Exception $e) {
            error_log("[VendaService::getRankingRentabilidade] Erro: " . $e->getMessage());
            return [];
        }
    }
}