<?php

namespace App\Repositories;

use App\Models\Arte;
use PDO;

/**
 * ============================================
 * REPOSITORY: ARTES
 * ============================================
 * 
 * FASE 1 (15/02/2026): Estabilização CRUD (11 bugs corrigidos)
 * MELHORIA 1 (16/02/2026): Paginação padronizada (padrão Tags/Clientes)
 *   - allPaginated(): Lista paginada com filtros combinados
 *   - countAll(): Conta total de registros (com ou sem filtros)
 * 
 * NOTA: O método paginate() antigo permanece para compatibilidade,
 * mas NÃO deve ser usado no index(). Usar allPaginated() no lugar.
 * 
 * Herda CRUD genérico do BaseRepository e
 * adiciona métodos específicos para artes.
 */
class ArteRepository extends BaseRepository
{
    protected string $table = 'artes';
    protected string $model = Arte::class;
    
    // Campos permitidos para mass assignment (segurança)
    protected array $fillable = [
        'nome',
        'descricao',
        'tempo_medio_horas',
        'complexidade',
        'preco_custo',
        'horas_trabalhadas',
        'status',
        'imagem'
    ];
    
    // ==========================================
    // PAGINAÇÃO E FILTROS (MELHORIA 1)
    // ==========================================
    
    /**
     * ============================================
     * MELHORIA 1: Lista artes paginadas com filtros combinados
     * ============================================
     * 
     * Diferente do Clientes (que só filtra por termo), Artes precisa
     * combinar 3 filtros simultaneamente: termo, status e tag_id.
     * 
     * O filtro por tag_id usa subquery em arte_tags porque Artes tem
     * relacionamento N:N com Tags (tabela pivot arte_tags).
     * 
     * SEGURANÇA:
     * - Whitelist de colunas para ORDER BY (previne SQL injection)
     * - Parâmetros via bindValue (previne SQL injection nos filtros)
     * - LIMIT/OFFSET como PDO::PARAM_INT (obrigatório MySQL)
     * 
     * @param int         $pagina    Página atual (1-based)
     * @param int         $porPagina Itens por página (default 12)
     * @param string|null $termo     Busca por nome/descrição (LIKE)
     * @param string|null $status    Filtro por status ENUM
     * @param int|null    $tagId     Filtro por tag (via subquery)
     * @param string      $ordenarPor Coluna de ordenação (whitelist)
     * @param string      $direcao   ASC ou DESC
     * @return array Array de objetos Arte
     */
    public function allPaginated(
        int $pagina = 1,
        int $porPagina = 12,
        ?string $termo = null,
        ?string $status = null,
        ?int $tagId = null,
        string $ordenarPor = 'created_at',
        string $direcao = 'DESC'
    ): array {
        // ── WHITELIST de colunas (previne SQL Injection no ORDER BY) ──
        // Preparado para Melhoria 2 (ordenação dinâmica) — já inclui todas
        // as colunas que serão ordenáveis, mas na M1 o default é created_at
        $camposPermitidos = [
            'nome', 'complexidade', 'preco_custo',
            'horas_trabalhadas', 'status', 'created_at'
        ];
        if (!in_array($ordenarPor, $camposPermitidos)) {
            $ordenarPor = 'created_at'; // fallback seguro
        }
        
        // Sanitiza direção: apenas ASC ou DESC
        $direcao = strtoupper($direcao) === 'ASC' ? 'ASC' : 'DESC';
        
        // Calcula OFFSET (página 1 = offset 0, página 2 = offset 12, etc.)
        $offset = ($pagina - 1) * $porPagina;
        
        // ── CONSTRUÇÃO DINÂMICA DO WHERE ──
        // Usa WHERE 1=1 para permitir concatenação simples com AND
        // Todos os filtros são opcionais e combinam entre si
        $conditions = [];
        $params = [];
        
        // Filtro por termo (busca em nome e descrição)
        if ($termo !== null && trim($termo) !== '') {
            $conditions[] = "(nome LIKE :termo1 OR descricao LIKE :termo2)";
            $params[':termo1'] = '%' . trim($termo) . '%';
            $params[':termo2'] = '%' . trim($termo) . '%';
        }
        
        // Filtro por status (valor exato do ENUM)
        if ($status !== null && trim($status) !== '') {
            $conditions[] = "status = :status";
            $params[':status'] = $status;
        }
        
        // Filtro por tag_id (subquery na tabela pivot arte_tags)
        // Usa subquery ao invés de JOIN para evitar duplicatas no resultado
        // (uma arte com 3 tags apareceria 3 vezes com JOIN)
        if ($tagId !== null && $tagId > 0) {
            $conditions[] = "id IN (SELECT arte_id FROM arte_tags WHERE tag_id = :tag_id)";
            $params[':tag_id'] = $tagId;
        }
        
        // Monta cláusula WHERE completa
        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }
        
