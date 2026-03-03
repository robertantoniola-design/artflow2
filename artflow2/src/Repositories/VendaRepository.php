<?php
namespace App\Repositories;

use App\Models\Venda;
use App\Models\Arte;
use App\Models\Cliente;

/**
 * ============================================
 * REPOSITORY: VENDAS — FASE 1 + MELHORIAS M1+M2+M3
 * ============================================
 * 
 * FASE 1 (22/02/2026): Estabilização CRUD — todos os métodos originais
 * 
 * MELHORIAS (23/02/2026):
 * ──────────
 * [M1] allPaginated() — Paginação 12/página com JOINs + hydrating
 * [M2] Ordenação dinâmica via whitelist de colunas (segurança SQL)
 * [M3] countAll() + filtros combinados (WHERE dinâmico com AND)
 * 
 * NOTA: Os métodos paginate() e findPaginated() antigos permanecem
 * para compatibilidade. O index() deve usar allPaginated() + countAll().
 */
class VendaRepository extends BaseRepository
{
    protected string $table = 'vendas';
    protected string $model = Venda::class;
    protected array $fillable = [
        'arte_id', 'cliente_id', 'valor', 'data_venda',
        'lucro_calculado', 'rentabilidade_hora',
        'forma_pagamento', 'observacoes'
    ];
    
    // ==========================================
    // PAGINAÇÃO + FILTROS COMBINADOS (M1+M2+M3 — NOVO)
    // ==========================================
    
