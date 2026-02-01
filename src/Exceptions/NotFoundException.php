<?php

namespace App\Exceptions;

use Exception;

/**
 * ============================================
 * NOT FOUND EXCEPTION
 * ============================================
 * 
 * Exceção lançada quando um recurso não é encontrado.
 * Resulta em resposta HTTP 404.
 * 
 * USO:
 * $arte = $repository->find($id);
 * if (!$arte) {
 *     throw new NotFoundException('Arte', $id);
 * }
 */
class NotFoundException extends Exception
{
    /**
     * Tipo de recurso
     */
    private string $resourceType;
    
    /**
     * ID do recurso
     */
    private $resourceId;
    
    /**
     * Construtor
     * 
     * @param string $resourceType Tipo de recurso (ex: 'Arte', 'Cliente')
     * @param mixed $resourceId ID do recurso (opcional)
     */
    public function __construct(string $resourceType, $resourceId = null)
    {
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        
        $message = $resourceId 
            ? "{$resourceType} com ID {$resourceId} não encontrado(a)"
            : "{$resourceType} não encontrado(a)";
        
        parent::__construct($message, 404);
    }
    
    /**
     * Obtém tipo de recurso
     * 
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
    
    /**
     * Obtém ID do recurso
     * 
     * @return mixed
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }
}
