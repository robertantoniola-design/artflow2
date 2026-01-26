<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Core\Database;
use PDO;

/**
 * ============================================
 * TAG REPOSITORY
 * ============================================
 * 
 * Gerencia acesso a dados de tags (categorias/etiquetas).
 * Tags são usadas para organizar e filtrar artes.
 * 
 * Principais operações:
 * - CRUD padrão (herdado)
 * - Busca por nome/cor
 * - Gerenciamento de relacionamento N:N com artes
 * - Contagem de artes por tag
 */
class TagRepository extends BaseRepository
{
    /**
     * Tabela do banco de dados
     */
    protected string $table = 'tags';
    
    /**
     * Classe do Model
     */
    protected string $model = Tag::class;
    
    /**
     * Campos permitidos para mass assignment
     */
    protected array $fillable = [
        'nome',
        'cor'
    ];
    
    // ==========================================
    // BUSCAS ESPECÍFICAS
    // ==========================================
    
    /**
     * Busca tag por nome (case-insensitive)
     * 
     * @param string $nome
     * @return Tag|null
     */
    public function findByNome(string $nome): ?Tag
    {
        $sql = "SELECT * FROM {$this->table} WHERE LOWER(nome) = LOWER(:nome) LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['nome' => $nome]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }
    
    /**
     * Lista todas as tags ordenadas por nome
     * 
     * @return array
     */
    public function allOrdered(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY nome ASC";
        $stmt = $this->getConnection()->query($sql);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca tags por parte do nome
     * 
     * @param string $termo
     * @return array
     */
    public function search(string $termo): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE nome LIKE :termo 
                ORDER BY nome ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['termo' => "%{$termo}%"]);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca tags por cor
     * 
     * @param string $cor Código hexadecimal (#RRGGBB)
     * @return array
     */
    public function findByCor(string $cor): array
    {
        return $this->where('cor', $cor);
    }
    
    // ==========================================
    // RELACIONAMENTO COM ARTES (N:N)
    // ==========================================
    
    /**
     * Associa tags a uma arte
     * 
     * @param int $arteId
     * @param array $tagIds Array de IDs das tags
     * @return void
     */
    public function syncArte(int $arteId, array $tagIds): void
    {
        $db = $this->getConnection();
        
        // Remove associações antigas
        $stmt = $db->prepare("DELETE FROM arte_tags WHERE arte_id = :arte_id");
        $stmt->execute(['arte_id' => $arteId]);
        
        // Insere novas associações
        if (!empty($tagIds)) {
            $stmt = $db->prepare("INSERT INTO arte_tags (arte_id, tag_id) VALUES (:arte_id, :tag_id)");
            
            foreach ($tagIds as $tagId) {
                $stmt->execute([
                    'arte_id' => $arteId,
                    'tag_id' => (int) $tagId
                ]);
            }
        }
    }
    
    /**
     * Adiciona uma tag a uma arte
     * 
     * @param int $arteId
     * @param int $tagId
     * @return bool
     */
    public function attachArte(int $arteId, int $tagId): bool
    {
        // Verifica se já existe
        $sql = "SELECT COUNT(*) FROM arte_tags WHERE arte_id = :arte_id AND tag_id = :tag_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['arte_id' => $arteId, 'tag_id' => $tagId]);
        
        if ($stmt->fetchColumn() > 0) {
            return true; // Já existe, não faz nada
        }
        
        // Insere nova associação
        $sql = "INSERT INTO arte_tags (arte_id, tag_id) VALUES (:arte_id, :tag_id)";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute(['arte_id' => $arteId, 'tag_id' => $tagId]);
    }
    
    /**
     * Remove uma tag de uma arte
     * 
     * @param int $arteId
     * @param int $tagId
     * @return bool
     */
    public function detachArte(int $arteId, int $tagId): bool
    {
        $sql = "DELETE FROM arte_tags WHERE arte_id = :arte_id AND tag_id = :tag_id";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute(['arte_id' => $arteId, 'tag_id' => $tagId]);
    }
    
    /**
     * Retorna tags de uma arte específica
     * 
     * @param int $arteId
     * @return array
     */
    public function getByArte(int $arteId): array
    {
        $sql = "SELECT t.* FROM {$this->table} t
                INNER JOIN arte_tags at ON t.id = at.tag_id
                WHERE at.arte_id = :arte_id
                ORDER BY t.nome ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['arte_id' => $arteId]);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Retorna IDs das tags de uma arte
     * 
     * @param int $arteId
     * @return array
     */
    public function getIdsByArte(int $arteId): array
    {
        $sql = "SELECT tag_id FROM arte_tags WHERE arte_id = :arte_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['arte_id' => $arteId]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
    /**
     * Retorna tags com contagem de artes
     * 
     * @return array
     */
    public function allWithCount(): array
    {
        $sql = "SELECT t.*, COUNT(at.arte_id) as total_artes
                FROM {$this->table} t
                LEFT JOIN arte_tags at ON t.id = at.tag_id
                GROUP BY t.id
                ORDER BY t.nome ASC";
        
        $stmt = $this->getConnection()->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hidrata e adiciona contagem
        $tags = [];
        foreach ($results as $row) {
            $tag = $this->hydrate($row);
            $tag->total_artes = (int) $row['total_artes'];
            $tags[] = $tag;
        }
        
        return $tags;
    }
    
    /**
     * Retorna as tags mais usadas
     * 
     * @param int $limit
     * @return array
     */
    public function getMaisUsadas(int $limit = 10): array
    {
        $sql = "SELECT t.*, COUNT(at.arte_id) as total_artes
                FROM {$this->table} t
                INNER JOIN arte_tags at ON t.id = at.tag_id
                GROUP BY t.id
                ORDER BY total_artes DESC
                LIMIT :limit";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tags = [];
        foreach ($results as $row) {
            $tag = $this->hydrate($row);
            $tag->total_artes = (int) $row['total_artes'];
            $tags[] = $tag;
        }
        
        return $tags;
    }
    
    /**
     * Retorna contagem de artes por tag (para gráfico)
     * 
     * @return array
     */
    public function getContagemPorTag(): array
    {
        $sql = "SELECT t.nome, t.cor, COUNT(at.arte_id) as quantidade
                FROM {$this->table} t
                LEFT JOIN arte_tags at ON t.id = at.tag_id
                GROUP BY t.id
                ORDER BY quantidade DESC";
        
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==========================================
    // UTILITÁRIOS
    // ==========================================
    
    /**
     * Cria tag se não existir, ou retorna existente
     * 
     * @param string $nome
     * @param string $cor
     * @return Tag
     */
    public function findOrCreate(string $nome, string $cor = '#6c757d'): Tag
    {
        $tag = $this->findByNome($nome);
        
        if ($tag) {
            return $tag;
        }
        
        return $this->create([
            'nome' => $nome,
            'cor' => $cor
        ]);
    }
    
    /**
     * Verifica se nome já existe (excluindo ID específico)
     * 
     * @param string $nome
     * @param int|null $exceptId ID para ignorar (usado em edição)
     * @return bool
     */
    public function nomeExists(string $nome, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE LOWER(nome) = LOWER(:nome)";
        $params = ['nome' => $nome];
        
        if ($exceptId) {
            $sql .= " AND id != :id";
            $params['id'] = $exceptId;
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Remove tag e todas suas associações
     * 
     * @param int $id
     * @return bool
     */
    public function deleteWithRelations(int $id): bool
    {
        $db = $this->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Remove associações
            $stmt = $db->prepare("DELETE FROM arte_tags WHERE tag_id = :id");
            $stmt->execute(['id' => $id]);
            
            // Remove tag
            $result = $this->delete($id);
            
            $db->commit();
            return $result;
            
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
