<?php
/**
 * MetaSeeder - Cria meta do mês atual
 */

use App\Core\Database;

return new class {
    public function run(): void
    {
        $db = Database::getInstance()->getConnection();
        
        echo "🎯 Criando meta do mês atual...\n";
        
        $mesAtual = date('Y-m-01');
        
        // Verifica se já existe
        $stmt = $db->prepare("SELECT id FROM metas WHERE mes_ano = ?");
        $stmt->execute([$mesAtual]);
        
        if ($stmt->fetch()) {
            echo "  ⏭️  Meta de " . date('m/Y') . " já existe\n";
            return;
        }
        
        // Cria meta padrão
        $stmt = $db->prepare("
            INSERT INTO metas (mes_ano, valor_meta, horas_diarias_ideal, dias_trabalho_semana, valor_realizado, porcentagem_atingida)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $mesAtual,
            5000.00,  // R$ 5.000 de meta
            8,        // 8 horas por dia
            5,        // 5 dias por semana
            0.00,     // Valor realizado inicial
            0.00      // Porcentagem inicial
        ]);
        
        echo "  ✅ Meta de " . date('m/Y') . " criada: R$ 5.000,00\n";
        echo "✅ Metas populadas!\n\n";
    }
};
