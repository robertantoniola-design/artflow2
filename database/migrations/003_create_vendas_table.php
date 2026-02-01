<?php
/**
 * Migration: Criar tabela de vendas
 * 
 * Registra todas as vendas de artes
 * Contém cálculos automáticos de lucro e rentabilidade
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
        $this->createTable('vendas', function(Schema $table) {
            $table->id();
            $table->unsignedInteger('arte_id')->nullable();       // FK para artes (SET NULL se deletar arte)
            $table->unsignedInteger('cliente_id')->nullable();    // FK para clientes (SET NULL se deletar cliente)
            $table->decimal('valor', 10, 2);                      // Valor da venda
            $table->date('data_venda');                           // Data da venda
            $table->decimal('lucro_calculado', 10, 2)->nullable();      // Valor - Custo
            $table->decimal('rentabilidade_hora', 10, 2)->nullable();   // Lucro / Horas
            $table->enum('forma_pagamento', ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'transferencia', 'outro'])
                  ->default('pix');
            $table->text('observacoes')->nullable();              // Observações da venda
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('arte_id', 'artes', 'id', 'SET NULL');
            $table->foreign('cliente_id', 'clientes', 'id', 'SET NULL');
            
            // Índices para relatórios
            $table->index('data_venda');
            $table->index('arte_id');
            $table->index('cliente_id');
        });
    }
    
    public function down(): void
    {
        $this->dropTable('vendas');
    }
};
