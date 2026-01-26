<?php
/**
 * Migration: Criar tabela timer_sessoes
 * 
 * Registra sessões de trabalho no cronômetro
 * Permite rastrear tempo gasto em cada arte
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
        $this->createTable('timer_sessoes', function(Schema $table) {
            $table->id();
            $table->unsignedInteger('arte_id');                 // FK para artes
            $table->datetime('inicio');                          // Início da sessão
            $table->datetime('fim')->nullable();                 // Fim (null se rodando)
            $table->integer('duracao_segundos')->default(0);     // Duração total em segundos
            $table->enum('status', ['rodando', 'pausado', 'finalizado'])
                  ->default('rodando');                          // Estado atual
            $table->text('pausas')->nullable();                  // JSON com histórico de pausas
            $table->integer('tempo_pausado_segundos')->default(0); // Total pausado
            $table->text('observacoes')->nullable();             // Notas da sessão
            $table->timestamps();
            
            // Foreign Key
            $table->foreign('arte_id', 'artes', 'id', 'CASCADE');
            
            // Índices
            $table->index('arte_id');
            $table->index('status');
            $table->index('inicio');
        });
    }
    
    public function down(): void
    {
        $this->dropTable('timer_sessoes');
    }
};
