<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * ============================================
 * DATABASE - Gerenciador de Conexão (Singleton)
 * ============================================
 * 
 * Padrão Singleton garante UMA ÚNICA conexão com o banco
 * durante toda a execução da aplicação.
 * 
 * BENEFÍCIOS:
 * - Evita múltiplas conexões desnecessárias
 * - Centraliza configuração de conexão
 * - Facilita transações
 * 
 * USO:
 * $db = Database::getInstance();
 * $pdo = $db->getConnection();
 */
class Database
{
    /**
     * Instância única (Singleton)
     */
    private static ?Database $instance = null;
    
    /**
     * Conexão PDO
     */
    private PDO $connection;
    
    /**
     * Construtor privado (Singleton)
     * Configura a conexão com o banco de dados
     */
    private function __construct()
    {
        try {
            // Obtém configurações do .env
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $dbname = $_ENV['DB_DATABASE'] ?? 'artflow2_db';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            
            // Monta DSN (Data Source Name)
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            
            // Opções de configuração do PDO
            $options = [
                // Lança exceções em caso de erro (IMPORTANTE!)
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // Retorna arrays associativos por padrão
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // Desabilita prepared statements emulados (mais seguro)
                PDO::ATTR_EMULATE_PREPARES => false,
                
                // Define charset UTF-8
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            // Cria conexão
            $this->connection = new PDO($dsn, $username, $password, $options);
            
        } catch (PDOException $e) {
            // Em produção, logar o erro ao invés de exibir
            $debug = $_ENV['APP_DEBUG'] ?? false;
            
            if ($debug) {
                die("❌ Erro de conexão com banco de dados: " . $e->getMessage());
            } else {
                die("❌ Erro ao conectar com o banco de dados. Contate o administrador.");
            }
        }
    }
    
    /**
     * Obtém instância única do Database (Singleton)
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        // Cria instância apenas se ainda não existir
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Obtém conexão PDO
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    /**
     * Inicia uma transação
     * 
     * TRANSAÇÕES permitem executar múltiplas operações
     * que só são confirmadas se TODAS tiverem sucesso.
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirma transação (commit)
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    /**
     * Reverte transação (rollback)
     * 
     * Usado quando algo dá errado para desfazer alterações.
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
    
    /**
     * Verifica se está em uma transação
     * 
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }
    
    /**
     * Obtém último ID inserido
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Executa query direta (USE COM CUIDADO!)
     * 
     * @param string $sql
     * @return \PDOStatement
     */
    public function query(string $sql): \PDOStatement
    {
        return $this->connection->query($sql);
    }
    
    /**
     * Prepara statement para execução segura
     * 
     * @param string $sql
     * @return \PDOStatement
     */
    public function prepare(string $sql): \PDOStatement
    {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Previne clonagem (Singleton)
     */
    private function __clone() {}
    
    /**
     * Previne deserialização (Singleton)
     */
    public function __wakeup()
    {
        throw new \Exception("Não é possível deserializar singleton Database");
    }
}
