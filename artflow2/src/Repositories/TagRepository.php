<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Core\Database;
use PDO;

/**
 * ============================================
 * TAG REPOSITORY (Melhoria 3 — + Descrição e Ícone)
 * ============================================
 * 
 * Gerencia acesso a dados de tags (categorias/etiquetas).
 * 
 * ALTERAÇÕES:
 * - Fase 1: searchWithCount(), getArtesByTag()
 * - Fase 2: allWithCountPaginated(), countAll()
 * - Melhoria 3: $fillable agora inclui 'descricao' e 'icone'
 *   (CRÍTICO: sem isso, BaseRepository::filterFillable() ignora esses campos)
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
     * 
     * MELHORIA 3: Adicionados 'descricao' e 'icone'
     * O BaseRepository::create() e update() usam filterFillable()
     * que DESCARTA qualquer campo não listado aqui.
     * Sem 'descricao' e 'icone' nesta lista, os dados seriam
     * silenciosamente ignorados nos INSERT/UPDATE.
     */
    protected array $fillable = [
        'nome',
        'cor',
        'descricao',   // MELHORIA 3: campo TEXT NULL
        'icone',        // Já existia na tabela, agora permitido no mass assignment
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
     * Busca tags por parte do nome (sem contagem de artes)
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
    
    // ==========================================
    // CONTAGEM DE ARTES (JOIN com arte_tags)
    // ==========================================
    
    /**
     * Lista todas as tags COM contagem de artes associadas
     * LEFT JOIN garante que tags sem artes apareçam (count=0)
     * 
     * @return array<Tag>
     */
    public function allWithCount(): array
    {
        $sql = "SELECT t.*, COUNT(at.arte_id) as artes_count 
                FROM {$this->table} t 
                LEFT JOIN arte_tags at ON t.id = at.tag_id 
                GROUP BY t.id 
                ORDER BY t.nome ASC";
        
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            $tag = $this->hydrate($row);
            $tag->setArtesCount((int)($row['artes_count'] ?? 0));
            return $tag;
        }, $rows);
    }
    
    /**
     * FASE 2: Lista tags paginadas com contagem, ordenação e busca
     * 
     * @param int $page Página atual (1-based)
     * @param int $perPage Itens por página
     * @param string $ordenar Campo: nome|data|contagem
     * @param string $direcao ASC|DESC
     * @param string $termo Busca opcional
     * @return array<Tag>
     */
    public function allWithCountPaginated(
        int $page,
        int $perPage,
        string $ordenar = 'nome',
        string $direcao = 'ASC',
        string $termo = ''
    ): array {
        // Whitelist de colunas — previne SQL injection no ORDER BY
        $colunasPermitidas = [
            'nome' => 't.nome',
            'data' => 't.created_at',
            'contagem' => 'total_artes',
        ];
        
        $colunaOrdem = $colunasPermitidas[$ordenar] ?? 't.nome';
        $direcao = strtoupper($direcao) === 'DESC' ? 'DESC' : 'ASC';
        $offset = ($page - 1) * $perPage;
        
        // Monta SQL base
        $sql = "SELECT t.*, COUNT(at.arte_id) as total_artes 
                FROM {$this->table} t 
                LEFT JOIN arte_tags at ON t.id = at.tag_id";
        
        $params = [];
        
        // WHERE condicional (busca por nome)
        if (!empty($termo)) {
            $sql .= " WHERE t.nome LIKE :termo";
            $params[':termo'] = "%{$termo}%";
        }
        
        // GROUP BY + ORDER BY dinâmico + LIMIT/OFFSET
        $sql .= " GROUP BY t.id 
                   ORDER BY {$colunaOrdem} {$direcao}
                   LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        // Bind de parâmetros
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hidrata com contagem
        return array_map(function($row) {
            $tag = $this->hydrate($row);
            $tag->setArtesCount((int)($row['total_artes'] ?? 0));
            return $tag;
        }, $rows);
    }
    
    /**
     * FASE 2: Conta total de tags (para cálculo de paginação)
     * 
     * @param string $termo Busca opcional
     * @return int
     */
    public function countAll(string $termo = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        
        if (!empty($termo)) {
            $sql .= " WHERE nome LIKE :termo";
            $params[':termo'] = "%{$termo}%";
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Retorna tags mais usadas (com pelo menos 1 arte)
     * INNER JOIN exclui tags sem associações
     * 
     * @param int $limite
     * @return array<Tag>
     */
    public function getMaisUsadas(int $limite = 10): array
    {
        $sql = "SELECT t.*, COUNT(at.arte_id) as artes_count 
                FROM {$this->table} t 
                INNER JOIN arte_tags at ON t.id = at.tag_id 
                GROUP BY t.id 
                ORDER BY artes_count DESC 
                LIMIT :limite";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            $tag = $this->hydrate($row);
            $tag->setArtesCount((int)($row['artes_count'] ?? 0));
            return $tag;
        }, $rows);
    }
    
    /**
     * Retorna contagem por tag para gráficos (Chart.js)
     * 
     * @return array [{nome, cor, quantidade}]
     */
    public function getContagemPorTag(): array
    {
        $sql = "SELECT t.nome, t.cor, COUNT(at.arte_id) as quantidade 
                FROM {$this->table} t 
                LEFT JOIN arte_tags at ON t.id = at.tag_id 
                GROUP BY t.id 
                ORDER BY quantidade DESC";
        
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==========================================
    // RELACIONAMENTO COM ARTES (Pivot arte_tags)
    // ==========================================
    
    /**
     * Retorna tags associadas a uma arte específica
     * 
     * @param int $arteId
     * @return array<Tag>
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
     * Retorna apenas IDs das tags de uma arte
     * 
     * @param int $arteId
     * @return array<int>
     */
    public function getIdsByArte(int $arteId): array
    {
        $sql = "SELECT tag_id FROM arte_tags WHERE arte_id = :arte_id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['arte_id' => $arteId]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Sincroniza tags de uma arte (delete + re-insert)
     * 
     * @param int $arteId
     * @param array $tagIds
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
        // Verifica se já existe (evita duplicata)
        $sql = "SELECT COUNT(*) FROM arte_tags WHERE arte_id = :arte_id AND tag_id = :tag_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['arte_id' => $arteId, 'tag_id' => $tagId]);
        
        if ($stmt->fetchColumn() > 0) {
            return true; // Já existe
        }
        
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
    
    // ==========================================
    // VERIFICAÇÕES
    // ==========================================
    
    /**
     * Verifica se nome já existe (para validação de unicidade)
     * 
     * @param string $nome
     * @param int|null $excludeId Exclui este ID da verificação (edição)
     * @return bool
     */
    public function nomeExists(string $nome, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE LOWER(nome) = LOWER(:nome)";
        $params = ['nome' => $nome];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Cria tag se não existir, retorna existente se já existir
     * 
     * @param string $nome
     * @param string $cor
     * @return Tag
     */
    public function findOrCreate(string $nome, string $cor = '#6c757d'): Tag
    {
        $existing = $this->findByNome($nome);
        
        if ($existing) {
            return $existing;
        }
        
        return $this->create([
            'nome' => $nome,
            'cor' => $cor,
        ]);
    }
    
    /**
     * Remove tag com todas as relações (transação atômica)
     * 
     * @param int $id
     * @return bool
     */
    public function deleteWithRelations(int $id): bool
    {
        $db = $this->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Remove relações na tabela pivot
            $stmt = $db->prepare("DELETE FROM arte_tags WHERE tag_id = :id");
            $stmt->execute(['id' => $id]);
            
            // Remove a tag
            $stmt = $db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            $db->commit();
            return true;
            
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    // ==========================================
    // FASE 1: BUSCA COM CONTAGEM + ARTES POR TAG
    // ==========================================
    
    /**
     * Busca tags por termo COM contagem de artes
     * 
     * @param string $termo
     * @param int $limite
     * @return array Associative arrays (não objetos Tag)
     */
    public function searchWithCount(string $termo, int $limite = 10): array
    {
        $sql = "SELECT t.*, COUNT(at.arte_id) as total_artes 
                FROM {$this->table} t 
                LEFT JOIN arte_tags at ON t.id = at.tag_id 
                WHERE t.nome LIKE :termo 
                GROUP BY t.id 
                ORDER BY t.nome ASC 
                LIMIT :limite";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':termo', '%' . $termo . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Retorna artes associadas a uma tag (via tabela pivot arte_tags)
     * 
     * IMPORTANTE: Retorna arrays associativos, NÃO objetos Arte.
     * Motivo: evitar dependência circular TagRepository→Arte Model.
     * A view show.php DEVE usar acesso por chave ($arte['nome']),
     * NÃO por método ($arte->getNome()).
     * 
     * @param int $tagId
     * @return array Array de arrays associativos
     */
    public function getArtesByTag(int $tagId): array
    {
        $sql = "SELECT a.* FROM artes a
                INNER JOIN arte_tags at ON a.id = at.arte_id
                WHERE at.tag_id = :tag_id
                ORDER BY a.nome ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
