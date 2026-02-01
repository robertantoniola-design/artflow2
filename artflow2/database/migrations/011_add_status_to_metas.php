<?php
/**
 * Migration 011: Adicionar campo STATUS à tabela metas
 * 
 * OBJETIVO:
 * Controlar o ciclo de vida da meta com status explícito:
 * - 'iniciado'     → Meta recém-criada, sem vendas registradas no mês
 * - 'em_progresso' → Após o primeiro registro de venda no mês
 * - 'finalizado'   → Quando o mês já passou (encerrado automaticamente)
 * 
 * EXECUÇÃO:
 * Acessar: http://localhost/artflow2/install.php
 * Ou executar manualmente no phpMyAdmin/MySQL
 * 
 * IMPACTO:
 * - NÃO quebra funcionalidades existentes (coluna tem DEFAULT)
 * - Metas antigas são atualizadas automaticamente no bloco de migração
 */

use App\Core\Migration;
use App\Core\Database;

return new class extends Migration
{
    public function __construct()
    {
        parent::__construct(Database::getInstance());
    }
    
    public function up(): void
    {
        echo "🔄 Adicionando coluna 'status' à tabela 'metas'...\n";
        
        // ============================================
        // 1. VERIFICAR SE COLUNA JÁ EXISTE (idempotente)
        // ============================================
        $stmt = $this->db->query("SHOW COLUMNS FROM metas LIKE 'status'");
        if ($stmt->rowCount() > 0) {
            echo "  ⏭️  Coluna 'status' já existe em 'metas'. Pulando.\n";
            return;
        }
        
        // ============================================
        // 2. ADICIONAR COLUNA STATUS
        // ============================================
        // ENUM com 3 estados possíveis, default 'iniciado'
        // Posicionada após 'porcentagem_atingida' para manter organização
        $this->db->exec("
            ALTER TABLE metas 
            ADD COLUMN status ENUM('iniciado', 'em_progresso', 'finalizado') 
            NOT NULL DEFAULT 'iniciado' 
            AFTER porcentagem_atingida
        ");
        echo "  ✅ Coluna 'status' adicionada com sucesso\n";
        
        // ============================================
        // 3. ATUALIZAR METAS EXISTENTES
        // ============================================
        // Metas de meses passados → 'finalizado'
        // Mês atual com vendas (valor_realizado > 0) → 'em_progresso'
        // Mês atual sem vendas → 'iniciado' (já é o default)
        // Meses futuros → 'iniciado' (já é o default)
        
        $mesAtual = date('Y-m-01');
        
        // 3a. Meses passados → finalizado
        $stmt = $this->db->prepare("
            UPDATE metas 
            SET status = 'finalizado' 
            WHERE mes_ano < :mes_atual
        ");
        $stmt->execute(['mes_atual' => $mesAtual]);
        $afetados = $stmt->rowCount();
        echo "  ✅ {$afetados} meta(s) de meses passados → 'finalizado'\n";
        
        // 3b. Mês atual com vendas → em_progresso
        $stmt = $this->db->prepare("
            UPDATE metas 
            SET status = 'em_progresso' 
            WHERE mes_ano = :mes_atual 
            AND valor_realizado > 0
        ");
        $stmt->execute(['mes_atual' => $mesAtual]);
        $afetados = $stmt->rowCount();
        echo "  ✅ {$afetados} meta(s) do mês atual com vendas → 'em_progresso'\n";
        
        // 3c. Índice para consultas de status (otimização)
        $this->db->exec("
            ALTER TABLE metas 
            ADD INDEX idx_metas_status (status)
        ");
        echo "  ✅ Índice 'idx_metas_status' criado\n";
        
        echo "✅ Migration 011 concluída!\n\n";
    }
    
    public function down(): void
    {
        echo "🔄 Revertendo migration 011...\n";
        
        // Remove índice primeiro
        try {
            $this->db->exec("ALTER TABLE metas DROP INDEX idx_metas_status");
            echo "  ✅ Índice 'idx_metas_status' removido\n";
        } catch (\Exception $e) {
            echo "  ⚠️  Índice não encontrado (ok)\n";
        }
        
        // Remove coluna
        try {
            $this->db->exec("ALTER TABLE metas DROP COLUMN status");
            echo "  ✅ Coluna 'status' removida\n";
        } catch (\Exception $e) {
            echo "  ⚠️  Coluna não encontrada (ok)\n";
        }
        
        echo "✅ Migration 011 revertida!\n\n";
    }
};
