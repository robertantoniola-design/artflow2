<?php

namespace App\Exceptions;

use Exception;

/**
 * ============================================
 * DATABASE EXCEPTION
 * ============================================
 * 
 * Exceção lançada em erros relacionados ao banco de dados.
 * 
 * USO:
 * try {
 *     $db->query($sql);
 * } catch (PDOException $e) {
 *     throw new DatabaseException('Erro ao executar query', $e);
 * }
 */
class DatabaseException extends Exception
{
    /**
     * Query SQL que causou o erro
     */
    private ?string $query;
    
    /**
     * Parâmetros da query
     */
    private array $params;
    
    /**
     * Construtor
     * 
     * @param string $message Mensagem de erro
     * @param \Throwable|null $previous Exceção anterior (PDOException)
     * @param string|null $query Query SQL
     * @param array $params Parâmetros da query
     */
    public function __construct(
        string $message = 'Erro de banco de dados',
        ?\Throwable $previous = null,
        ?string $query = null,
        array $params = []
    ) {
        $this->query = $query;
        $this->params = $params;
        
        parent::__construct($message, 500, $previous);
    }
    
    /**
     * Obtém query SQL
     * 
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }
    
    /**
     * Obtém parâmetros
     * 
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     * Obtém informações para debug
     * 
     * @return array
     */
    public function getDebugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'query' => $this->query,
            'params' => $this->params,
            'previous' => $this->getPrevious()?->getMessage()
        ];
    }
}
