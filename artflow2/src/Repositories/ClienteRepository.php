<?php
namespace App\Repositories;

use App\Models\Cliente;
use PDO;

/**
 * ============================================
 * REPOSITORY: CLIENTES
 * ============================================
 * 
 * CORREÇÕES FASE 1 (12/02/2026):
 * - B5: Adicionado getTopCompradores() como método real (antes era alias inexistente
 *       que causava "Call to undefined method" no DashboardController)
 * - B6: Adicionados hasVendas() e emailExists() — usados pelo ClienteService
 *       mas que não existiam, causando erros no remover() e validarEmailUnico()
 * - B7: search() agora busca também por telefone e cidade (antes só nome/email/empresa)
 * - BX: Adicionado allOrdered() — BaseRepository só tem all($orderBy, $direction),
 *        não tem allOrdered(). ClienteService.listar() chamava allOrdered() que
 *        não existia, causando Fatal Error ao acessar /clientes.
 * 
 * CORREÇÃO ANTERIOR (31/01/2026):
 * - topClientes(): Retorna arrays ao invés de objetos hydrated
 *   para preservar campos calculados (total_compras, valor_total_compras)
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
    // LISTAGEM
    // ==========================================
    
    /**
     * Lista todos os clientes ordenados por nome (A-Z)
     * 
     * CORREÇÃO BX: BaseRepository::all() existe com parâmetros ($orderBy, $direction),
     * mas NÃO existe allOrdered(). O ClienteService.listar() chamava allOrdered()
     * causando "Call to undefined method". Este método resolve isso.
     * 
     * Padrão igual ao TagRepository.allOrdered().
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
     * Antes: Só buscava nome, email, empresa
     * Agora: Inclui telefone e cidade para resultados mais completos
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
        
        $likeTermo = "%{$termo}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            't1' => $likeTermo,
            't2' => $likeTermo,
            't3' => $likeTermo,
            't4' => $likeTermo,
            't5' => $likeTermo
        ]);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
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
        
        $stmt = $this->db->query($sql);
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Retorna top clientes por valor de compras
     * 
     * Retorna ARRAYS (não objetos) para preservar os campos calculados
     * total_compras e valor_total_compras. O hydrateMany() descartava
     * esses campos extras.
     * 
     * @param int $limit Quantidade de clientes
     * @return array Arrays associativos com dados + estatísticas
     */
    public function topClientes(int $limit = 10): array
    {
        $sql = "SELECT c.*,
                    COUNT(v.id) as total_compras,
                    COALESCE(SUM(v.valor), 0) as valor_total_compras
                FROM {$this->table} c
                INNER JOIN vendas v ON c.id = v.cliente_id
                GROUP BY c.id
                ORDER BY valor_total_compras DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        // Retorna arrays para preservar campos calculados
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Alias para topClientes() — usado pelo ClienteService.getTopClientes()
     * 
     * CORREÇÃO B5: Este método não existia e causava:
     * "Call to undefined method ClienteRepository::getTopCompradores()"
     * no DashboardController quando chamava ClienteService.getTopClientes()
     * 
     * @param int $limit Quantidade de clientes
     * @return array Arrays associativos
     */
    public function getTopCompradores(int $limit = 10): array
    {
        return $this->topClientes($limit);
    }
    
    // ==========================================
    // HISTÓRICO DE COMPRAS
    // ==========================================
    
    /**
     * Busca histórico de compras de um cliente
     * 
     * CORREÇÃO B4: Método documentado mas não implementado.
     * Retorna vendas do cliente com dados da arte associada.
     * 
     * @param int $clienteId ID do cliente
     * @return array Arrays com dados das vendas + nome da arte
     */
    public function getHistoricoCompras(int $clienteId): array
    {
        $sql = "SELECT v.*,
                    a.nome as arte_nome,
                    a.status as arte_status
                FROM vendas v
                LEFT JOIN artes a ON v.arte_id = a.id
                WHERE v.cliente_id = :cliente_id
                ORDER BY v.data_venda DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cliente_id' => $clienteId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==========================================
    // VERIFICAÇÕES
    // ==========================================
    
    /**
     * Verifica se o cliente possui vendas associadas
     * 
     * CORREÇÃO B6: Método chamado pelo ClienteService.remover()
     * para impedir exclusão de clientes com vendas, mas não existia.
     * 
     * @param int $clienteId ID do cliente
     * @return bool true se tem vendas, false se não tem
     */
    public function hasVendas(int $clienteId): bool
    {
        $sql = "SELECT COUNT(*) FROM vendas WHERE cliente_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $clienteId]);
        
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica se um email já existe no banco
     * 
     * CORREÇÃO B6: Método chamado pelo ClienteService.validarEmailUnico()
     * mas não existia. Aceita $exceptId para ignorar o próprio registro
     * durante edição (evita falso positivo de duplicação).
     * 
     * @param string $email Email a verificar
     * @param int|null $exceptId ID a excluir da verificação (para update)
     * @return bool true se já existe, false se disponível
     */
    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        if ($exceptId) {
            $sql = "SELECT COUNT(*) FROM {$this->table} 
                    WHERE email = :email AND id != :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email, 'id' => $exceptId]);
        } else {
            $sql = "SELECT COUNT(*) FROM {$this->table} 
                    WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
        }
        
        return (int)$stmt->fetchColumn() > 0;
    }
}