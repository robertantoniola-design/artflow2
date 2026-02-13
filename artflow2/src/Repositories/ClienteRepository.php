<?php
namespace App\Repositories;

use App\Models\Cliente;
use PDO;

/**
 * ============================================
 * REPOSITORY: CLIENTES
 * ============================================
 * 
 * FASE 1 (12/02/2026): Correções B5, B6, B7, BX
 * MELHORIA 1 (13/02/2026): Paginação padronizada (padrão Tags)
 * 
 * Métodos de paginação:
 * - allPaginated(): Lista paginada com filtros
 * - countAll(): Conta total de registros (com ou sem filtro)
 * - searchPaginated(): Busca paginada por termo
 */
class ClienteRepository extends BaseRepository
{
    protected string $table = 'clientes';
    protected string $model = Cliente::class;
    protected array $fillable = [
        'nome', 'email', 'telefone', 'empresa',
        'endereco', 'cidade', 'estado', 'observacoes'
    ];
    
    // ==========================================
    // LISTAGEM E PAGINAÇÃO (MELHORIA 1)
    // ==========================================
    
    /**
     * Lista clientes paginados com filtros opcionais
     * 
     * @param int $pagina Página atual (1-based)
     * @param int $porPagina Itens por página
     * @param string|null $termo Termo de busca (opcional)
     * @param string $ordenarPor Campo para ordenação
     * @param string $direcao ASC ou DESC
     * @return array Array de objetos Cliente
     */
    public function allPaginated(
        int $pagina = 1,
        int $porPagina = 12,
        ?string $termo = null,
        string $ordenarPor = 'nome',
        string $direcao = 'ASC'
    ): array {
        // Valida campos de ordenação permitidos
        $camposPermitidos = ['nome', 'email', 'cidade', 'created_at'];
        if (!in_array($ordenarPor, $camposPermitidos)) {
            $ordenarPor = 'nome';
        }
        
        // Sanitiza direção
        $direcao = strtoupper($direcao) === 'DESC' ? 'DESC' : 'ASC';
        
        // Calcula offset
        $offset = ($pagina - 1) * $porPagina;
        
        // Monta query base
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Adiciona filtro de busca se houver termo
        if ($termo) {
            $sql .= " WHERE (
                nome LIKE :termo1 
                OR email LIKE :termo2 
                OR telefone LIKE :termo3
                OR empresa LIKE :termo4
                OR cidade LIKE :termo5
            )";
            $params['termo1'] = "%{$termo}%";
            $params['termo2'] = "%{$termo}%";
            $params['termo3'] = "%{$termo}%";
            $params['termo4'] = "%{$termo}%";
            $params['termo5'] = "%{$termo}%";
        }
        
        // Ordenação e paginação
        $sql .= " ORDER BY {$ordenarPor} {$direcao} LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind dos parâmetros de busca
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        // Bind de limit e offset (devem ser INT)
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Conta total de clientes (com ou sem filtro)
     * 
     * @param string|null $termo Termo de busca (opcional)
     * @return int Total de registros
     */
    public function countAll(?string $termo = null): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        
        if ($termo) {
            $sql .= " WHERE (
                nome LIKE :termo1 
                OR email LIKE :termo2 
                OR telefone LIKE :termo3
                OR empresa LIKE :termo4
                OR cidade LIKE :termo5
            )";
            $params['termo1'] = "%{$termo}%";
            $params['termo2'] = "%{$termo}%";
            $params['termo3'] = "%{$termo}%";
            $params['termo4'] = "%{$termo}%";
            $params['termo5'] = "%{$termo}%";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Lista todos os clientes ordenados por nome (A-Z)
     * 
     * Mantido para compatibilidade com código existente.
     * 
     * @return array Array de objetos Cliente
     */
    public function allOrdered(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY nome ASC";
        $stmt = $this->db->query($sql);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // ==========================================
    // BUSCAS ESPECÍFICAS
    // ==========================================
    
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
     * Busca clientes com termo (nome, email, empresa, telefone, cidade)
     * 
     * CORREÇÃO B7: Agora busca também por telefone e cidade.
     * Mantido para compatibilidade - para busca paginada use allPaginated()
     * 
     * @param string $termo
     * @return array
     */
    public function search(string $termo): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE nome LIKE :t1 
                   OR email LIKE :t2 
                   OR empresa LIKE :t3
                   OR telefone LIKE :t4
                   OR cidade LIKE :t5
                ORDER BY nome ASC";
        
        $like = "%{$termo}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            't1' => $like,
            't2' => $like,
            't3' => $like,
            't4' => $like,
            't5' => $like
        ]);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // ==========================================
    // RELACIONAMENTOS
    // ==========================================
    
    /**
     * Verifica se cliente tem vendas associadas
     * 
     * CORREÇÃO B6: Método usado pelo Service antes de excluir.
     * Impede exclusão de cliente com histórico de vendas.
     * 
     * @param int $clienteId
     * @return bool
     */
    public function hasVendas(int $clienteId): bool
    {
        $sql = "SELECT COUNT(*) FROM vendas WHERE cliente_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clienteId]);
        
        return (int) $stmt->fetchColumn() > 0;
    }
    
    /**
     * Retorna histórico de compras do cliente
     * 
     * CORREÇÃO B4: Usado pela show.php para exibir histórico.
     * 
     * @param int $clienteId
     * @return array Vendas do cliente com dados da arte
     */
    public function getHistoricoCompras(int $clienteId): array
    {
        $sql = "SELECT v.*, a.nome as arte_nome
                FROM vendas v
                LEFT JOIN artes a ON v.arte_id = a.id
                WHERE v.cliente_id = ?
                ORDER BY v.data_venda DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clienteId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==========================================
    // ESTATÍSTICAS E RANKINGS
    // ==========================================
    
    /**
     * Verifica se email já existe (exceto para um ID específico)
     * 
     * CORREÇÃO B6: Usado na validação de unicidade de email.
     * 
     * @param string $email
     * @param int|null $exceptId ID a ignorar (para edição)
     * @return bool
     */
    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn() > 0;
    }
    
    /**
     * Retorna top clientes por valor de compras
     * 
     * CORREÇÃO B5: getTopCompradores() não existia.
     * Retorna ARRAYS (não objetos) para preservar campos calculados.
     * 
     * @param int $limit
     * @return array Arrays com dados do cliente + estatísticas
     */
    public function getTopCompradores(int $limit = 10): array
    {
        $sql = "SELECT 
                    c.id,
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
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Alias para getTopCompradores (compatibilidade)
     */
    public function topClientes(int $limit = 10): array
    {
        return $this->getTopCompradores($limit);
    }
}