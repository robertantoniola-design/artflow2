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
 * - Paginação e ordenação dinâmica
 * 
 * CORREÇÕES APLICADAS:
 * - [07/02/2026] Adicionado searchWithCount() — busca por termo COM contagem de artes
 * - [07/02/2026] Adicionado getArtesByTag() — retorna artes associadas a uma tag
 * 
 * MELHORIAS APLICADAS:
 * - [07/02/2026] Melhoria 1: allWithCountPaginated() — paginação com LIMIT/OFFSET
 * - [07/02/2026] Melhoria 2: Ordenação dinâmica com whitelist de colunas
 * - [07/02/2026] countAll() — contagem total para cálculo de páginas
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
     * Busca tags por parte do nome (sem contagem de artes)
     * Usado internamente e no endpoint AJAX de autocomplete
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
     * ============================================
     * FASE 1: Busca tags por termo COM contagem de artes
     * ============================================
     * 
     * Usado pela listagem (index) quando há filtro de busca.
     * Diferente de search(), este método retorna tags com
     * a contagem de artes associadas (total_artes), igual
     * ao allWithCount() mas filtrado por termo.
     * 
     * @param string $termo Parte do nome da tag
     * @param int $limite Máximo de resultados
     * @return array Array de Tag objects com artesCount setado
     */
    public function searchWithCount(string $termo, int $limite = 50): array
    {
        $sql = "SELECT t.*, COUNT(at.arte_id) as total_artes
                FROM {$this->table} t
                LEFT JOIN arte_tags at ON t.id = at.tag_id
                WHERE t.nome LIKE :termo
                GROUP BY t.id
                ORDER BY t.nome ASC
                LIMIT :limite";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':termo', "%{$termo}%", PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hidrata e adiciona contagem (mesmo padrão de allWithCount)
        $tags = [];
        foreach ($results as $row) {
            $tag = $this->hydrate($row);
            $tag->setArtesCount((int) $row['total_artes']);
            $tags[] = $tag;
        }
        
        return $tags;
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
     * Associa tags a uma arte (sync: remove antigas + insere novas)
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
        // Verifica se já existe (evita duplicata)
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
     * @return array Array de Tag objects
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
     * @return array Array de inteiros (IDs)
     */
    public function getIdsByArte(int $arteId): array
    {
        $sql = "SELECT tag_id FROM arte_tags WHERE arte_id = :arte_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['arte_id' => $arteId]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * ============================================
     * FASE 1: Retorna artes associadas a uma tag
     * ============================================
     * 
     * Usado pela página show (detalhes da tag) para exibir
     * todas as artes que possuem esta tag.
     * Retorna arrays associativos (não objetos Arte) porque
     * o TagRepository não conhece o model Arte.
     * 
     * @param int $tagId ID da tag
     * @return array Array de arrays associativos com dados das artes
     */
    public function getArtesByTag(int $tagId): array
    {
        $sql = "SELECT a.* 
                FROM artes a
                INNER JOIN arte_tags at ON a.id = at.arte_id
                WHERE at.tag_id = :tag_id
                ORDER BY a.nome ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['tag_id' => $tagId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
    /**
     * Retorna tags com contagem de artes
     * 
     * @return array Array de Tag objects com artesCount setado
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
            $tag->setArtesCount((int) $row['total_artes']);
            $tags[] = $tag;
        }
        
        return $tags;
    }
    
    /**
     * Retorna as tags mais usadas
     * 
     * @param int $limit
     * @return array Array de Tag objects com artesCount setado
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
            $tag->setArtesCount((int) $row['total_artes']);
            $tags[] = $tag;
        }
        
        return $tags;
    }
    
    /**
     * Retorna contagem de artes por tag (para gráfico)
     * 
     * @return array Array associativo ['nome', 'cor', 'quantidade']
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
    // PAGINAÇÃO E ORDENAÇÃO (Melhoria 1+2)
    // ==========================================
    
    /**
     * ============================================
     * MELHORIA 1+2: Lista tags paginadas com contagem e ordenação
     * ============================================
     * 
     * Método principal que substitui allWithCount() na listagem.
     * Combina paginação (LIMIT/OFFSET) com ordenação dinâmica
     * e busca opcional por termo.
     * 
     * SEGURANÇA: Usa whitelist de colunas para ORDER BY,
     * impedindo SQL Injection via parâmetro de ordenação.
     * 
     * RETORNO: Array de objetos Tag (não arrays!) com artesCount setado,
     * mantendo compatibilidade com a view que usa $tag->getId(), etc.
     * 
     * @param int $page Página atual (1-based)
     * @param int $perPage Registros por página (default 12 = 3 linhas de 4 cards)
     * @param string $ordenar Coluna: 'nome'|'data'|'contagem' (default 'nome')
     * @param string $direcao Direção: 'ASC'|'DESC' (default 'ASC')
     * @param string|null $termo Filtro de busca LIKE (opcional)
     * @return array Array de objetos Tag com artesCount
     */
    public function allWithCountPaginated(
        int $page = 1,
        int $perPage = 12,
        string $ordenar = 'nome',
        string $direcao = 'ASC',
        ?string $termo = null
    ): array {
        // ── WHITELIST DE COLUNAS (previne SQL Injection) ──
        // Mapeia nomes amigáveis para colunas reais do SQL
        $colunasPermitidas = [
            'nome'     => 't.nome',        // Ordenar alfabeticamente
            'data'     => 't.created_at',   // Ordenar por data de criação
            'contagem' => 'total_artes'     // Ordenar por quantidade de artes
        ];

        // Se coluna não está na whitelist, usa 'nome' como fallback seguro
        $colunaOrdem = $colunasPermitidas[$ordenar] ?? 't.nome';

        // Sanitiza direção: apenas ASC ou DESC são válidos
        $direcao = strtoupper($direcao) === 'DESC' ? 'DESC' : 'ASC';

        // Calcula OFFSET baseado na página (página 1 = offset 0)
        $offset = ($page - 1) * $perPage;

        // ── CONSTRUÇÃO DINÂMICA DO WHERE ──
        $where = '';
        $params = [];

        if ($termo !== null && trim($termo) !== '') {
            $where = 'WHERE t.nome LIKE :termo';
            $params[':termo'] = '%' . trim($termo) . '%';
        }

        // ── QUERY PRINCIPAL ──
        // LEFT JOIN garante que tags sem artes apareçam (count=0)
        // GROUP BY necessário por causa do COUNT agregado
        $sql = "SELECT t.*, COUNT(at.arte_id) as total_artes
                FROM {$this->table} t
                LEFT JOIN arte_tags at ON t.id = at.tag_id
                {$where}
                GROUP BY t.id
                ORDER BY {$colunaOrdem} {$direcao}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);

        // Bind dos parâmetros de busca (se houver)
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        // Bind de LIMIT e OFFSET como inteiros (obrigatório para MySQL)
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── HIDRATAÇÃO: Converte arrays em objetos Tag ──
        // Mesmo padrão do allWithCount() existente
        $tags = [];
        foreach ($results as $row) {
            $tag = $this->hydrate($row);
            $tag->setArtesCount((int) $row['total_artes']);
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * ============================================
     * MELHORIA 1: Conta total de tags (com filtro opcional)
     * ============================================
     * 
     * Necessário para calcular o total de páginas na paginação.
     * Aceita o mesmo filtro de busca da listagem paginada para
     * que a contagem reflita os resultados filtrados.
     * 
     * Exemplo: Se existem 50 tags mas o filtro "Aqua" retorna 2,
     * a paginação deve mostrar 1 página, não 5.
     * 
     * @param string|null $termo Filtro de busca (mesmo do allWithCountPaginated)
     * @return int Total de registros
     */
    public function countAll(?string $termo = null): int
    {
        $where = '';
        $params = [];

        if ($termo !== null && trim($termo) !== '') {
            $where = 'WHERE nome LIKE :termo';
            $params[':termo'] = '%' . trim($termo) . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
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
     * Remove tag e todas suas associações (transação segura)
     * 
     * @param int $id
     * @return bool
     */
    public function deleteWithRelations(int $id): bool
    {
        $db = $this->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Remove associações na tabela pivot
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