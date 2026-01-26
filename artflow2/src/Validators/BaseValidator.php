<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * ============================================
 * BASE VALIDATOR
 * ============================================
 * 
 * Classe base para validação de dados.
 * Fornece métodos genéricos de validação e
 * gerenciamento de erros.
 * 
 * Validators específicos herdam desta classe:
 * class ArteValidator extends BaseValidator { ... }
 */
abstract class BaseValidator
{
    /**
     * Erros de validação encontrados
     */
    protected array $errors = [];
    
    /**
     * Dados sendo validados
     */
    protected array $data = [];
    
    /**
     * Regras de validação (definidas nas subclasses)
     */
    protected array $rules = [];
    
    /**
     * Mensagens customizadas (definidas nas subclasses)
     */
    protected array $messages = [];
    
    // ==========================================
    // MÉTODO PRINCIPAL
    // ==========================================
    
    /**
     * Valida dados e lança exceção se inválidos
     * 
     * @param array $data
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        $this->data = $data;
        $this->errors = [];
        
        // Executa validação específica (implementada na subclasse)
        $this->doValidation();
        
        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }
    }
    
    /**
     * Valida e retorna booleano (não lança exceção)
     * 
     * @param array $data
     * @return bool
     */
    public function isValid(array $data): bool
    {
        $this->data = $data;
        $this->errors = [];
        
        $this->doValidation();
        
        return empty($this->errors);
    }
    
    /**
     * Método abstrato - implementar nas subclasses
     */
    abstract protected function doValidation(): void;
    
    // ==========================================
    // MÉTODOS DE VALIDAÇÃO
    // ==========================================
    
    /**
     * Valida campo obrigatório
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function required(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value === null || $value === '' || $value === []) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} é obrigatório");
        }
    }
    
    /**
     * Valida tamanho mínimo
     * 
     * @param string $field
     * @param int $min
     * @param string|null $message
     */
    protected function minLength(string $field, int $min, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && mb_strlen($value) < $min) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ter pelo menos {$min} caracteres");
        }
    }
    
    /**
     * Valida tamanho máximo
     * 
     * @param string $field
     * @param int $max
     * @param string|null $message
     */
    protected function maxLength(string $field, int $max, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && mb_strlen($value) > $max) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ter no máximo {$max} caracteres");
        }
    }
    
    /**
     * Valida valor mínimo numérico
     * 
     * @param string $field
     * @param float $min
     * @param string|null $message
     */
    protected function minValue(string $field, float $min, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && is_numeric($value) && $value < $min) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser no mínimo {$min}");
        }
    }
    
    /**
     * Valida valor máximo numérico
     * 
     * @param string $field
     * @param float $max
     * @param string|null $message
     */
    protected function maxValue(string $field, float $max, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && is_numeric($value) && $value > $max) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser no máximo {$max}");
        }
    }
    
    /**
     * Valida se é numérico
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function numeric(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser numérico");
        }
    }
    
    /**
     * Valida se é inteiro
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function integer(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser um número inteiro");
        }
    }
    
    /**
     * Valida email
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function email(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser um email válido");
        }
    }
    
    /**
     * Valida se valor está em lista de permitidos
     * 
     * @param string $field
     * @param array $allowed
     * @param string|null $message
     */
    protected function in(string $field, array $allowed, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser um dos valores: {$allowedStr}");
        }
    }
    
    /**
     * Valida se é data válida
     * 
     * @param string $field
     * @param string $format
     * @param string|null $message
     */
    protected function date(string $field, string $format = 'Y-m-d', ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $date = \DateTime::createFromFormat($format, $value);
            
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser uma data válida");
            }
        }
    }
    
    /**
     * Valida expressão regular
     * 
     * @param string $field
     * @param string $pattern
     * @param string|null $message
     */
    protected function regex(string $field, string $pattern, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} está em formato inválido");
        }
    }
    
    /**
     * Valida telefone brasileiro
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function phone(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            // Remove formatação
            $phone = preg_replace('/[^0-9]/', '', $value);
            
            // Valida: 10 dígitos (fixo) ou 11 dígitos (celular)
            if (strlen($phone) < 10 || strlen($phone) > 11) {
                $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser um telefone válido");
            }
        }
    }
    
    /**
     * Valida se é URL válida
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function url(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser uma URL válida");
        }
    }
    
    /**
     * Valida valor não negativo
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function notNegative(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && is_numeric($value) && $value < 0) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} não pode ser negativo");
        }
    }
    
    /**
     * Valida valor positivo (maior que zero)
     * 
     * @param string $field
     * @param string|null $message
     */
    protected function positive(string $field, ?string $message = null): void
    {
        $value = $this->getValue($field);
        
        if ($value !== null && is_numeric($value) && $value <= 0) {
            $this->addError($field, $message ?? "O campo {$this->fieldName($field)} deve ser maior que zero");
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    /**
     * Obtém valor de um campo
     * 
     * @param string $field
     * @return mixed
     */
    protected function getValue(string $field)
    {
        return $this->data[$field] ?? null;
    }
    
    /**
     * Adiciona erro
     * 
     * @param string $field
     * @param string $message
     */
    protected function addError(string $field, string $message): void
    {
        // Só adiciona se ainda não tem erro no campo
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
    
    /**
     * Obtém todos os erros
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Obtém erro de campo específico
     * 
     * @param string $field
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Converte nome de campo para legível
     * 
     * @param string $field
     * @return string
     */
    protected function fieldName(string $field): string
    {
        // Converte snake_case para palavras
        $name = str_replace('_', ' ', $field);
        return mb_strtolower($name);
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
}
