<?php
/**
 * Migration: Criar tabela de tags
 * 
 * Etiquetas para categorizar artes (ex: Aquarela, Óleo, Retrato)
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
        $this->createTable('tags', function(Schema $table) {
            $table->id();
            $table->string('nome', 50);                    // Nome da tag
            $table->string('cor', 7)->default('#6c757d');  // Cor hexadecimal (#RRGGBB)
            $table->string('icone', 50)->nullable();       // Classe do ícone (Font Awesome)
            $table->timestamps();
            
            // Nome único
            $table->uniqueIndex('nome');
        });
    }
    
    public function down(): void
    {
        $this->dropTable('tags');
    }
};
