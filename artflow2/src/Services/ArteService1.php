<?php

namespace App\Services;

use App\Models\Arte;
use App\Repositories\ArteRepository;
use App\Repositories\TagRepository;
use App\Validators\ArteValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * ARTE SERVICE — CORRIGIDO Fase 1 (T1 + T11) + MELHORIA 1 (Paginação)
 * ============================================
 * 
 * Camada de lógica de negócio para Artes.
 * Orquestra validação, repository e regras de negócio.
 * 
 * CORREÇÕES APLICADAS:
 * ─────────────────────
 * [Bug T1]  Método listar() — normalização de filtros com ?: null
 * [Bug T11] Método validarTransicaoStatus() — 'reservada' adicionada
 * 
 * MELHORIAS:
 * ──────────
 * [M1 16/02/2026] listarPaginado() — paginação 12/página com filtros combinados
 *   Substitui listar() na listagem index. Os filtros (termo, status, tag_id)
 *   são aplicados simultaneamente (não mais mutuamente exclusivos).
 */
class ArteService
{
    private ArteRepository $arteRepository;
    private TagRepository $tagRepository;
    private ArteValidator $validator;
    
    // ── Constante de paginação (mesmo padrão de Tags e Clientes) ──
    // 12 itens = 3 linhas × 4 colunas em layout XL, ou 4 linhas × 3 em LG
    const POR_PAGINA = 12;
    
