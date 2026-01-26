<?php
/**
 * TagSeeder - Popula tags iniciais
 */

use App\Core\Database;

return new class {
    public function run(): void
    {
        $db = Database::getInstance()->getConnection();
        
        echo "🏷️  Populando tags...\n";
        
        $tags = [
            ['nome' => 'Aquarela', 'cor' => '#17a2b8'],
            ['nome' => 'Óleo', 'cor' => '#fd7e14'],
            ['nome' => 'Acrílica', 'cor' => '#28a745'],
            ['nome' => 'Digital', 'cor' => '#6f42c1'],
            ['nome' => 'Retrato', 'cor' => '#e83e8c'],
            ['nome' => 'Paisagem', 'cor' => '#20c997'],
            ['nome' => 'Abstrato', 'cor' => '#007bff'],
            ['nome' => 'Encomenda', 'cor' => '#dc3545'],
        ];
        
        $stmt = $db->prepare("INSERT INTO tags (nome, cor) VALUES (:nome, :cor)");
        
        foreach ($tags as $tag) {
            try {
                $stmt->execute($tag);
                echo "  ✅ Tag '{$tag['nome']}' criada\n";
            } catch (\PDOException $e) {
                // Tag já existe, ignora
                if ($e->getCode() != '23000') {
                    throw $e;
                }
                echo "  ⏭️  Tag '{$tag['nome']}' já existe\n";
            }
        }
        
        echo "✅ Tags populadas!\n\n";
    }
};
