<?php
/**
 * Migration: Criar tabela de metas
 * 
 * Metas mensais de vendas com acompanhamento de progresso
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
        $this->createTable('metas', function(Schema $table) {
            $table->id();
            $table->date('mes_ano');                                  // Primeiro dia do mês (2025-01-01)
            $table->decimal('valor_meta', 10, 2);                     // Valor alvo em R$
            $table->integer('horas_diarias_ideal')->default(8);       // Meta de horas/dia
            $table->integer('dias_trabalho_semana')->default(5);      // Dias úteis/semana
            $table->decimal('valor_realizado', 10, 2)->default(0);    // Soma das vendas
            $table->decimal('porcentagem_atingida', 5, 2)->default(0);// % atingido
            $table->text('observacoes')->nullable();                  // Anotações
            $table->timestamps();
            
            // Índice único para evitar duplicidade de mês
            $table->uniqueIndex('mes_ano');
        });
    }
    
    public function down(): void
    {
        $this->dropTable('metas');
    }
};
