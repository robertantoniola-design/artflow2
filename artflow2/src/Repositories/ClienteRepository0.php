<?php
namespace App\Repositories;

use App\Models\Cliente;

/**
 * REPOSITORY: CLIENTES
 * 
 * CORREÇÃO (31/01/2026):
 * - topClientes(): Retorna arrays ao invés de objetos hydrated
 *   para preservar campos calculados (total_compras, valor_total_compras)
 * - getTopCompradores(): Alias atualizado
 */
class ClienteRepository extends BaseRepository
{
    protected string $table = 'clientes';
    protected string $model = Cliente::class;
    protected array $fillable = [
        'nome', 'email', 'telefone', 'empresa',
        'endereco', 'cidade', 'estado', 'observacoes'
    ];
    
    /**
     * Busca cliente por email
     */
    public function findByEmail(string $email): ?Cliente
    {
        return $this->findFirstBy('email', $email);
    }
    
    /**
     * Busca clientes por cidade
     */
    public function findByCidade(string $cidade): array
    {
        return $this->findBy('cidade', $cidade);
    }
    
    /**
     * Busca clientes com termo (nome, email, empresa)
     */
    public function search(string $termo): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE nome LIKE :t1 
                   OR email LIKE :t2 
                   OR empresa LIKE :t3
                ORDER BY nome ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            't1' => "%{$termo}%",
            't2' => "%{$termo}%",
            't3' => "%{$termo}%"
        ]);
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Lista clientes com estatísticas de compras
     */
    public function allWithStats(): array
    {
        $sql = "SELECT c.*,
                    COUNT(v.id) as total_compras,
                    COALESCE(SUM(v.valor), 0) as valor_total_compras
                FROM {$this->table} c
                LEFT JOIN vendas v ON c.id = v.cliente_id
                GROUP BY c.id
                ORDER BY c.nome ASC";
        
        $stmt = $this->getConnection()->query($sql);
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Retorna top clientes por valor de compras
     * 
     * CORREÇÃO: Retorna ARRAYS (não objetos) para preservar
     * os campos calculados total_compras e valor_total_compras.
     * O hydrateMany() descartava esses campos extras.
     * 
     * @param int $limit
     * @return array Array de arrays associativos
     */
    public function topClientes(int $limit = 10): array
    {
        $sql = "SELECT c.id,
                    c.nome,
                    c.email,
                    c.telefone,
                    c.cidade,
                    c.estado,
                    COUNT(v.id) as total_compras,
                    COALESCE(SUM(v.valor), 0) as valor_total_compras
                FROM {$this->table} c
                INNER JOIN vendas v ON c.id = v.cliente_id
                GROUP BY c.id, c.nome, c.email, c.telefone, c.cidade, c.estado
                ORDER BY valor_total_compras DESC
                LIMIT :limite";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Lista clientes paginados
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['termo'])) {
            $where[] = "(nome LIKE :t1 OR email LIKE :t2 OR empresa LIKE :t3)";
            $params['t1'] = $params['t2'] = $params['t3'] = "%{$filters['termo']}%";
        }
        
        if (!empty($filters['cidade'])) {
            $where[] = "cidade = :cidade";
            $params['cidade'] = $filters['cidade'];
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // Total
        $stmt = $this->getConnection()->prepare("SELECT COUNT(*) FROM {$this->table} {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return [
            'data' => $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC)),
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page
        ];
    }
    
    /**
     * Alias para topClientes (compatibilidade)
     */
    public function getTopCompradores(int $limit = 10): array
    {
        return $this->topClientes($limit);
    }
    
    /**
     * Retorna todos clientes ordenados por nome
     */
    public function allOrdered(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY nome ASC";
        $stmt = $this->getConnection()->query($sql);
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Verifica se cliente tem vendas associadas
     */
    public function hasVendas(int $clienteId): bool
    {
        $sql = "SELECT COUNT(*) FROM vendas WHERE cliente_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([$clienteId]);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica se email já existe (exceto para um ID específico)
     */
    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}
