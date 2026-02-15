<?php

namespace App\Validators;

/**
 * ============================================
 * ARTE VALIDATOR
 * ============================================
 * 
 * Valida dados de entrada para criação/edição de artes.
 * Usa $this->data que é preenchido pelo BaseValidator.
 */
class ArteValidator extends BaseValidator
{
    /**
     * Status válidos para arte
     */
    private array $statusValidos = ['disponivel', 'em_producao', 'vendida'];
    
    /**
     * Níveis de complexidade válidos
     */
    private array $complexidadeValidas = ['baixa', 'media', 'alta'];
    
    /**
     * Implementa validação específica de Arte
     * Usa $this->data (preenchido pelo BaseValidator)
     */
    protected function doValidation(): void
    {
        // Nome (obrigatório)
        $this->required('nome', 'O nome da arte é obrigatório');
        
        if (!empty($this->data['nome'])) {
            $this->minLength('nome', 3, 'O nome deve ter pelo menos 3 caracteres');
            $this->maxLength('nome', 100, 'O nome deve ter no máximo 100 caracteres');
        }
        
        // Descrição (opcional)
        if (!empty($this->data['descricao'])) {
            $this->maxLength('descricao', 1000, 'A descrição deve ter no máximo 1000 caracteres');
        }
        
        // Tempo médio (obrigatório)
        $this->required('tempo_medio_horas', 'O tempo médio de produção é obrigatório');
        
        if (isset($this->data['tempo_medio_horas'])) {
            $this->numeric('tempo_medio_horas', 'O tempo médio deve ser um número');
            $this->positive('tempo_medio_horas', 'O tempo médio deve ser maior que zero');
        }
        
        // Complexidade (obrigatória)
        $this->required('complexidade', 'A complexidade é obrigatória');
        
        if (!empty($this->data['complexidade'])) {
            $this->in('complexidade', $this->complexidadeValidas, 
                'Complexidade inválida. Use: baixa, media ou alta');
        }
        
        // Preço de custo (opcional, mas não negativo)
        if (isset($this->data['preco_custo']) && $this->data['preco_custo'] !== '') {
            $this->numeric('preco_custo', 'O preço de custo deve ser um número');
            $this->notNegative('preco_custo', 'O preço de custo não pode ser negativo');
        }
        
        // Horas trabalhadas (opcional, mas não negativo)
        if (isset($this->data['horas_trabalhadas']) && $this->data['horas_trabalhadas'] !== '') {
            $this->numeric('horas_trabalhadas', 'As horas trabalhadas devem ser um número');
            $this->notNegative('horas_trabalhadas', 'As horas trabalhadas não podem ser negativas');
        }
        
        // Status (obrigatório se fornecido)
        if (!empty($this->data['status'])) {
            $this->in('status', $this->statusValidos, 
                'Status inválido. Use: disponivel, em_producao ou vendida');
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
        
        // Nome (se fornecido)
        if (isset($this->data['nome'])) {
            if (empty($this->data['nome'])) {
                $this->addError('nome', 'O nome não pode ficar vazio');
            } else {
                $this->minLength('nome', 3, 'O nome deve ter pelo menos 3 caracteres');
                $this->maxLength('nome', 100, 'O nome deve ter no máximo 100 caracteres');
            }
        }
        
        // Descrição (se fornecida)
        if (isset($this->data['descricao']) && !empty($this->data['descricao'])) {
            $this->maxLength('descricao', 1000, 'A descrição deve ter no máximo 1000 caracteres');
        }
        
        // Tempo médio (se fornecido)
        if (isset($this->data['tempo_medio_horas'])) {
            $this->numeric('tempo_medio_horas', 'O tempo médio deve ser um número');
            $this->positive('tempo_medio_horas', 'O tempo médio deve ser maior que zero');
        }
        
        // Complexidade (se fornecida)
        if (isset($this->data['complexidade']) && !empty($this->data['complexidade'])) {
            $this->in('complexidade', $this->complexidadeValidas, 
                'Complexidade inválida. Use: baixa, media ou alta');
        }
        
        // Preço de custo (se fornecido)
        if (isset($this->data['preco_custo']) && $this->data['preco_custo'] !== '') {
            $this->numeric('preco_custo', 'O preço de custo deve ser um número');
            $this->notNegative('preco_custo', 'O preço de custo não pode ser negativo');
        }
        
        // Horas trabalhadas (se fornecido)
        if (isset($this->data['horas_trabalhadas']) && $this->data['horas_trabalhadas'] !== '') {
            $this->numeric('horas_trabalhadas', 'As horas trabalhadas devem ser um número');
            $this->notNegative('horas_trabalhadas', 'As horas trabalhadas não podem ser negativas');
        }
        
        // Status (se fornecido)
        if (isset($this->data['status']) && !empty($this->data['status'])) {
            $this->in('status', $this->statusValidos, 
                'Status inválido. Use: disponivel, em_producao ou vendida');
        }
        
        return empty($this->errors);
    }
}
