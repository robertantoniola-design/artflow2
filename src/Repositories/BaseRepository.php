<?php

namespace App\Repositories;

use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Exceptions\DatabaseException;
use PDO;
use PDOException;

/**
 * ============================================
 * BASE REPOSITORY
 * ============================================
 * 
 * Classe base para todos os repositórios.
 * Implementa operações CRUD genéricas.
 * 
 * Os repositórios específicos herdam desta classe e
 * podem adicionar métodos próprios.
 * 
 * USO:
 * class ArteRepository extends BaseRepository {
 *     protected string $table = 'artes';
 *     protected string $model = Arte::class;
 * }
 */
abstract class BaseRepository
{
    /**
     * Nome da tabela no banco
     */
    protected string $table;
    
    /**
     * Classe do Model
     */
    protected string $model;
    
    /**
     * Conexão PDO
     */
    protected PDO $db;
    
    /**
     * Campos permitidos para mass assignment
     */
    protected array $fillable = [];
    
    /**
     * Construtor - recebe conexão via DI
     * 
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }
    
    // ==========================================
    // MÉTODOS DE LEITURA
    // ==========================================
    
    /**
     * Busca todos os registros
     * 
     * @param string $orderBy Coluna para ordenação
     * @param string $direction ASC ou DESC
     * @return array
     */
    public function all(string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}";
        $stmt = $this->db->query($sql);
        
        return $this->hydrateMany($stmt->fetchAll());
    }
    
    /**
     * Busca registro por ID
     * 
     * @param int $id
     * @return object|null
     */
    public function find(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $data = $stmt->fetch();
        
        return $data ? $this->hydrate($data) : null;
    }
    
    /**
     * Busca registro por ID ou lança exceção
     * 
     * @param int $id
     * @return object
     * @throws NotFoundException
     */
    public function findOrFail(int $id): object
    {
        $result = $this->find($id);
        
        if (!$result) {
            throw new NotFoundException($this->getModelName(), $id);
        }
        
        return $result;
    }
    
    /**
     * Busca registros por condição
     * 
     * @param string $column
     * @param mixed $value
     * @return array
     */
    public function findBy(string $column, $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        
        return $this->hydrateMany($stmt->fetchAll());
    }
    
    /**
     * Busca primeiro registro por condição
     * 
     * @param string $column
     * @param mixed $value
     * @return object|null
     */
    public function findFirstBy(string $column, $value): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        
        $data = $stmt->fetch();
        
        return $data ? $this->hydrate($data) : null;
    }
    
    /**
     * Busca com condições WHERE dinâmicas
     * 
     * @param array $conditions ['campo' => 'valor']
     * @param string $orderBy
     * @param string $direction
     * @return array
     */
    public function where(array $conditions, string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $where = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }
        
        $whereClause = implode(' AND ', $where);
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY {$orderBy} {$direction}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->hydrateMany($stmt->fetchAll());
    }
    
    /**
     * Conta total de registros
     * 
     * @param array $conditions Condições opcionais
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $column => $value) {
                $where[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetch()['total'];
    }
    
    /**
     * Verifica se registro existe
     * 
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch() !== false;
    }
    
    // ==========================================
    // MÉTODOS DE ESCRITA
    // ==========================================
    
    /**
     * Cria novo registro
     * 
     * @param array $data
     * @return object Registro criado
     * @throws DatabaseException
     */
    public function create(array $data): object
    {
        try {
            // Filtra apenas campos permitidos
            $data = $this->filterFillable($data);
            
            // Adiciona timestamps
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Monta SQL
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            
            // Retorna registro criado
            $id = (int) $this->db->lastInsertId();
            return $this->find($id);
            
        } catch (PDOException $e) {
            throw new DatabaseException("Erro ao criar registro", $e, $sql ?? '', $data);
        }
    }
    
    /**
     * Atualiza registro existente
     * 
     * @param int $id
     * @param array $data
     * @return object Registro atualizado
     * @throws NotFoundException
     * @throws DatabaseException
     */
    public function update(int $id, array $data): object
    {
        // Verifica se existe
        $this->findOrFail($id);
        
        try {
            // Filtra apenas campos permitidos
            $data = $this->filterFillable($data);
            
            // Atualiza timestamp
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Monta SET
            $set = [];
            foreach (array_keys($data) as $column) {
                $set[] = "{$column} = :{$column}";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = :id";
            
            $data['id'] = $id;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            
            // Retorna registro atualizado
            return $this->find($id);
            
        } catch (PDOException $e) {
            throw new DatabaseException("Erro ao atualizar registro", $e, $sql ?? '', $data);
        }
    }
    
    /**
     * Remove registro
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     * @throws DatabaseException
     */
    public function delete(int $id): bool
    {
        // Verifica se existe
        $this->findOrFail($id);
        
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            throw new DatabaseException("Erro ao deletar registro", $e);
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    /**
     * Converte array em objeto do Model
     * 
     * @param array $data
     * @return object
     */
    protected function hydrate(array $data): object
    {
        if (method_exists($this->model, 'fromArray')) {
            return $this->model::fromArray($data);
        }
        
        return (object) $data;
    }
    
    /**
     * Converte múltiplos arrays em objetos
     * 
     * @param array $items
     * @return array
     */
    protected function hydrateMany(array $items): array
    {
        return array_map(fn($item) => $this->hydrate($item), $items);
    }
    
    /**
     * Filtra apenas campos permitidos
     * 
     * @param array $data
     * @return array
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Obtém nome do Model para mensagens
     * 
     * @return string
     */
    protected function getModelName(): string
    {
        $parts = explode('\\', $this->model);
        return end($parts);
    }
    
    /**
     * Executa query customizada com SELECT
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Executa query customizada (INSERT, UPDATE, DELETE)
     * 
     * @param string $sql
     * @param array $params
     * @return int Linhas afetadas
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Obtém conexão PDO (para queries complexas)
     * 
     * @return PDO
     */
    protected function getConnection(): PDO
    {
        return $this->db;
    }
}