    public function __construct(
        ArteRepository $arteRepository,
        TagRepository $tagRepository,
        ArteValidator $validator
    ) {
        $this->arteRepository = $arteRepository;
        $this->tagRepository = $tagRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // PAGINAÇÃO (MELHORIA 1)
    // ==========================================
    
    /**
     * ============================================
     * MELHORIA 1: Lista artes paginadas com filtros combinados
     * ============================================
     * 
     * Método principal para a listagem index. Substitui listar() na
     * view de listagem porque aplica paginação + filtros combinados.
     * 
     * DIFERENÇA vs listar():
     * - listar() usa if/elseif (filtros mutuamente exclusivos)
     * - listarPaginado() aplica TODOS os filtros simultaneamente
     *   via ArteRepository::allPaginated() (query dinâmica com AND)
     * 
     * Padrão idêntico ao ClienteService::listarPaginado().
     * 
     * @param array $filtros Filtros da URL:
     *   - 'termo'   => string|null  Busca por nome/descrição
     *   - 'status'  => string|null  Filtro por status ENUM
     *   - 'tag_id'  => int|null     Filtro por tag (N:N)
     *   - 'pagina'  => int          Página atual (default 1)
     *   - 'ordenar' => string       Coluna (default 'created_at')
     *   - 'direcao' => string       ASC|DESC (default 'DESC')
     * 
     * @return array [
     *     'artes'     => Arte[],     // Artes da página atual
     *     'paginacao' => [
     *         'total'        => int,  // Total de registros com filtros
     *         'porPagina'    => int,  // Itens por página (12)
     *         'paginaAtual'  => int,  // Página corrente
     *         'totalPaginas' => int,  // Total de páginas
     *         'temAnterior'  => bool, // Tem página anterior?
     *         'temProxima'   => bool, // Tem próxima página?
     *     ]
     * ]
     */
    public function listarPaginado(array $filtros = []): array
    {
        // ── Extrai parâmetros com defaults seguros ──
        // Normalização com ?: null resolve o problema T1 (string vazia → null)
        $termo   = $filtros['termo']  ?? null ?: null;
        $status  = $filtros['status'] ?? null ?: null;
        $tagId   = $filtros['tag_id'] ?? null ?: null;
        $pagina  = max(1, (int)($filtros['pagina'] ?? 1));
        $ordenar = $filtros['ordenar'] ?? 'created_at';
        $direcao = $filtros['direcao'] ?? 'DESC';
        
        // Converte tag_id para int (Router pode passar string)
        if ($tagId !== null) {
            $tagId = (int) $tagId;
        }
        
        // ── 1. Busca total de registros (com os mesmos filtros) ──
        $total = $this->arteRepository->countAll($termo, $status, $tagId);
        
        // ── 2. Calcula total de páginas ──
        $totalPaginas = (int) ceil($total / self::POR_PAGINA);
        
        // ── 3. Ajusta página se exceder o total ──
        // Ex: Usuário está na pag 5 e aplica filtro que retorna só 1 página
        if ($totalPaginas > 0 && $pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        
        // ── 4. Busca artes da página atual ──
        $artes = $this->arteRepository->allPaginated(
            $pagina,
            self::POR_PAGINA,
            $termo,
            $status,
            $tagId,
            $ordenar,
            $direcao
        );
        
        // ── 5. Retorna dados + metadados de paginação ──
        return [
            'artes' => $artes,
            'paginacao' => [
                'total'        => $total,
                'porPagina'    => self::POR_PAGINA,
                'paginaAtual'  => $pagina,
                'totalPaginas' => $totalPaginas,
                'temAnterior'  => $pagina > 1,
                'temProxima'   => $pagina < $totalPaginas
            ]
        ];
    }
    
    // ==========================================
    // OPERAÇÕES CRUD (mantidas da Fase 1)
    // ==========================================
    
    /**
     * Lista artes com filtros opcionais (MÉTODO ORIGINAL)
     * 
     * NOTA: Mantido para compatibilidade com outros módulos que chamam
     * ArteService::listar(). Para a listagem index, use listarPaginado().
     * 
     * Os filtros são mutuamente exclusivos nesta implementação.
     * O listarPaginado() já resolve isso com query dinâmica.
     * 
     * [Bug T1 CORRIGIDO] — Normalização de filtros com ?: null
     */
    public function listar(array $filtros = []): array
    {
        // ─── [T1 FIX] Normaliza filtros: converte "" para null ───
        $status = $filtros['status'] ?? null ?: null;
        $termo  = $filtros['termo']  ?? null ?: null;
        $tagId  = $filtros['tag_id'] ?? null ?: null;
        
        // Busca com filtro de status (sem termo de pesquisa)
        if ($status && !$termo) {
            return $this->arteRepository->findByStatus($status);
        }
        
        // Busca com termo de pesquisa (com ou sem status combinado)
        if ($termo) {
            return $this->arteRepository->search($termo, $status);
        }
        
        // Busca por tag
        if ($tagId) {
            return $this->arteRepository->findByTag((int) $tagId);
        }
        
        // Lista todas (sem filtros)
        return $this->arteRepository->all();
    }
    
    /**
     * Busca arte por ID
     * 
     * @param int $id
     * @return Arte
     * @throws NotFoundException
     */
    public function buscar(int $id): Arte
    {
        return $this->arteRepository->findOrFail($id);
    }
    
    /**
     * Cria nova arte
     * 
     * @param array $dados
     * @return Arte
     * @throws ValidationException
     */
    public function criar(array $dados): Arte
    {
        // Validação
        $this->validator->validate($dados);
        
        // Dados padrão
        $dados['status'] = $dados['status'] ?? 'disponivel';
        $dados['horas_trabalhadas'] = $dados['horas_trabalhadas'] ?? 0;
        $dados['preco_custo'] = $dados['preco_custo'] ?? 0;
        
        // Cria a arte
        $arte = $this->arteRepository->create($dados);
        
        // Associa tags se fornecidas
        if (!empty($dados['tags'])) {
            $this->tagRepository->syncArte($arte->getId(), (array) $dados['tags']);
        }
        
        return $arte;
    }
    
    /**
     * Atualiza arte existente
     * 
     * @param int $id
     * @param array $dados
     * @return Arte
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados): Arte
    {
        // Verifica se existe
        $arte = $this->arteRepository->findOrFail($id);
        
        // Validação
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Atualiza
        $this->arteRepository->update($id, $dados);
        
        // Atualiza tags se fornecidas
        if (isset($dados['tags'])) {
            $this->tagRepository->syncArte($id, (array) $dados['tags']);
        }
        
        return $this->arteRepository->find($id);
    }
    
    /**
     * Remove arte
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function remover(int $id): bool
    {
        // Verifica se existe
        $arte = $this->arteRepository->findOrFail($id);
        
        // Verifica se pode ser removida (não vendida)
        if ($arte->getStatus() === 'vendida') {
            throw new ValidationException([
                'arte' => 'Artes vendidas não podem ser removidas'
            ]);
        }
        
        // Remove associações com tags
        $this->tagRepository->syncArte($id, []);
        
        // Remove a arte
        return $this->arteRepository->delete($id);
    }
    
    // ==========================================
    // OPERAÇÕES DE STATUS
    // ==========================================
    
    /**
     * Altera status da arte
     * 
     * @param int $id
     * @param string $novoStatus
     * @return Arte
     */
    public function alterarStatus(int $id, string $novoStatus): Arte
    {
        $arte = $this->arteRepository->findOrFail($id);
        
        // Valida transição de status
        $this->validarTransicaoStatus($arte->getStatus(), $novoStatus);
        
        $this->arteRepository->update($id, ['status' => $novoStatus]);
        
        return $this->arteRepository->find($id);
    }
    
    /**
     * Valida se transição de status é permitida
     * 
     * [Bug T11 CORRIGIDO] — 'reservada' adicionada como origem E destino
     * 
     * @param string $atual Status atual
     * @param string $novo  Status desejado
     * @throws ValidationException Se transição não permitida
     */
    private function validarTransicaoStatus(string $atual, string $novo): void
    {
        // Mapa de transições permitidas (máquina de estados)
        $transicoesPermitidas = [
            'disponivel'  => ['em_producao', 'vendida', 'reservada'],
            'em_producao' => ['disponivel', 'vendida', 'reservada'],
            'vendida'     => [],  // Vendida é estado terminal
            'reservada'   => ['disponivel', 'em_producao', 'vendida'],
        ];
        
        // Se status atual não está no mapa, rejeita
        if (!isset($transicoesPermitidas[$atual])) {
            throw new ValidationException([
                'status' => "Status atual '{$atual}' não reconhecido"
            ]);
        }
        
        // Se status novo não está na lista de destinos permitidos
        if (!in_array($novo, $transicoesPermitidas[$atual] ?? [])) {
            throw new ValidationException([
                'status' => "Não é possível mudar de '{$atual}' para '{$novo}'"
            ]);
        }
    }
    
    // ==========================================
    // OPERAÇÕES DE TEMPO/HORAS
    // ==========================================
    
    /**
     * Adiciona horas trabalhadas à arte
     * 
     * @param int $id
     * @param float $horas
     * @return Arte
     */
    public function adicionarHoras(int $id, float $horas): Arte
    {
        $arte = $this->arteRepository->findOrFail($id);
        
        if ($horas <= 0) {
            throw new ValidationException([
                'horas' => 'As horas devem ser maiores que zero'
            ]);
        }
        
        $novasHoras = $arte->getHorasTrabalhadas() + $horas;
        $this->arteRepository->update($id, ['horas_trabalhadas' => $novasHoras]);
        
        return $this->arteRepository->find($id);
    }
    
    /**
     * Define horas trabalhadas da arte
     * 
     * @param int $id
     * @param float $horas
     * @return Arte
     */
    public function definirHoras(int $id, float $horas): Arte
    {
        $this->arteRepository->findOrFail($id);
        
        if ($horas < 0) {
            throw new ValidationException([
                'horas' => 'As horas não podem ser negativas'
            ]);
        }
        
        $this->arteRepository->update($id, ['horas_trabalhadas' => $horas]);
        
        return $this->arteRepository->find($id);
    }
    
    // ==========================================
    // CÁLCULOS E MÉTRICAS
    // ==========================================
    
    /**
     * Calcula custo por hora da arte
     * 
     * @param Arte $arte
     * @return float
     */
    public function calcularCustoPorHora(Arte $arte): float
    {
        $horas = $arte->getHorasTrabalhadas();
        
        if ($horas <= 0) {
            return 0;
        }
        
        return $arte->getPrecoCusto() / $horas;
    }
    
    /**
     * Calcula preço sugerido de venda (baseado em margem desejada)
     * 
     * @param Arte $arte
     * @param float $margemDesejada Percentual (ex: 50 para 50%)
     * @param float $valorHoraMinimo Valor mínimo da hora de trabalho
     * @return float
     */
    public function calcularPrecoSugerido(Arte $arte, float $margemDesejada = 50, float $valorHoraMinimo = 50): float
    {
        $custo = $arte->getPrecoCusto();
        $horas = $arte->getHorasTrabalhadas();
        
        // Custo de mão de obra
        $custoMaoObra = $horas * $valorHoraMinimo;
        
        // Custo total
        $custoTotal = $custo + $custoMaoObra;
        
        // Aplica margem
        $precoSugerido = $custoTotal * (1 + ($margemDesejada / 100));
        
        return round($precoSugerido, 2);
    }
    
    /**
     * Retorna estatísticas gerais das artes
     * 
     * @return array
     */
    public function getEstatisticas(): array
    {
        return $this->arteRepository->countByStatus();
    }
    
    /**
     * Retorna artes disponíveis para venda
     * 
     * @return array
     */
    public function getDisponiveisParaVenda(): array
    {
        $disponiveis = $this->arteRepository->findByStatus('disponivel');
        $emProducao = $this->arteRepository->findByStatus('em_producao');
        
        return array_merge($disponiveis, $emProducao);
    }
    
    // ==========================================
    // TAGS
    // ==========================================
    
    /**
     * Retorna tags de uma arte
     * 
     * @param int $arteId
     * @return array
     */
    public function getTags(int $arteId): array
    {
        return $this->tagRepository->getByArte($arteId);
    }
    
    /**
     * Atualiza tags de uma arte
     * 
     * @param int $arteId
     * @param array $tagIds
     * @return void
     */
    public function atualizarTags(int $arteId, array $tagIds): void
    {
        $this->arteRepository->findOrFail($arteId);
        $this->tagRepository->syncArte($arteId, $tagIds);
    }
    
    /**
     * Pesquisa artes por termo (alias para listar com filtros)
     * 
     * @param array $filtros
     * @param int $limit
     * @return array
     */
    public function pesquisar(array $filtros = [], int $limit = 10): array
    {
        $artes = $this->listar($filtros);
        return array_slice($artes, 0, $limit);
    }
}