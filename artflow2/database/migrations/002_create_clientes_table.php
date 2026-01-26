<?php
/**
 * Migration: Criar tabela de clientes
 * 
 * Armazena informações dos clientes que compram as artes
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
        $this->createTable('clientes', function(Schema $table) {
            $table->id();
            $table->string('nome', 150);                      // Nome completo
            $table->string('email', 150)->nullable();         // E-mail (único se preenchido)
            $table->string('telefone', 20)->nullable();       // Telefone/WhatsApp
            $table->string('empresa', 100)->nullable();       // Empresa (se aplicável)
            $table->text('endereco')->nullable();             // Endereço completo
            $table->string('cidade', 100)->nullable();        // Cidade
            $table->string('estado', 2)->nullable();          // UF (2 caracteres)
            $table->text('observacoes')->nullable();          // Observações gerais
            $table->timestamps();
            
            // Índices
            $table->index('nome');
            $table->index('email');
            $table->index('cidade');
        });
    }
    
    public function down(): void
    {
        $this->dropTable('clientes');
    }
};