    /**
     * ============================================
     * M1+M2+M3: Lista vendas paginadas com filtros combinados e ordenação
     * ============================================
     * 
     * Método principal para a listagem index(). Substitui paginate() na
     * view porque aplica paginação + filtros combinados + ordenação dinâmica.
     * 
     * DIFERENÇA vs paginate() antigo:
     * - paginate() não suporta busca por termo nem forma de pagamento
     * - allPaginated() aplica TODOS os filtros simultaneamente (AND)
     * - Usa whitelist de colunas para ORDER BY (segurança contra SQL Injection)
     * - Retorna objetos Venda hydrated (não arrays brutos)
     * 
     * Padrão idêntico a ArteRepository::allPaginated() e ClienteRepository::allPaginated().
     * 
     * @param int $pagina Página atual (1-based)
     * @param int $porPagina Itens por página (default 12)
     * @param string|null $termo Busca por nome da arte ou observações
     * @param string|null $clienteId Filtro por cliente específico
     * @param string|null $formaPagamento Filtro por forma de pagamento
     * @param string|null $dataInicio Filtro: data_venda >= (BETWEEN início)
     * @param string|null $dataFim Filtro: data_venda <= (BETWEEN fim)
     * @param string $ordenarPor Coluna de ordenação (whitelist validada)
     * @param string $direcao ASC ou DESC
     * @return array Array de objetos Venda com Arte e Cliente hydrated
     */
    public function allPaginated(
        int $pagina = 1,
        int $porPagina = 12,
        ?string $termo = null,
        ?string $clienteId = null,
        ?string $formaPagamento = null,
        ?string $dataInicio = null,
        ?string $dataFim = null,
        string $ordenarPor = 'data_venda',
        string $direcao = 'DESC'
    ): array {
        // ── WHITELIST DE COLUNAS (previne SQL Injection) ──
        // Mapeia nomes amigáveis da URL para colunas reais do SQL
        // Vendas usa JOINs, então os nomes incluem alias de tabela
        $colunasPermitidas = [
            'data_venda'       => 'v.data_venda',        // Data da venda (padrão)
            'arte_nome'        => 'a.nome',              // Nome da arte (via JOIN)
            'cliente_nome'     => 'c.nome',              // Nome do cliente (via JOIN)
            'valor'            => 'v.valor',             // Valor da venda
            'lucro_calculado'  => 'v.lucro_calculado',   // Lucro calculado
            'forma_pagamento'  => 'v.forma_pagamento',   // Forma de pagamento
            'created_at'       => 'v.created_at'         // Data de criação
        ];
        
        // Se coluna não está na whitelist, usa data_venda como fallback seguro
        $colunaOrdem = $colunasPermitidas[$ordenarPor] ?? 'v.data_venda';
        
        // Sanitiza direção: apenas ASC ou DESC são válidos
        $direcao = strtoupper($direcao) === 'DESC' ? 'DESC' : 'ASC';
        
        // ── CONSTRUÇÃO DINÂMICA DO WHERE (filtros combinados com AND) ──
        $where = [];
        $params = [];
        
        // Filtro 1: Busca por termo (nome da arte OU observações da venda)
        // Usa LIKE com % nas duas pontas para busca parcial
        if ($termo !== null && $termo !== '') {
            $where[] = "(a.nome LIKE :termo1 OR v.observacoes LIKE :termo2)";
            $params['termo1'] = "%{$termo}%";
            $params['termo2'] = "%{$termo}%";
        }
        
        // Filtro 2: Cliente específico
        if ($clienteId !== null && $clienteId !== '') {
            $where[] = "v.cliente_id = :cliente_id";
            $params['cliente_id'] = (int) $clienteId;
        }
        
        // Filtro 3: Forma de pagamento
        if ($formaPagamento !== null && $formaPagamento !== '') {
            $where[] = "v.forma_pagamento = :forma_pagamento";
            $params['forma_pagamento'] = $formaPagamento;
        }
        
        // Filtro 4: Período (data início e/ou data fim)
        // Permite filtrar só por início, só por fim, ou ambos (BETWEEN)
        if ($dataInicio !== null && $dataInicio !== '') {
            $where[] = "v.data_venda >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
        if ($dataFim !== null && $dataFim !== '') {
            $where[] = "v.data_venda <= :data_fim";
            $params['data_fim'] = $dataFim;
        }
        
        // Monta cláusula WHERE (vazia = sem filtros = lista tudo)
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // ── OFFSET para paginação ──
        $offset = ($pagina - 1) * $porPagina;
        
        // ── QUERY PRINCIPAL com JOINs + filtros + ordenação + LIMIT ──
        // LEFT JOIN garante que vendas sem arte ou sem cliente apareçam
        $sql = "SELECT v.*,
                    a.nome as arte_nome, a.status as arte_status,
                    c.nome as cliente_nome, c.email as cliente_email
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                {$whereClause}
                ORDER BY {$colunaOrdem} {$direcao}
                LIMIT {$porPagina} OFFSET {$offset}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // ── HYDRATA OBJETOS com relacionamentos ──
        // Mesmo padrão do allWithRelations(): cria Venda + Arte + Cliente
        return array_map(function($row) {
            $venda = Venda::fromArray($row);
            
            // Hydrata Arte se existir (FK pode ser NULL por ON DELETE SET NULL)
            if (!empty($row['arte_id'])) {
                $venda->setArte(Arte::fromArray([
                    'id'     => $row['arte_id'],
                    'nome'   => $row['arte_nome'] ?? '',
                    'status' => $row['arte_status'] ?? ''
                ]));
            }
            
            // Hydrata Cliente se existir (FK pode ser NULL)
            if (!empty($row['cliente_id'])) {
                $venda->setCliente(Cliente::fromArray([
                    'id'    => $row['cliente_id'],
                    'nome'  => $row['cliente_nome'] ?? '',
                    'email' => $row['cliente_email'] ?? ''
                ]));
            }
            
            return $venda;
        }, $rows);
    }
    
    /**
     * ============================================
     * M1+M3: Conta total de vendas com os mesmos filtros combinados
     * ============================================
     * 
     * CRUCIAL: Deve usar EXATAMENTE os mesmos filtros do allPaginated()
     * para que a contagem de páginas seja consistente.
     * 
     * Se allPaginated() retorna 5 vendas por página com filtro "pix",
     * countAll() deve contar apenas vendas com forma_pagamento = 'pix'.
     * 
     * @param string|null $termo Busca por nome da arte ou observações
     * @param string|null $clienteId Filtro por cliente
     * @param string|null $formaPagamento Filtro por forma de pagamento
     * @param string|null $dataInicio Filtro: data_venda >=
     * @param string|null $dataFim Filtro: data_venda <=
     * @return int Total de registros que atendem os filtros
     */
    public function countAll(
        ?string $termo = null,
        ?string $clienteId = null,
        ?string $formaPagamento = null,
        ?string $dataInicio = null,
        ?string $dataFim = null
    ): int {
        // ── MESMA LÓGICA de filtros do allPaginated() ──
        $where = [];
        $params = [];
        
        if ($termo !== null && $termo !== '') {
            $where[] = "(a.nome LIKE :termo1 OR v.observacoes LIKE :termo2)";
            $params['termo1'] = "%{$termo}%";
            $params['termo2'] = "%{$termo}%";
        }
        
        if ($clienteId !== null && $clienteId !== '') {
            $where[] = "v.cliente_id = :cliente_id";
            $params['cliente_id'] = (int) $clienteId;
        }
        
        if ($formaPagamento !== null && $formaPagamento !== '') {
            $where[] = "v.forma_pagamento = :forma_pagamento";
            $params['forma_pagamento'] = $formaPagamento;
        }
        
        if ($dataInicio !== null && $dataInicio !== '') {
            $where[] = "v.data_venda >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
        
        if ($dataFim !== null && $dataFim !== '') {
            $where[] = "v.data_venda <= :data_fim";
            $params['data_fim'] = $dataFim;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // JOIN com artes é necessário porque o filtro de termo busca em a.nome
        $sql = "SELECT COUNT(*) 
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                {$whereClause}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
    
    // ==========================================
    // MÉTODOS ORIGINAIS (PRESERVADO 100% DA FASE 1)
    // ==========================================
    
    /**
     * Busca vendas com relacionamentos (arte e cliente)
     */
    public function allWithRelations(): array
    {
        $sql = "SELECT v.*,
                    a.nome as arte_nome, a.status as arte_status,
                    c.nome as cliente_nome, c.email as cliente_email
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                ORDER BY v.data_venda DESC";
        
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Hydrata com relacionamentos
        return array_map(function($row) {
            $venda = Venda::fromArray($row);
            
            if ($row['arte_id']) {
                $venda->setArte(Arte::fromArray([
                    'id' => $row['arte_id'],
                    'nome' => $row['arte_nome'],
                    'status' => $row['arte_status']
                ]));
            }
            
            if ($row['cliente_id']) {
                $venda->setCliente(Cliente::fromArray([
                    'id' => $row['cliente_id'],
                    'nome' => $row['cliente_nome'],
                    'email' => $row['cliente_email']
                ]));
            }
            
            return $venda;
        }, $rows);
    }
    
    /**
     * Busca venda por ID com relacionamentos
     */
    public function findWithRelations(int $id): ?Venda
    {
        $sql = "SELECT v.*,
                    a.id as arte_id, a.nome as arte_nome, a.status as arte_status,
                    a.preco_custo as arte_preco_custo, a.horas_trabalhadas as arte_horas,
                    c.id as cliente_id, c.nome as cliente_nome, c.email as cliente_email, c.telefone as cliente_telefone
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE v.id = ?";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        $venda = Venda::fromArray($row);
        
        if ($row['arte_id']) {
            $venda->setArte(Arte::fromArray([
                'id' => $row['arte_id'],
                'nome' => $row['arte_nome'],
                'status' => $row['arte_status'],
                'preco_custo' => $row['arte_preco_custo'],
                'horas_trabalhadas' => $row['arte_horas']
            ]));
        }
        
        if ($row['cliente_id']) {
            $venda->setCliente(Cliente::fromArray([
                'id' => $row['cliente_id'],
                'nome' => $row['cliente_nome'],
                'email' => $row['cliente_email'],
                'telefone' => $row['cliente_telefone'] ?? null
            ]));
        }
        
        return $venda;
    }
    
    /**
     * Busca vendas por período
     */
    public function findByPeriodo(string $dataInicio, string $dataFim): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE data_venda BETWEEN :inicio AND :fim
                ORDER BY data_venda DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca vendas do mês/ano
     */
    public function findByMesAno(int $ano, int $mes): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE YEAR(data_venda) = :ano AND MONTH(data_venda) = :mes
                ORDER BY data_venda DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca vendas do mês (formato YYYY-MM)
     */
    public function findByMes(string $mesAno): array
    {
        [$ano, $mes] = explode('-', $mesAno);
        return $this->findByMesAno((int)$ano, (int)$mes);
    }
    
    /**
     * Total de vendas do mês (formato YYYY-MM)
     */
    public function getTotalVendasMes(string $mesAno): float
    {
        [$ano, $mes] = explode('-', $mesAno);
        return $this->somaVendasMes((int)$ano, (int)$mes);
    }
    
    /**
     * Soma vendas do mês
     */
    public function somaVendasMes(int $ano, int $mes): float
    {
        $sql = "SELECT COALESCE(SUM(valor), 0) FROM {$this->table}
                WHERE YEAR(data_venda) = :ano AND MONTH(data_venda) = :mes";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Estatísticas gerais de vendas
     */
    public function getEstatisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_vendas,
                    COALESCE(SUM(valor), 0) as valor_total,
                    COALESCE(AVG(valor), 0) as ticket_medio,
                    COALESCE(SUM(lucro_calculado), 0) as lucro_total,
                    COALESCE(AVG(rentabilidade_hora), 0) as rentabilidade_media
                FROM {$this->table}";
        
        return $this->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Vendas agrupadas por mês (para gráficos)
     */
    public function vendasPorMes(int $meses = 12): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(data_venda, '%Y-%m') as mes,
                    COUNT(*) as quantidade,
                    SUM(valor) as total,
                    SUM(lucro_calculado) as lucro
                FROM {$this->table}
                WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                GROUP BY DATE_FORMAT(data_venda, '%Y-%m')
                ORDER BY mes ASC";
        
        return $this->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Vendas por cliente
     */
    public function vendasPorCliente(): array
    {
        $sql = "SELECT 
                    c.id, c.nome,
                    COUNT(v.id) as total_vendas,
                    SUM(v.valor) as valor_total
                FROM vendas v
                INNER JOIN clientes c ON v.cliente_id = c.id
                GROUP BY c.id
                ORDER BY valor_total DESC";
        
        return $this->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Paginação com filtros (LEGADO — para index use allPaginated())
     * Mantido para compatibilidade com código antigo.
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['cliente_id'])) {
            $where[] = "v.cliente_id = :cliente_id";
            $params['cliente_id'] = $filters['cliente_id'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $where[] = "v.data_venda >= :data_inicio";
            $params['data_inicio'] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $where[] = "v.data_venda <= :data_fim";
            $params['data_fim'] = $filters['data_fim'];
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // Total
        $stmt = $this->getConnection()->prepare(
            "SELECT COUNT(*) FROM {$this->table} v {$whereClause}"
        );
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT v.*, a.nome as arte_nome, c.nome as cliente_nome
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                {$whereClause}
                ORDER BY v.data_venda DESC
                LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return [
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page
        ];
    }
    
    /**
     * Busca vendas por cliente
     */
    public function findByCliente(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE cliente_id = :cliente_id
                ORDER BY data_venda DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['cliente_id' => $clienteId]);
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca vendas recentes
     */
    public function getRecentes(int $limit = 10): array
    {
        $sql = "SELECT v.*, a.nome as arte_nome, c.nome as cliente_nome
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                ORDER BY v.data_venda DESC, v.created_at DESC
                LIMIT {$limit}";
        
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Vendas agrupadas por mês (alias para vendasPorMes)
     */
    public function getVendasPorMes(int $meses = 12): array
    {
        return $this->vendasPorMes($meses);
    }
    
    /**
     * Vendas mais rentáveis
     */
    public function getMaisRentaveis(int $limit = 10): array
    {
        $sql = "SELECT v.*, a.nome as arte_nome, c.nome as cliente_nome
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE v.rentabilidade_hora IS NOT NULL AND v.rentabilidade_hora > 0
                ORDER BY v.rentabilidade_hora DESC
                LIMIT {$limit}";
        
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ══════════════════════════════════════════════════════════════
    // [MELHORIA 5] Estatísticas por Venda — Métodos para show.php
    // ══════════════════════════════════════════════════════════════

    /**
     * [M5] Retorna a posição de uma venda no ranking de rentabilidade
     * 
     * Conta quantas vendas têm rentabilidade_hora MAIOR que a venda atual,
     * então posição = count + 1. Se a venda não tem rentabilidade, retorna 0.
     * 
     * Exemplo: Se 3 vendas têm R$/h maior → posição = 4° lugar
     * 
     * @param int $vendaId ID da venda
     * @return int Posição no ranking (1 = mais rentável), 0 se sem rentabilidade
     */
    public function getPosicaoRanking(int $vendaId): int
    {
        // Primeiro busca a rentabilidade da venda específica
        $sqlCheck = "SELECT COALESCE(rentabilidade_hora, 0) 
                     FROM {$this->table} WHERE id = :id";
        $stmtCheck = $this->getConnection()->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $vendaId]);
        $rentabilidade = (float) $stmtCheck->fetchColumn();
        
        // Se não tem rentabilidade, retorna 0 (sem posição no ranking)
        if ($rentabilidade <= 0) {
            return 0;
        }
        
        // Conta quantas vendas têm rentabilidade MAIOR que esta
        $sql = "SELECT COUNT(*) 
                FROM {$this->table} 
                WHERE rentabilidade_hora > :rentabilidade
                AND rentabilidade_hora IS NOT NULL";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['rentabilidade' => $rentabilidade]);
        
        // Posição = quantas acima + 1
        return (int) $stmt->fetchColumn() + 1;
    }

    /**
     * [M5] Retorna o total de vendas com rentabilidade calculada
     * 
     * Usado para exibir "X° de Y vendas" no ranking do show.php.
     * Conta apenas vendas que têm rentabilidade_hora > 0.
     * 
     * @return int Total de vendas com rentabilidade
     */
    public function countComRentabilidade(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE rentabilidade_hora IS NOT NULL 
                AND rentabilidade_hora > 0";
        
        return (int) $this->getConnection()->query($sql)->fetchColumn();
    }

    // ══════════════════════════════════════════════════════════════
    // [MELHORIA 6] Gráficos — Distribuição por Forma de Pagamento
    // ══════════════════════════════════════════════════════════════

    /**
     * [M6] Conta vendas agrupadas por forma de pagamento
     * 
     * Retorna array associativo para alimentar o gráfico Doughnut
     * de distribuição de formas de pagamento no index.php.
     * 
     * Retorno: [['forma_pagamento' => 'pix', 'total' => 15], ...]
     * 
     * @return array Lista de formas de pagamento com contagem
     */
    public function countByFormaPagamento(): array
    {
        $sql = "SELECT forma_pagamento, COUNT(*) as total
                FROM {$this->table}
                GROUP BY forma_pagamento
                ORDER BY total DESC";
        
        return $this->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }



    // ══════════════════════════════════════════════════════════════
    // [MELHORIA 4] Relatório Aprimorado — Queries filtradas
    // ══════════════════════════════════════════════════════════════

    /**
     * [M4] Retorna anos distintos com vendas registradas
     * 
     * Alimenta o dropdown de filtro por ano no relatório.
     * Se não houver vendas, retorna array com o ano atual como fallback.
     * 
     * @return array Ex: [2026, 2025, 2024] (DESC)
     */
    public function getAnosDisponiveis(): array
    {
        $sql = "SELECT DISTINCT YEAR(data_venda) as ano 
                FROM {$this->table} 
                WHERE data_venda IS NOT NULL
                ORDER BY ano DESC";
        
        $rows = $this->getConnection()
                     ->query($sql)
                     ->fetchAll(\PDO::FETCH_COLUMN);
        
        // Fallback: se não houver vendas, retorna ano atual
        return !empty($rows) ? $rows : [(int) date('Y')];
    }

    /**
     * [M4] Estatísticas filtradas por período
     * 
     * Mesma lógica do getEstatisticas() global, mas com WHERE opcional.
     * Quando dataInicio/dataFim são null, retorna stats de TODAS as vendas
     * (comportamento idêntico ao getEstatisticas() original).
     * 
     * Adiciona campo margem_media (%) que o getEstatisticas() não tem.
     * 
     * @param string|null $dataInicio Formato YYYY-MM-DD (inclusive)
     * @param string|null $dataFim Formato YYYY-MM-DD (inclusive)
     * @return array Stats com total_vendas, valor_total, ticket_medio, lucro_total, margem_media, rentabilidade_media
     */
    public function getEstatisticasFiltradas(?string $dataInicio = null, ?string $dataFim = null): array
    {
        // ── Construção dinâmica do WHERE ──
        $where = [];
        $params = [];
        
        if ($dataInicio !== null && $dataInicio !== '') {
            $where[] = "data_venda >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
        if ($dataFim !== null && $dataFim !== '') {
            $where[] = "data_venda <= :data_fim";
            $params['data_fim'] = $dataFim;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // ── Query com margem média (novo campo vs getEstatisticas) ──
        // margem_media = AVG((lucro / valor) * 100) — só vendas com valor > 0
        $sql = "SELECT 
                    COUNT(*) as total_vendas,
                    COALESCE(SUM(valor), 0) as valor_total,
                    COALESCE(AVG(valor), 0) as ticket_medio,
                    COALESCE(SUM(lucro_calculado), 0) as lucro_total,
                    COALESCE(AVG(
                        CASE WHEN valor > 0 AND lucro_calculado IS NOT NULL 
                             THEN (lucro_calculado / valor) * 100 
                        END
                    ), 0) as margem_media,
                    COALESCE(AVG(
                        CASE WHEN rentabilidade_hora IS NOT NULL AND rentabilidade_hora > 0
                             THEN rentabilidade_hora
                        END
                    ), 0) as rentabilidade_media
                FROM {$this->table}
                {$whereClause}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * [M4] Vendas detalhadas com JOINs para tabela do relatório
     * 
     * Retorna arrays brutos (não objetos) com dados de arte e cliente
     * para exibição na tabela detalhada do relatório.
     * 
     * Inclui custo e horas da arte para cálculos na view.
     * 
     * @param string|null $dataInicio Formato YYYY-MM-DD
     * @param string|null $dataFim Formato YYYY-MM-DD
     * @param int $limit Máximo de registros (default 200)
     * @return array Arrays com dados completos da venda + arte + cliente
     */
    public function getVendasDetalhadas(?string $dataInicio = null, ?string $dataFim = null, int $limit = 200): array
    {
        $where = [];
        $params = [];
        
        if ($dataInicio !== null && $dataInicio !== '') {
            $where[] = "v.data_venda >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
        if ($dataFim !== null && $dataFim !== '') {
            $where[] = "v.data_venda <= :data_fim";
            $params['data_fim'] = $dataFim;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // LEFT JOIN: preserva vendas sem arte ou sem cliente (FK SET NULL)
        $sql = "SELECT v.id, v.valor, v.data_venda, v.lucro_calculado, 
                       v.rentabilidade_hora, v.forma_pagamento,
                       a.nome as arte_nome, a.preco_custo as arte_custo,
                       a.horas_trabalhadas as arte_horas,
                       c.nome as cliente_nome
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                {$whereClause}
                ORDER BY v.data_venda DESC
                LIMIT {$limit}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * [M4] Vendas agrupadas por mês com filtro de período
     * 
     * Similar ao vendasPorMes(), mas aceita filtros de data e
     * inclui campos adicionais (ticket_medio, margem_media) para
     * a tabela de comparativo mensal.
     * 
     * @param string|null $dataInicio Formato YYYY-MM-DD
     * @param string|null $dataFim Formato YYYY-MM-DD
     * @return array Dados mensais com faturamento, lucro, ticket, margem
     */
    public function vendasPorMesFiltradas(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $where = [];
        $params = [];
        
        if ($dataInicio !== null && $dataInicio !== '') {
            $where[] = "data_venda >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
        if ($dataFim !== null && $dataFim !== '') {
            $where[] = "data_venda <= :data_fim";
            $params['data_fim'] = $dataFim;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        $sql = "SELECT 
                    DATE_FORMAT(data_venda, '%Y-%m') as mes,
                    COUNT(*) as quantidade,
                    COALESCE(SUM(valor), 0) as faturamento,
                    COALESCE(SUM(lucro_calculado), 0) as lucro,
                    COALESCE(AVG(valor), 0) as ticket_medio,
                    COALESCE(AVG(
                        CASE WHEN valor > 0 AND lucro_calculado IS NOT NULL 
                             THEN (lucro_calculado / valor) * 100 
                        END
                    ), 0) as margem_media
                FROM {$this->table}
                {$whereClause}
                GROUP BY DATE_FORMAT(data_venda, '%Y-%m')
                ORDER BY mes ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * [M4] Distribuição por forma de pagamento filtrada por período
     * 
     * Similar ao countByFormaPagamento() (M6), mas com WHERE período.
     * Alimenta o gráfico Doughnut do relatório filtrado.
     * 
     * @param string|null $dataInicio Formato YYYY-MM-DD
     * @param string|null $dataFim Formato YYYY-MM-DD
     * @return array [['forma_pagamento' => 'pix', 'total' => 15], ...]
     */
    public function countByFormaPagamentoFiltrada(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $where = [];
        $params = [];
        
        if ($dataInicio !== null && $dataInicio !== '') {
            $where[] = "data_venda >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
        if ($dataFim !== null && $dataFim !== '') {
            $where[] = "data_venda <= :data_fim";
            $params['data_fim'] = $dataFim;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        $sql = "SELECT forma_pagamento, COUNT(*) as total
                FROM {$this->table}
                {$whereClause}
                GROUP BY forma_pagamento
                ORDER BY total DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}