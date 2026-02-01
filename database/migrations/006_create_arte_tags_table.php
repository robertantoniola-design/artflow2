<?php
/**
 * Migration: Criar tabela pivot arte_tags
 * 
 * Relacionamento N:N entre artes e tags
 * Uma arte pode ter várias tags
 * Uma tag pode estar em várias artes
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
        $this->createTable('arte_tags', function(Schema $table) {
            $table->unsignedInteger('arte_id');    // FK para artes
            $table->unsignedInteger('tag_id');     // FK para tags
            
            // Foreign Keys com CASCADE (se deletar arte/tag, remove relação)
            $table->foreign('arte_id', 'artes', 'id', 'CASCADE');
            $table->foreign('tag_id', 'tags', 'id', 'CASCADE');
            
            // Índices para performance
            $table->index('arte_id');
            $table->index('tag_id');
        });
        
        // Adiciona chave primária composta manualmente
        // (Schema não suporta PK composta diretamente)
        $this->execute("ALTER TABLE `arte_tags` ADD PRIMARY KEY (`arte_id`, `tag_id`)");
    }
    
    public function down(): void
    {
        $this->dropTable('arte_tags');
    }
};
