<?php

namespace App\Validators;

/**
 * ============================================
 * TAG VALIDATOR
 * ============================================
 * 
 * Valida dados de entrada para criação/edição de tags.
 * Usa $this->data que é preenchido pelo BaseValidator.
 */
class TagValidator extends BaseValidator
{
    /**
     * Implementa validação específica de Tag
     */
    protected function doValidation(): void
    {
        // Nome (obrigatório)
        $this->required('nome', 'O nome da tag é obrigatório');
        
        if (!empty($this->data['nome'])) {
            $this->minLength('nome', 2, 'O nome deve ter pelo menos 2 caracteres');
            $this->maxLength('nome', 50, 'O nome deve ter no máximo 50 caracteres');
            
            if (!preg_match('/^[\p{L}\p{N}\s\-]+$/u', $this->data['nome'])) {
                $this->addError('nome', 'O nome deve conter apenas letras, números, espaços e hífens');
            }
        }
        
        // Cor (opcional, mas se fornecida deve ser válida)
        if (!empty($this->data['cor'])) {
            $this->validateCorHex($this->data['cor']);
        }
    }
    
    /**
     * Valida formato de cor hexadecimal
     */
    private function validateCorHex(string $cor): void
    {
        if (strpos($cor, '#') !== 0) {
            $cor = '#' . $cor;
        }
        
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $cor)) {
            $this->addError('cor', 'Cor inválida. Use formato hexadecimal (#RRGGBB ou #RGB)');
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
     * Valida dados para atualização
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
                $this->maxLength('nome', 50, 'O nome deve ter no máximo 50 caracteres');
                
                if (!preg_match('/^[\p{L}\p{N}\s\-]+$/u', $this->data['nome'])) {
                    $this->addError('nome', 'O nome deve conter apenas letras, números, espaços e hífens');
                }
            }
        }
        
        if (isset($this->data['cor']) && !empty($this->data['cor'])) {
            $this->validateCorHex($this->data['cor']);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Normaliza cor para formato padrão (#RRGGBB)
     */
    public static function normalizeCor(string $cor): string
    {
        $cor = ltrim($cor, '#');
        
        if (strlen($cor) === 3) {
            $cor = $cor[0] . $cor[0] . $cor[1] . $cor[1] . $cor[2] . $cor[2];
        }
        
        return '#' . strtolower($cor);
    }
    
    /**
     * Lista de cores predefinidas
     */
    public static function getCoresPredefinidas(): array
    {
        return [
            '#dc3545' => 'Vermelho',
            '#fd7e14' => 'Laranja',
            '#ffc107' => 'Amarelo',
            '#28a745' => 'Verde',
            '#17a2b8' => 'Ciano',
            '#007bff' => 'Azul',
            '#6f42c1' => 'Roxo',
            '#e83e8c' => 'Rosa',
            '#6c757d' => 'Cinza',
            '#343a40' => 'Preto',
            '#20c997' => 'Teal',
            '#6610f2' => 'Índigo'
        ];
    }
}
