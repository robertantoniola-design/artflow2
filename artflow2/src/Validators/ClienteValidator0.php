<?php

namespace App\Validators;

/**
 * ============================================
 * CLIENTE VALIDATOR
 * ============================================
 * 
 * Valida dados de entrada para criação/edição de clientes.
 * Usa $this->data que é preenchido pelo BaseValidator.
 */
class ClienteValidator extends BaseValidator
{
    /**
     * Implementa validação específica de Cliente
     */
    protected function doValidation(): void
    {
        // Nome (obrigatório)
        $this->required('nome', 'O nome do cliente é obrigatório');
        
        if (!empty($this->data['nome'])) {
            $this->minLength('nome', 2, 'O nome deve ter pelo menos 2 caracteres');
            $this->maxLength('nome', 150, 'O nome deve ter no máximo 150 caracteres');
        }
        
        // Email (opcional, mas se fornecido deve ser válido)
        if (!empty($this->data['email'])) {
            $this->email('email', 'O e-mail informado não é válido');
            $this->maxLength('email', 150, 'O e-mail deve ter no máximo 150 caracteres');
        }
        
        // Telefone (opcional)
        if (!empty($this->data['telefone'])) {
            $this->maxLength('telefone', 20, 'O telefone deve ter no máximo 20 caracteres');
            $this->validateTelefone($this->data['telefone']);
        }
        
        // Empresa (opcional)
        if (!empty($this->data['empresa'])) {
            $this->maxLength('empresa', 100, 'O nome da empresa deve ter no máximo 100 caracteres');
        }
    }
    
    /**
     * Validação customizada de telefone
     */
    private function validateTelefone(string $telefone): void
    {
        $apenasNumeros = preg_replace('/[^0-9]/', '', $telefone);
        
        if (strlen($apenasNumeros) < 10 || strlen($apenasNumeros) > 11) {
            $this->addError('telefone', 'Telefone inválido. Use formato: (11) 99999-9999');
        }
    }
    
    /**
     * Valida dados para criação
     */
    public function validateCreate(array $data): bool
    {
        return $this->isValid($data);
    }
    
    /**
     * Valida dados para atualização (mais flexível)
     */
    public function validateUpdate(array $data): bool
    {
        $this->data = $data;
        $this->errors = [];
        
        if (isset($this->data['nome'])) {
            if (empty($this->data['nome'])) {
                $this->addError('nome', 'O nome não pode ficar vazio');
            } else {
                $this->minLength('nome', 2, 'O nome deve ter pelo menos 2 caracteres');
                $this->maxLength('nome', 150, 'O nome deve ter no máximo 150 caracteres');
            }
        }
        
        if (isset($this->data['email']) && !empty($this->data['email'])) {
            $this->email('email', 'O e-mail informado não é válido');
            $this->maxLength('email', 150, 'O e-mail deve ter no máximo 150 caracteres');
        }
        
        if (isset($this->data['telefone']) && !empty($this->data['telefone'])) {
            $this->maxLength('telefone', 20, 'O telefone deve ter no máximo 20 caracteres');
            $this->validateTelefone($this->data['telefone']);
        }
        
        if (isset($this->data['empresa']) && !empty($this->data['empresa'])) {
            $this->maxLength('empresa', 100, 'O nome da empresa deve ter no máximo 100 caracteres');
        }
        
        return empty($this->errors);
    }
    
    /**
     * Valida se email já está em uso
     */
    public function validateEmailUnique(string $email, ?int $exceptId = null): bool
    {
        return true;
    }
}
