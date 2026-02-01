<?php

namespace App\Core;

use PDO;

/**
 * ============================================
 * MIGRATION - Classe Base para Migrations
 * ============================================
 * 
 * Migrations permitem versionar alterações no banco de dados.
 * 
 * Cada migration deve implementar:
 * - up(): Aplica a migration
 * - down(): Reverte a migration
 */
abstract class Migration
{
    /**
     * Conexão PDO
     */
    protected PDO $db;
    
    /**
     * Construtor
     * 
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }
    
    /**
     * Executa a migration (criar tabelas, etc)
     */
    abstract public function up(): void;
    
    /**
     * Reverte a migration (desfazer alterações)
     */
    abstract public function down(): void;
    
    /**
     * Cria uma tabela usando Schema builder
     * 
     * @param string $tableName
     * @param callable $callback
     */
    protected function createTable(string $tableName, callable $callback): void
    {
        $schema = new Schema($tableName);
        $callback($schema);
        
        $sql = $schema->toSql();
        $this->db->exec($sql);
        
        echo "  ✅ Tabela '{$tableName}' criada\n";
    }
    
    /**
     * Remove uma tabela
     * 
     * @param string $tableName
     */
    protected function dropTable(string $tableName): void
    {
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        $this->db->exec($sql);
        
        echo "  ✅ Tabela '{$tableName}' removida\n";
    }
    
    /**
     * Adiciona coluna a tabela existente
     * 
     * @param string $tableName
     * @param string $column
     * @param string $definition
     */
    protected function addColumn(string $tableName, string $column, string $definition): void
    {
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$column}` {$definition}";
        $this->db->exec($sql);
        
        echo "  ✅ Coluna '{$column}' adicionada em '{$tableName}'\n";
    }
    
    /**
     * Remove coluna de tabela
     * 
     * @param string $tableName
     * @param string $column
     */
    protected function dropColumn(string $tableName, string $column): void
    {
        $sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$column}`";
        $this->db->exec($sql);
        
