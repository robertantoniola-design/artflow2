<?php
/**
 * DemoSeeder - Cria dados de demonstração
 */

use App\Core\Database;

return new class {
    public function run(): void
    {
        $db = Database::getInstance()->getConnection();
        
        echo "📦 Criando dados de demonstração...\n\n";
        
        // ============================
        // CLIENTES DE EXEMPLO
        // ============================
        echo "👥 Criando clientes...\n";
        
        $clientes = [
            [
                'nome' => 'Maria Silva',
                'email' => 'maria@exemplo.com',
                'telefone' => '11999999999',
                'empresa' => 'Galeria Arte Viva'
            ],
            [
                'nome' => 'João Santos',
                'email' => 'joao@exemplo.com',
                'telefone' => '11988888888',
                'empresa' => null
            ],
            [
                'nome' => 'Ana Oliveira',
                'email' => 'ana@exemplo.com',
                'telefone' => '11977777777',
                'empresa' => 'Decoração & Design'
            ],
        ];
        
        $stmtCliente = $db->prepare("
            INSERT INTO clientes (nome, email, telefone, empresa) 
            VALUES (:nome, :email, :telefone, :empresa)
        ");
        
        $clienteIds = [];
        foreach ($clientes as $cliente) {
            try {
                $stmtCliente->execute($cliente);
                $clienteIds[] = $db->lastInsertId();
                echo "  ✅ Cliente '{$cliente['nome']}' criado\n";
            } catch (\PDOException $e) {
                if ($e->getCode() != '23000') throw $e;
                echo "  ⏭️  Cliente '{$cliente['nome']}' já existe\n";
            }
        }
        
        // ============================
        // ARTES DE EXEMPLO
        // ============================
        echo "\n🎨 Criando artes...\n";
        
        $artes = [
            [
                'nome' => 'Pôr do Sol na Montanha',
                'descricao' => 'Paisagem impressionista com cores vibrantes do entardecer.',
                'tempo_medio_horas' => 12.0,
                'complexidade' => 'media',
                'preco_custo' => 150.00,
                'horas_trabalhadas' => 10.5,
                'status' => 'disponivel'
            ],
            [
                'nome' => 'Retrato em Aquarela',
                'descricao' => 'Técnica mista com aquarela e lápis de cor.',
                'tempo_medio_horas' => 8.0,
                'complexidade' => 'alta',
                'preco_custo' => 200.00,
                'horas_trabalhadas' => 6.0,
                'status' => 'em_producao'
            ],
            [
                'nome' => 'Abstrato Geométrico',
                'descricao' => 'Composição com formas geométricas em acrílica.',
                'tempo_medio_horas' => 6.0,
                'complexidade' => 'baixa',
                'preco_custo' => 80.00,
                'horas_trabalhadas' => 5.5,
                'status' => 'disponivel'
            ],
            [
                'nome' => 'Natureza Morta',
                'descricao' => 'Óleo sobre tela com frutas e flores.',
                'tempo_medio_horas' => 15.0,
                'complexidade' => 'alta',
                'preco_custo' => 250.00,
                'horas_trabalhadas' => 14.0,
                'status' => 'vendida'
            ],
        ];
        
        $stmtArte = $db->prepare("
            INSERT INTO artes (nome, descricao, tempo_medio_horas, complexidade, preco_custo, horas_trabalhadas, status)
            VALUES (:nome, :descricao, :tempo_medio_horas, :complexidade, :preco_custo, :horas_trabalhadas, :status)
        ");
        
        $arteIds = [];
        foreach ($artes as $arte) {
            try {
                $stmtArte->execute($arte);
                $arteIds[] = $db->lastInsertId();
                echo "  ✅ Arte '{$arte['nome']}' criada\n";
            } catch (\PDOException $e) {
                if ($e->getCode() != '23000') throw $e;
                echo "  ⏭️  Arte '{$arte['nome']}' já existe\n";
            }
        }
        
        // ============================
        // VINCULAR TAGS ÀS ARTES
        // ============================
        echo "\n🏷️  Vinculando tags às artes...\n";
        
        // Buscar IDs das tags
        $stmtTag = $db->query("SELECT id, nome FROM tags");
        $tags = [];
        while ($row = $stmtTag->fetch()) {
            $tags[$row['nome']] = $row['id'];
        }
        
        // Vincular (se temos IDs)
        if (!empty($arteIds) && !empty($tags)) {
            $stmtVinculo = $db->prepare("INSERT IGNORE INTO arte_tags (arte_id, tag_id) VALUES (?, ?)");
            
            // Arte 1 (Paisagem): Paisagem, Óleo
            if (isset($arteIds[0]) && isset($tags['Paisagem'])) {
                $stmtVinculo->execute([$arteIds[0], $tags['Paisagem']]);
            }
            
            // Arte 2 (Retrato): Aquarela, Retrato
            if (isset($arteIds[1]) && isset($tags['Aquarela'])) {
                $stmtVinculo->execute([$arteIds[1], $tags['Aquarela']]);
            }
            if (isset($arteIds[1]) && isset($tags['Retrato'])) {
                $stmtVinculo->execute([$arteIds[1], $tags['Retrato']]);
            }
            
            // Arte 3 (Abstrato): Abstrato, Acrílica
            if (isset($arteIds[2]) && isset($tags['Abstrato'])) {
                $stmtVinculo->execute([$arteIds[2], $tags['Abstrato']]);
            }
            if (isset($arteIds[2]) && isset($tags['Acrílica'])) {
                $stmtVinculo->execute([$arteIds[2], $tags['Acrílica']]);
            }
            
            echo "  ✅ Tags vinculadas\n";
        }
        
        // ============================
        // VENDA DE EXEMPLO
        // ============================
        echo "\n💰 Criando venda de exemplo...\n";
        
        if (!empty($arteIds) && !empty($clienteIds)) {
            $arteVendidaId = end($arteIds); // Última arte (vendida)
            $clienteId = $clienteIds[0];
            
            $stmtVenda = $db->prepare("
                INSERT INTO vendas (arte_id, cliente_id, valor, data_venda, lucro_calculado, rentabilidade_hora)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $valorVenda = 600.00;
            $custoArte = 250.00;
            $horasArte = 14.0;
            $lucro = $valorVenda - $custoArte;
            $rentabilidade = $lucro / $horasArte;
            
            try {
                $stmtVenda->execute([
                    $arteVendidaId,
                    $clienteId,
                    $valorVenda,
                    date('Y-m-d'),
                    $lucro,
                    $rentabilidade
                ]);
                echo "  ✅ Venda registrada: R$ " . number_format($valorVenda, 2, ',', '.') . "\n";
                
                // Atualiza meta do mês
                $mesAtual = date('Y-m-01');
                $db->exec("
                    UPDATE metas 
                    SET valor_realizado = valor_realizado + {$valorVenda},
                        porcentagem_atingida = ((valor_realizado + {$valorVenda}) / valor_meta) * 100
                    WHERE mes_ano = '{$mesAtual}'
                ");
                echo "  ✅ Meta do mês atualizada\n";
                
            } catch (\PDOException $e) {
                echo "  ⚠️  Erro ao criar venda: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n✅ Dados de demonstração criados com sucesso!\n\n";
    }
};
