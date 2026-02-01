<?php
/**
 * Migration: Criar tabela de artes
 * 
 * Tabela principal para armazenar as artes/trabalhos artísticos
 * Campos principais: nome, descrição, tempo, complexidade, custo, status
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
    
    /**
     * Executa a migration - cria a tabela
     */
    public function up(): void
    {
        $this->createTable('artes', function(Schema $table) {
            $table->id();                                           // ID auto increment
            $table->string('nome', 150);                            // Nome da arte
            $table->text('descricao')->nullable();                  // Descrição detalhada
            $table->decimal('tempo_medio_horas', 6, 2)->nullable(); // Tempo estimado
            $table->enum('complexidade', ['baixa', 'media', 'alta']) // Nível de dificuldade
                  ->default('media');
            $table->decimal('preco_custo', 10, 2)->default(0);      // Custo de produção
            $table->decimal('horas_trabalhadas', 8, 2)->default(0); // Horas já investidas
            $table->enum('status', ['disponivel', 'em_producao', 'vendida', 'reservada'])
                  ->default('disponivel');                          // Status atual
            $table->string('imagem', 255)->nullable();              // Caminho da imagem
            $table->timestamps();                                    // created_at, updated_at
            
            // Índices para buscas frequentes
            $table->index('nome');
            $table->index('status');
            $table->index('complexidade');
        });
    }
    
    /**
     * Reverte a migration - remove a tabela
     */
    public function down(): void
    {
        $this->dropTable('artes');
    }
};
