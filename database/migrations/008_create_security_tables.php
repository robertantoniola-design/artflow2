<?php
/**
 * Migration: Criar tabelas de segurança e log
 * 
 * - csrf_tokens: Proteção contra CSRF
 * - activity_log: Histórico de ações
 * - configuracoes: Configurações do sistema
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
        // TABELA: TOKENS CSRF
        // ========================================
        $this->createTable('csrf_tokens', function(Schema $table) {
            $table->id();
            $table->string('token', 64);           // Token único
            $table->string('session_id', 128);     // ID da sessão
            $table->datetime('expires_at');        // Expiração
            $table->boolean('used')->default(false); // Se já foi usado
            $table->timestamps();
            
            $table->index('token');
            $table->index('session_id');
            $table->index('expires_at');
        });
        
        // ========================================
        // TABELA: LOG DE ATIVIDADES
        // ========================================
        $this->createTable('activity_log', function(Schema $table) {
            $table->id();
            $table->string('acao', 50);             // create, update, delete, view
            $table->string('tabela', 50);           // Nome da tabela afetada
            $table->unsignedInteger('registro_id')->nullable(); // ID do registro
            $table->text('dados_antigos')->nullable();  // JSON com dados antes
            $table->text('dados_novos')->nullable();    // JSON com dados depois
            $table->string('ip_address', 45)->nullable(); // IP do usuário
            $table->string('user_agent', 255)->nullable(); // Navegador
            $table->timestamps();
            
            $table->index('acao');
            $table->index('tabela');
            $table->index('created_at');
        });
        
        // ========================================
        // TABELA: CONFIGURAÇÕES DO SISTEMA
        // ========================================
        $this->createTable('configuracoes', function(Schema $table) {
            $table->id();
            $table->string('chave', 100);           // Nome da configuração
            $table->text('valor')->nullable();      // Valor (pode ser JSON)
            $table->string('tipo', 20)->default('string'); // string, int, bool, json
            $table->string('descricao', 255)->nullable();  // Descrição
            $table->timestamps();
            
            $table->uniqueIndex('chave');
        });
        
        // ========================================
        // INSERIR CONFIGURAÇÕES PADRÃO
        // ========================================
        $this->execute("
            INSERT INTO configuracoes (chave, valor, tipo, descricao, created_at, updated_at) VALUES
            ('app_nome', 'ArtFlow', 'string', 'Nome da aplicação', NOW(), NOW()),
            ('moeda_simbolo', 'R$', 'string', 'Símbolo da moeda', NOW(), NOW()),
            ('moeda_decimal', ',', 'string', 'Separador decimal', NOW(), NOW()),
            ('moeda_milhar', '.', 'string', 'Separador de milhar', NOW(), NOW()),
            ('dark_mode', 'false', 'bool', 'Modo escuro ativado', NOW(), NOW()),
            ('itens_por_pagina', '10', 'int', 'Itens por página na listagem', NOW(), NOW())
        ");
    }
    
    public function down(): void
    {
        $this->dropTable('configuracoes');
        $this->dropTable('activity_log');
        $this->dropTable('csrf_tokens');
    }
};