        // ── QUERY PRINCIPAL ──
        $sql = "SELECT * FROM {$this->table} 
                {$whereClause} 
                ORDER BY {$ordenarPor} {$direcao} 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind dos parâmetros de filtro (string)
        foreach ($params as $key => $value) {
            // tag_id precisa ser INT, os demais são STR
            if ($key === ':tag_id') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        // Bind de LIMIT e OFFSET como inteiros (obrigatório para MySQL)
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Hidrata resultados em objetos Arte
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * ============================================
     * MELHORIA 1: Conta total de artes (com filtros combinados)
     * ============================================
     * 
     * Necessário para calcular o total de páginas na paginação.
     * Aceita os MESMOS filtros do allPaginated() para que a contagem
     * reflita exatamente os resultados filtrados.
     * 
     * Exemplo: Se existem 50 artes, mas o filtro status=disponivel
     * retorna 15, a paginação mostra 2 páginas (15/12 = 1.25 → 2).
     * 
     * @param string|null $termo  Filtro de busca por nome/descrição
     * @param string|null $status Filtro por status ENUM
     * @param int|null    $tagId  Filtro por tag (via subquery)
     * @return int Total de registros que correspondem aos filtros
     */
    public function countAll(
        ?string $termo = null,
        ?string $status = null,
        ?int $tagId = null
    ): int {
        // ── MESMA lógica de WHERE do allPaginated ──
        // IMPORTANTE: Se alterar os filtros aqui, alterar lá também!
        $conditions = [];
        $params = [];
        
        if ($termo !== null && trim($termo) !== '') {
            $conditions[] = "(nome LIKE :termo1 OR descricao LIKE :termo2)";
            $params[':termo1'] = '%' . trim($termo) . '%';
            $params[':termo2'] = '%' . trim($termo) . '%';
        }
        
        if ($status !== null && trim($status) !== '') {
            $conditions[] = "status = :status";
            $params[':status'] = $status;
        }
        
        if ($tagId !== null && $tagId > 0) {
            $conditions[] = "id IN (SELECT arte_id FROM arte_tags WHERE tag_id = :tag_id)";
            $params[':tag_id'] = $tagId;
        }
        
        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
    
    // ========================================
    // MÉTODOS DE BUSCA ESPECÍFICOS (originais)
    // ========================================
    
    /**
     * Busca artes por status
     * 
     * @param string $status Status desejado
     * @return Arte[]
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = :status 
                ORDER BY created_at DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['status' => $status]);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca artes disponíveis para venda
     * 
     * @return Arte[]
     */
    public function findDisponiveis(): array
    {
        return $this->findByStatus(Arte::STATUS_DISPONIVEL);
    }
    
    /**
     * Busca artes em produção
     * 
     * @return Arte[]
     */
    public function findEmProducao(): array
    {
        return $this->findByStatus(Arte::STATUS_EM_PRODUCAO);
    }
    
    /**
     * Busca artes vendidas
     * 
     * @return Arte[]
     */
    public function findVendidas(): array
    {
        return $this->findByStatus(Arte::STATUS_VENDIDA);
    }
    
    /**
     * Busca artes por complexidade
     * 
     * @param string $complexidade
     * @return Arte[]
     */
    public function findByComplexidade(string $complexidade): array
    {
        return $this->findBy('complexidade', $complexidade);
    }
    
    /**
     * Busca artes por termo (nome ou descrição)
     * Mantido para compatibilidade. Para listagem paginada, use allPaginated().
     * 
     * @param string $termo Termo de busca
     * @param string|null $status Filtro opcional de status
     * @return Arte[]
     */
    public function search(string $termo, ?string $status = null): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (nome LIKE :termo OR descricao LIKE :termo2)";
        
        $params = [
            'termo' => "%{$termo}%",
            'termo2' => "%{$termo}%"
        ];
        
        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca artes por tag
     * 
     * @param int $tagId ID da tag
     * @return Arte[]
     */
    public function findByTag(int $tagId): array
    {
        $sql = "SELECT a.* FROM {$this->table} a
                INNER JOIN arte_tags at ON a.id = at.arte_id
                WHERE at.tag_id = :tag_id
                ORDER BY a.nome ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['tag_id' => $tagId]);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // ========================================
    // ESTATÍSTICAS
    // ========================================
    
    /**
     * Conta artes por status
     * 
     * @return array ['disponivel' => N, 'em_producao' => N, ...]
     */
    public function countByStatus(): array
    {
        $sql = "SELECT status, COUNT(*) as total 
                FROM {$this->table} 
                GROUP BY status";
        
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [
            'disponivel' => 0,
            'em_producao' => 0,
            'vendida' => 0,
            'reservada' => 0
        ];
        
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['total'];
        }
        
        return $result;
    }
    
    // ========================================
    // [MELHORIA 6] ESTATÍSTICAS PARA GRÁFICOS
    // ========================================

    /**
     * [M6] Conta artes agrupadas por complexidade
     * 
     * Mesmo padrão do countByStatus(), mas agrupa por complexidade.
     * Usado no gráfico de barras horizontais da index.php.
     * 
     * @return array ['baixa' => N, 'media' => N, 'alta' => N]
     */
    public function countByComplexidade(): array
    {
        $sql = "SELECT complexidade, COUNT(*) as total 
                FROM {$this->table} 
                GROUP BY complexidade";
        
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Inicializa com zeros (garante que todas as chaves existam)
        $result = [
            'baixa' => 0,
            'media' => 0,
            'alta'  => 0
        ];
        
        foreach ($rows as $row) {
            if (isset($result[$row['complexidade']])) {
                $result[$row['complexidade']] = (int) $row['total'];
            }
        }
        
        return $result;
    }

    /**
     * [M6] Retorna resumo financeiro para cards de indicadores
     * 
     * Query única que calcula todos os indicadores de uma vez
     * (mais eficiente que múltiplas queries separadas).
     * 
     * Indicadores:
     * - total: Total de artes no banco
     * - valor_estoque: SUM(preco_custo) das artes NÃO vendidas
     * - horas_totais: SUM(horas_trabalhadas) de todas as artes
     * - disponiveis: COUNT de artes com status 'disponivel'
     * 
     * @return array Associativo com os 4 indicadores
     */
    public function getResumoFinanceiro(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(
                        CASE WHEN status IN ('disponivel', 'em_producao', 'reservada') 
                        THEN preco_custo ELSE 0 END
                    ), 0) as valor_estoque,
                    COALESCE(SUM(horas_trabalhadas), 0) as horas_totais,
                    SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END) as disponiveis
                FROM {$this->table}";
        
