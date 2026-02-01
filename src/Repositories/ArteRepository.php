<?php
namespace App\Repositories;

use App\Models\Arte;
use App\Core\Database;

/**
 * ============================================
 * REPOSITORY: ARTES
 * ============================================
 * 
 * Responsável por todas as operações de banco
 * relacionadas à tabela 'artes'.
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
    
    // ========================================
    // MÉTODOS DE BUSCA ESPECÍFICOS
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
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
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
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
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
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    // ========================================
    // ESTATÍSTICAS
    // ========================================
    
    /**
     * Retorna estatísticas gerais das artes
     */
    public function getEstatisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END) as disponiveis,
                    SUM(CASE WHEN status = 'em_producao' THEN 1 ELSE 0 END) as em_producao,
                    SUM(CASE WHEN status = 'vendida' THEN 1 ELSE 0 END) as vendidas,
                    SUM(CASE WHEN status = 'reservada' THEN 1 ELSE 0 END) as reservadas,
                    AVG(horas_trabalhadas) as media_horas,
                    SUM(preco_custo) as custo_total,
                    AVG(preco_custo) as custo_medio
                FROM {$this->table}";
        
        $result = $this->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        
        // Garante valores numéricos
        return [
            'total' => (int)($result['total'] ?? 0),
            'disponiveis' => (int)($result['disponiveis'] ?? 0),
            'em_producao' => (int)($result['em_producao'] ?? 0),
            'vendidas' => (int)($result['vendidas'] ?? 0),
            'reservadas' => (int)($result['reservadas'] ?? 0),
            'media_horas' => round((float)($result['media_horas'] ?? 0), 2),
            'custo_total' => (float)($result['custo_total'] ?? 0),
            'custo_medio' => round((float)($result['custo_medio'] ?? 0), 2),
        ];
    }
    
    /**
     * Conta artes por status
     */
    public function countByStatus(): array
    {
        $sql = "SELECT status, COUNT(*) as total 
                FROM {$this->table} 
                GROUP BY status";
        
        $stmt = $this->getConnection()->query($sql);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['total'];
        }
        
        return $counts;
    }
    
    /**
     * Conta artes por complexidade
     */
    public function countByComplexidade(): array
    {
        $sql = "SELECT complexidade, COUNT(*) as total 
                FROM {$this->table} 
                GROUP BY complexidade";
        
        $stmt = $this->getConnection()->query($sql);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['complexidade']] = (int)$row['total'];
        }
        
        return $counts;
    }
    
    // ========================================
    // OPERAÇÕES DE HORAS
    // ========================================
    
    /**
     * Adiciona horas trabalhadas a uma arte
     * 
     * @param int $id ID da arte
     * @param float $horas Horas a adicionar
     * @return bool
     */
    public function adicionarHoras(int $id, float $horas): bool
    {
        $sql = "UPDATE {$this->table} 
                SET horas_trabalhadas = horas_trabalhadas + :horas,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'horas' => $horas
        ]);
    }
    
    /**
     * Atualiza status de uma arte
     * 
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function atualizarStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }
    
    // ========================================
    // RELACIONAMENTOS COM TAGS
    // ========================================
    
    /**
     * Associa tags a uma arte
     * 
     * @param int $arteId
     * @param array $tagIds Array de IDs de tags
     */
    public function syncTags(int $arteId, array $tagIds): void
    {
        $pdo = $this->getConnection();
        
        // Remove associações antigas
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
        
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
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
            $tags = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $arte->setTags($tags);
        }
        
        return $arte;
    }
    
    // ========================================
    // PAGINAÇÃO COM FILTROS
    // ========================================
    
    /**
     * Lista artes paginadas com filtros
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
        
        // Filtro por status
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }
        
        // Filtro por complexidade
        if (!empty($filters['complexidade'])) {
            $where[] = "complexidade = :complexidade";
            $params['complexidade'] = $filters['complexidade'];
        }
        
        // Filtro por termo de busca
        if (!empty($filters['termo'])) {
            $where[] = "(nome LIKE :termo OR descricao LIKE :termo2)";
            $params['termo'] = "%{$filters['termo']}%";
            $params['termo2'] = "%{$filters['termo']}%";
        }
        
        // Monta WHERE
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // Conta total
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->getConnection()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        // Calcula paginação
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        // Busca dados
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY {$orderBy} {$orderDir}
                LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return [
            'data' => $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC)),
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
            'per_page' => $perPage
        ];
    }
}
