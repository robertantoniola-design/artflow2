<?php

namespace App\Exceptions;

use Exception;

/**
 * ============================================
 * VALIDATION EXCEPTION
 * ============================================
 * 
 * Exceção lançada quando dados de entrada são inválidos.
 * Carrega array de erros para exibição ao usuário.
 * 
 * USO:
 * throw new ValidationException(['nome' => 'O nome é obrigatório']);
 * 
 * CAPTURA:
 * try {
 *     $validator->validate($data);
 * } catch (ValidationException $e) {
 *     $errors = $e->getErrors();
 * }
 */
class ValidationException extends Exception
{
    /**
     * Array de erros [campo => mensagem]
     */
    private array $errors;
    
    /**
     * Construtor
     * 
     * @param array $errors Array de erros [campo => mensagem]
     * @param string $message Mensagem geral (opcional)
     * @param int $code Código de erro
     */
    public function __construct(array $errors, string $message = 'Erro de validação', int $code = 422)
    {
        $this->errors = $errors;
        parent::__construct($message, $code);
    }
    
    /**
     * Obtém array de erros
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Obtém erro de um campo específico
     * 
     * @param string $field
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Verifica se tem erro em campo específico
     * 
     * @param string $field
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
    
    /**
     * Obtém primeiro erro
     * 
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        return reset($this->errors) ?: null;
    }
    
    /**
     * Converte para array (útil para JSON)
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors
        ];
    }
}