        $result = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total'          => (int)   ($result['total'] ?? 0),
            'valor_estoque'  => (float) ($result['valor_estoque'] ?? 0),
            'horas_totais'   => (float) ($result['horas_totais'] ?? 0),
            'disponiveis'    => (int)   ($result['disponiveis'] ?? 0),
        ];
    }


    // ========================================
    // RELACIONAMENTO COM TAGS (N:N)
    // ========================================
    
    /**
     * Sincroniza tags de uma arte (remove antigas + insere novas)
     * 
     * @param int $arteId
     * @param array $tagIds Array de IDs de tags
     */
    public function sincronizarTags(int $arteId, array $tagIds): void
    {
        $pdo = $this->getConnection();
        
        // Remove todas as tags atuais
        $stmt = $pdo->prepare("DELETE FROM arte_tags WHERE arte_id = ?");
        $stmt->execute([$arteId]);
        
        // Adiciona novas associações
        if (!empty($tagIds)) {
            $stmt = $pdo->prepare("INSERT INTO arte_tags (arte_id, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tagId) {
                $stmt->execute([$arteId, $tagId]);
            }
        }
    }
    
    /**
     * Retorna IDs das tags de uma arte
     * 
     * @param int $arteId
     * @return int[]
     */
    public function getTagIds(int $arteId): array
    {
        $sql = "SELECT tag_id FROM arte_tags WHERE arte_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([$arteId]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Carrega arte com suas tags
     * 
     * @param int $id
     * @return Arte|null
     */
    public function findWithTags(int $id): ?Arte
    {
        $arte = $this->find($id);
        
        if ($arte) {
            $sql = "SELECT t.* FROM tags t
                    INNER JOIN arte_tags at ON t.id = at.tag_id
                    WHERE at.arte_id = ?
                    ORDER BY t.nome";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([$id]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $arte->setTags($tags);
        }
        
        return $arte;
    }
    
    // ========================================
    // PAGINAÇÃO LEGADO (manter compatibilidade)
    // ========================================
    
    /**
     * Lista artes paginadas com filtros (MÉTODO LEGADO)
     * 
     * NOTA: Mantido para compatibilidade. Para a listagem index,
     * use allPaginated() que segue o padrão de Tags/Clientes.
     * 
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @param array $filters Filtros (status, complexidade, termo)
     * @return array ['data' => Arte[], 'total' => int, 'pages' => int]
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['complexidade'])) {
            $where[] = "complexidade = :complexidade";
            $params['complexidade'] = $filters['complexidade'];
        }
        
        if (!empty($filters['termo'])) {
            $where[] = "(nome LIKE :termo OR descricao LIKE :termo2)";
            $params['termo'] = "%{$filters['termo']}%";
            $params['termo2'] = "%{$filters['termo']}%";
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->getConnection()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY {$orderBy} {$orderDir}
                LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return [
            'data' => $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC)),
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
            'per_page' => $perPage
        ];
    }
}