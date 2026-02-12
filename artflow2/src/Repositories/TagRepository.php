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


    // ==========================================
    // MELHORIA 4: MERGE DE TAGS
    // ==========================================

    /**
     * Mescla tag origem na tag destino (transação atômica)
     * 
     * Transfere todas as associações arte_tags da tag origem para a tag destino,
     * tratando duplicatas (arte que já tem ambas as tags), e depois deleta
     * a tag origem.
     * 
     * LÓGICA DE DUPLICATAS:
     * - Uma arte pode estar associada tanto à tag origem quanto à destino.
     * - Passo 1: Transfere apenas associações que NÃO causariam duplicata.
     * - Passo 2: Remove associações restantes da origem (eram duplicatas).
     * - Passo 3: Deleta a tag origem.
     * 
     * @param int $origemId  ID da tag a ser absorvida (será deletada)
     * @param int $destinoId ID da tag que receberá as associações
     * @return array ['transferidas' => int, 'duplicatas' => int]
     * @throws \Exception Em caso de erro (faz rollback)
     */
    public function mergeTags(int $origemId, int $destinoId): array
    {
        $db = $this->getConnection();
        
        try {
            $db->beginTransaction();
            
            // ── Passo 1: Contar quantas artes têm APENAS a tag origem ──
            // São as associações que serão transferidas (sem conflito)
            $sqlContarTransferiveis = "
                SELECT COUNT(*) FROM arte_tags 
                WHERE tag_id = :origem_id 
                AND arte_id NOT IN (
                    SELECT arte_id FROM arte_tags WHERE tag_id = :destino_id
                )
            ";
            $stmt = $db->prepare($sqlContarTransferiveis);
            $stmt->execute(['origem_id' => $origemId, 'destino_id' => $destinoId]);
            $transferiveis = (int) $stmt->fetchColumn();
            
            // ── Passo 2: Contar duplicatas (artes com ambas as tags) ──
            $sqlContarDuplicatas = "
                SELECT COUNT(*) FROM arte_tags a1
                INNER JOIN arte_tags a2 ON a1.arte_id = a2.arte_id
                WHERE a1.tag_id = :origem_id AND a2.tag_id = :destino_id
            ";
            $stmt = $db->prepare($sqlContarDuplicatas);
            $stmt->execute(['origem_id' => $origemId, 'destino_id' => $destinoId]);
            $duplicatas = (int) $stmt->fetchColumn();
            
            // ── Passo 3: Transferir associações sem duplicata ──
            // UPDATE arte_tags SET tag_id = destino WHERE tag_id = origem
            // APENAS para artes que NÃO têm a tag destino ainda
            $sqlTransferir = "
                UPDATE arte_tags 
                SET tag_id = :destino_id 
                WHERE tag_id = :origem_id 
                AND arte_id NOT IN (
                    SELECT arte_id FROM (
                        SELECT arte_id FROM arte_tags WHERE tag_id = :destino_id2
                    ) AS sub
                )
            ";
            $stmt = $db->prepare($sqlTransferir);
            $stmt->execute([
                'destino_id'  => $destinoId,
                'origem_id'   => $origemId,
                'destino_id2' => $destinoId,
            ]);
            
            // ── Passo 4: Remover associações duplicadas restantes ──
            // As artes que já tinham a tag destino ficam com a entrada duplicada
            // da tag origem que precisa ser removida
            $sqlRemoverDuplicatas = "
                DELETE FROM arte_tags WHERE tag_id = :origem_id
            ";
            $stmt = $db->prepare($sqlRemoverDuplicatas);
            $stmt->execute(['origem_id' => $origemId]);
            
            // ── Passo 5: Deletar a tag origem ──
            $sqlDeletarTag = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $db->prepare($sqlDeletarTag);
            $stmt->execute(['id' => $origemId]);
            
            $db->commit();
            
            return [
                'transferidas' => $transferiveis,
                'duplicatas'   => $duplicatas,
            ];
            
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

        // ==========================================
    // ESTATÍSTICAS POR TAG (Melhoria 5)
    // ==========================================

    /**
     * ============================================
     * MELHORIA 5: Retorna estatísticas completas de uma tag
     * ============================================
     * 
     * Executa 2 queries otimizadas para obter métricas das artes
     * associadas a uma tag e suas vendas correspondentes.
     * 
     * QUERY 1 — Estatísticas das ARTES:
     *   - total_artes: quantas artes têm esta tag
     *   - artes_vendidas: quantas com status = 'vendida'
     *   - artes_disponiveis: quantas com status = 'disponivel'
     *   - artes_producao: quantas com status = 'em_producao'
     *   - custo_medio: AVG(preco_custo) das artes
     *   - custo_total: SUM(preco_custo) das artes
     *   - horas_totais: SUM(horas_trabalhadas) das artes
     *   - complexidade_mais_comum: complexidade com maior contagem
     * 
     * QUERY 2 — Estatísticas das VENDAS (artes vendidas com esta tag):
     *   - total_vendas: quantas vendas existem
     *   - faturamento_total: SUM(vendas.valor)
     *   - lucro_total: SUM(vendas.lucro_calculado)
     *   - ticket_medio: AVG(vendas.valor)
     *   - lucro_medio: AVG(vendas.lucro_calculado)
     *   - rentabilidade_media: AVG(vendas.rentabilidade_hora)
     *   - primeira_venda: MIN(vendas.data_venda)
     *   - ultima_venda: MAX(vendas.data_venda)
     * 
     * POR QUE 2 QUERIES SEPARADAS?
     * Se fizéssemos um único JOIN de artes + vendas, artes com
     * múltiplas vendas seriam contadas múltiplas vezes no AVG/SUM
     * de artes, distorcendo os resultados. Separar garante precisão.
     * 
     * @param int $tagId ID da tag
     * @return array Array associativo com todas as métricas (valores default 0/null se sem dados)
     */
    public function getEstatisticasByTag(int $tagId): array
    {
        $conn = $this->getConnection();
        
        // ── QUERY 1: Estatísticas das Artes ──
        // LEFT JOIN garante que mesmo tags sem artes retornem resultado (zeros)
        // CASE WHEN conta artes por status sem precisar de queries separadas
        $sqlArtes = "
            SELECT 
                COUNT(a.id) AS total_artes,
                SUM(CASE WHEN a.status = 'vendida' THEN 1 ELSE 0 END) AS artes_vendidas,
                SUM(CASE WHEN a.status = 'disponivel' THEN 1 ELSE 0 END) AS artes_disponiveis,
                SUM(CASE WHEN a.status = 'em_producao' THEN 1 ELSE 0 END) AS artes_producao,
                COALESCE(AVG(a.preco_custo), 0) AS custo_medio,
                COALESCE(SUM(a.preco_custo), 0) AS custo_total,
                COALESCE(SUM(a.horas_trabalhadas), 0) AS horas_totais
            FROM arte_tags at
            INNER JOIN artes a ON a.id = at.arte_id
            WHERE at.tag_id = :tag_id
        ";
        
        $stmt = $conn->prepare($sqlArtes);
        $stmt->execute(['tag_id' => $tagId]);
        $estatArtes = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // ── Complexidade mais comum (query separada por causa do GROUP BY) ──
        // Só executa se a tag tem artes (evita query desnecessária)
        $complexidadeMaisComum = null;
        if ((int)$estatArtes['total_artes'] > 0) {
            $sqlComplexidade = "
                SELECT a.complexidade, COUNT(*) AS total
                FROM arte_tags at
                INNER JOIN artes a ON a.id = at.arte_id
                WHERE at.tag_id = :tag_id
                  AND a.complexidade IS NOT NULL
                GROUP BY a.complexidade
                ORDER BY total DESC
                LIMIT 1
            ";
            $stmt = $conn->prepare($sqlComplexidade);
            $stmt->execute(['tag_id' => $tagId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $complexidadeMaisComum = $row ? $row['complexidade'] : null;
        }
        
        // ── QUERY 2: Estatísticas das Vendas ──
        // JOIN triplo: arte_tags → artes → vendas
        // Só conta vendas de artes que TÊM esta tag
        $sqlVendas = "
            SELECT 
                COUNT(v.id) AS total_vendas,
                COALESCE(SUM(v.valor), 0) AS faturamento_total,
                COALESCE(SUM(v.lucro_calculado), 0) AS lucro_total,
                COALESCE(AVG(v.valor), 0) AS ticket_medio,
                COALESCE(AVG(v.lucro_calculado), 0) AS lucro_medio,
                COALESCE(AVG(v.rentabilidade_hora), 0) AS rentabilidade_media,
                MIN(v.data_venda) AS primeira_venda,
                MAX(v.data_venda) AS ultima_venda
            FROM arte_tags at
            INNER JOIN artes a ON a.id = at.arte_id
            INNER JOIN vendas v ON v.arte_id = a.id
            WHERE at.tag_id = :tag_id
        ";
        
        $stmt = $conn->prepare($sqlVendas);
        $stmt->execute(['tag_id' => $tagId]);
        $estatVendas = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // ── Monta array consolidado ──
        // Converte tipos para evitar problemas na view (string → int/float)
        return [
            // Contagens de artes
            'total_artes'         => (int) $estatArtes['total_artes'],
            'artes_vendidas'      => (int) $estatArtes['artes_vendidas'],
            'artes_disponiveis'   => (int) $estatArtes['artes_disponiveis'],
            'artes_producao'      => (int) $estatArtes['artes_producao'],
            
            // Valores financeiros das artes
            'custo_medio'         => round((float) $estatArtes['custo_medio'], 2),
            'custo_total'         => round((float) $estatArtes['custo_total'], 2),
            'horas_totais'        => round((float) $estatArtes['horas_totais'], 1),
            
            // Complexidade
            'complexidade_mais_comum' => $complexidadeMaisComum,
            
            // Vendas
            'total_vendas'        => (int) $estatVendas['total_vendas'],
            'faturamento_total'   => round((float) $estatVendas['faturamento_total'], 2),
            'lucro_total'         => round((float) $estatVendas['lucro_total'], 2),
            'ticket_medio'        => round((float) $estatVendas['ticket_medio'], 2),
            'lucro_medio'         => round((float) $estatVendas['lucro_medio'], 2),
            'rentabilidade_media' => round((float) $estatVendas['rentabilidade_media'], 2),
            'primeira_venda'      => $estatVendas['primeira_venda'],  // string DATE ou null
            'ultima_venda'        => $estatVendas['ultima_venda'],    // string DATE ou null
        ];
    }
}