        echo "  ✅ Coluna '{$column}' removida de '{$tableName}'\n";
    }
    
    /**
     * Adiciona índice
     * 
     * @param string $tableName
     * @param string $indexName
     * @param string|array $columns
     */
    protected function addIndex(string $tableName, string $indexName, $columns): void
    {
        if (is_array($columns)) {
            $columns = '`' . implode('`, `', $columns) . '`';
        } else {
            $columns = "`{$columns}`";
        }
        
        $sql = "ALTER TABLE `{$tableName}` ADD INDEX `{$indexName}` ({$columns})";
        $this->db->exec($sql);
    }
    
    /**
     * Remove índice
     * 
     * @param string $tableName
     * @param string $indexName
     */
    protected function dropIndex(string $tableName, string $indexName): void
    {
        $sql = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`";
        $this->db->exec($sql);
    }
    
    /**
     * Executa SQL raw
     * 
     * @param string $sql
     */
    protected function execute(string $sql): void
    {
        $this->db->exec($sql);
    }
}

/**
 * ============================================
 * SCHEMA - Builder para Estrutura de Tabelas
 * ============================================
 * 
 * Permite construir tabelas de forma fluente:
 * 
 * $table->id();
 * $table->string('nome', 100);
 * $table->decimal('valor', 10, 2);
 * $table->timestamps();
 */
class Schema
{
    private string $tableName;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private string $engine = 'InnoDB';
    private string $charset = 'utf8mb4';
    private string $collation = 'utf8mb4_unicode_ci';
    
    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }
    
    /**
     * Coluna ID auto increment
     */
    public function id(string $name = 'id'): self
    {
        $this->columns[] = "`{$name}` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }
    
    /**
     * Coluna VARCHAR
     */
    public function string(string $name, int $length = 255): self
    {
        $this->columns[] = "`{$name}` VARCHAR({$length})";
        return $this;
    }
    
    /**
     * Coluna TEXT
     */
    public function text(string $name): self
    {
        $this->columns[] = "`{$name}` TEXT";
        return $this;
    }
    
    /**
     * Coluna LONGTEXT
     */
    public function longText(string $name): self
    {
        $this->columns[] = "`{$name}` LONGTEXT";
        return $this;
    }
    
    /**
     * Coluna INTEGER
     */
    public function integer(string $name): self
    {
        $this->columns[] = "`{$name}` INT";
        return $this;
    }
    
    /**
     * Coluna INTEGER UNSIGNED
     */
    public function unsignedInteger(string $name): self
    {
        $this->columns[] = "`{$name}` INT UNSIGNED";
        return $this;
    }
    
    /**
     * Coluna BIGINT
     */
    public function bigInteger(string $name): self
    {
        $this->columns[] = "`{$name}` BIGINT";
        return $this;
    }
    
    /**
     * Coluna TINYINT
     */
    public function tinyInteger(string $name): self
    {
        $this->columns[] = "`{$name}` TINYINT";
        return $this;
    }
    
    /**
     * Coluna BOOLEAN (TINYINT(1))
     */
    public function boolean(string $name): self
    {
        $this->columns[] = "`{$name}` TINYINT(1)";
        return $this;
    }
    
    /**
     * Coluna DECIMAL
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2): self
    {
        $this->columns[] = "`{$name}` DECIMAL({$precision},{$scale})";
        return $this;
    }
    
    /**
     * Coluna FLOAT
     */
    public function float(string $name): self
    {
        $this->columns[] = "`{$name}` FLOAT";
        return $this;
    }
    
    /**
     * Coluna DATE
     */
    public function date(string $name): self
    {
        $this->columns[] = "`{$name}` DATE";
        return $this;
    }
    
    /**
     * Coluna DATETIME
     */
    public function datetime(string $name): self
    {
        $this->columns[] = "`{$name}` DATETIME";
        return $this;
    }
    
    /**
     * Coluna TIMESTAMP
     */
    public function timestamp(string $name): self
    {
        $this->columns[] = "`{$name}` TIMESTAMP";
        return $this;
    }
    
    /**
     * Coluna TIME
     */
    public function time(string $name): self
    {
        $this->columns[] = "`{$name}` TIME";
        return $this;
    }
    
    /**
     * Coluna ENUM
     */
    public function enum(string $name, array $values): self
    {
        $vals = "'" . implode("','", $values) . "'";
        $this->columns[] = "`{$name}` ENUM({$vals})";
        return $this;
    }
    
    /**
     * Coluna JSON
     */
    public function json(string $name): self
    {
        $this->columns[] = "`{$name}` JSON";
        return $this;
    }
    
    /**
     * Coluna BLOB
     */
    public function blob(string $name): self
    {
        $this->columns[] = "`{$name}` BLOB";
        return $this;
    }
    
    // ==========================================
    // MODIFICADORES
    // ==========================================
    
    /**
     * Permite NULL
     */
    public function nullable(): self
    {
        $lastIndex = count($this->columns) - 1;
        $this->columns[$lastIndex] .= " NULL";
        return $this;
    }
    
    /**
     * Define valor padrão
     */
    public function default($value): self
    {
        $lastIndex = count($this->columns) - 1;
        
        if (is_string($value)) {
            $value = "'{$value}'";
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif ($value === null) {
            $value = 'NULL';
        }
        
        $this->columns[$lastIndex] .= " DEFAULT {$value}";
        return $this;
    }
    
    /**
     * Adiciona UNIQUE
     */
    public function unique(): self
    {
        $lastIndex = count($this->columns) - 1;
        $this->columns[$lastIndex] .= " UNIQUE";
        return $this;
    }
    
    /**
     * Adiciona NOT NULL
     */
    public function notNull(): self
    {
        $lastIndex = count($this->columns) - 1;
        $this->columns[$lastIndex] .= " NOT NULL";
        return $this;
    }
    
    /**
     * Adiciona UNSIGNED
     */
    public function unsigned(): self
    {
        $lastIndex = count($this->columns) - 1;
        $this->columns[$lastIndex] = str_replace('INT', 'INT UNSIGNED', $this->columns[$lastIndex]);
        return $this;
    }
    
    /**
     * Adiciona comentário
     */
    public function comment(string $comment): self
    {
        $lastIndex = count($this->columns) - 1;
        $this->columns[$lastIndex] .= " COMMENT '{$comment}'";
        return $this;
    }
    
    // ==========================================
    // TIMESTAMPS E ATALHOS
    // ==========================================
    
    /**
     * Adiciona created_at e updated_at
     */
    public function timestamps(): self
    {
        $this->columns[] = "`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }
    
    /**
     * Adiciona soft delete (deleted_at)
     */
    public function softDeletes(): self
    {
        $this->columns[] = "`deleted_at` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }
    
    // ==========================================
    // ÍNDICES E FOREIGN KEYS
    // ==========================================
    
    /**
     * Adiciona índice
     */
    public function index(string $columnName, ?string $indexName = null): self
    {
        $indexName = $indexName ?? "idx_{$this->tableName}_{$columnName}";
        $this->indexes[] = "INDEX `{$indexName}` (`{$columnName}`)";
        return $this;
    }
    
    /**
     * Adiciona índice único
     */
    public function uniqueIndex(string $columnName, ?string $indexName = null): self
    {
        $indexName = $indexName ?? "uq_{$this->tableName}_{$columnName}";
        $this->indexes[] = "UNIQUE INDEX `{$indexName}` (`{$columnName}`)";
        return $this;
    }
    
    /**
     * Adiciona foreign key
     */
    public function foreign(string $column, string $referencedTable, string $referencedColumn = 'id', string $onDelete = 'CASCADE'): self
    {
        $constraintName = "fk_{$this->tableName}_{$column}";
        $this->foreignKeys[] = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$column}`) REFERENCES `{$referencedTable}`(`{$referencedColumn}`) ON DELETE {$onDelete}";
        return $this;
    }
    
    // ==========================================
    // GERAÇÃO DE SQL
    // ==========================================
    
    /**
     * Gera SQL CREATE TABLE
     */
    public function toSql(): string
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (\n  ";
        
        // Adiciona colunas
        $sql .= implode(",\n  ", $this->columns);
        
        // Adiciona índices
        if (!empty($this->indexes)) {
            $sql .= ",\n  " . implode(",\n  ", $this->indexes);
        }
        
        // Adiciona foreign keys
        if (!empty($this->foreignKeys)) {
            $sql .= ",\n  " . implode(",\n  ", $this->foreignKeys);
        }
        
        $sql .= "\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation};";
        
        return $sql;
    }
}
