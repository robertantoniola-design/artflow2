<?php
/**
 * Migration 013: Criar tabela meta_status_log
 * 
 * MELHORIA 6: Histórico de Transições de Status
 * 
 * Registra TODAS as mudanças de status das metas para auditoria.
 * Cada vez que uma meta muda de status (ex: iniciado → em_progresso),
 * um registro é criado com o snapshot do momento (porcentagem e valor).
 * 
 * Campos:
 * - meta_id: FK para metas (CASCADE ao deletar)
 * - status_anterior: NULL na criação inicial, string nos demais
 * - status_novo: Status para o qual transitou
 * - porcentagem_momento: % atingida no instante da transição
 * - valor_realizado_momento: R$ realizado no instante da transição
 * - observacao: Texto opcional (ex: "Transição automática por venda")
 * - created_at: Timestamp da transição
 * 
 * DEPENDÊNCIA: Migration 012 (status superado) deve estar executada.
 * EXECUTAR: Via phpMyAdmin ou php database/migrate.php
 */

use App\Core\Migration;
use App\Core\Schema;
use App\Core\Database;

return new class extends Migration
{
    public function __construct()
    {
        parent::__construct(Database::getInstance());
    }
    
    public function up(): void
    {
        // ========================================
        // TABELA: LOG DE TRANSIÇÕES DE STATUS
        // ========================================
        $this->createTable('meta_status_log', function(Schema $table) {
            $table->id();
            
            // FK para a meta — CASCADE: ao deletar meta, remove logs
            $table->unsignedInteger('meta_id');
            
            // Status anterior (NULL = criação inicial da meta)
            $table->string('status_anterior', 20)->nullable();
            
            // Status novo (obrigatório)
            $table->string('status_novo', 20);
            
            // Snapshot do momento da transição
            $table->decimal('porcentagem_momento', 10, 2)->nullable();
            $table->decimal('valor_realizado_momento', 10, 2)->nullable();
            
            // Observação opcional
            $table->text('observacao')->nullable();
            
            // Apenas created_at (logs não são editados)
            $table->timestamp('created_at');
            
            // Foreign Key
            $table->foreign('meta_id', 'metas', 'id', 'CASCADE');
            
            // Índices para consultas rápidas
            $table->index('meta_id');       // Buscar logs de uma meta
            $table->index('created_at');    // Ordenar cronologicamente
        });
    }
    
    public function down(): void
    {
        $this->dropTable('meta_status_log');
    }
};
