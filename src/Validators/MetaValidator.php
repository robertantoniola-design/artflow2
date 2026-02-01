<?php

namespace App\Validators;

/**
 * ============================================
 * META VALIDATOR
 * ============================================
 * 
 * Valida dados de entrada para criação/edição de metas mensais.
 * Usa $this->data que é preenchido pelo BaseValidator.
 */
class MetaValidator extends BaseValidator
{
    /**
     * Implementa validação específica de Meta
     */
    protected function doValidation(): void
    {
        // Mês/Ano (obrigatório)
        $this->required('mes_ano', 'O mês/ano é obrigatório');
        
        if (!empty($this->data['mes_ano'])) {
            $this->validateMesAno($this->data['mes_ano']);
        }
        
        // Valor da meta (obrigatório)
        $this->required('valor_meta', 'O valor da meta é obrigatório');
        
        if (isset($this->data['valor_meta']) && $this->data['valor_meta'] !== '') {
            $this->numeric('valor_meta', 'O valor da meta deve ser um número');
            $this->positive('valor_meta', 'O valor da meta deve ser maior que zero');
            
            if (is_numeric($this->data['valor_meta']) && $this->data['valor_meta'] > 99999999.99) {
                $this->addError('valor_meta', 'O valor da meta parece muito alto');
            }
        }
        
        // Horas diárias ideais (opcional)
        if (isset($this->data['horas_diarias_ideal']) && $this->data['horas_diarias_ideal'] !== '') {
            $this->integer('horas_diarias_ideal', 'As horas diárias devem ser um número inteiro');
            $this->minValue('horas_diarias_ideal', 1, 'Horas diárias deve ser pelo menos 1');
            $this->maxValue('horas_diarias_ideal', 24, 'Horas diárias não pode exceder 24');
        }
        
        // Dias de trabalho por semana (opcional)
        if (isset($this->data['dias_trabalho_semana']) && $this->data['dias_trabalho_semana'] !== '') {
            $this->integer('dias_trabalho_semana', 'Os dias de trabalho devem ser um número inteiro');
            $this->minValue('dias_trabalho_semana', 1, 'Dias de trabalho deve ser pelo menos 1');
            $this->maxValue('dias_trabalho_semana', 7, 'Dias de trabalho não pode exceder 7');
        }
        
        // Valor realizado (se fornecido, não negativo)
        if (isset($this->data['valor_realizado']) && $this->data['valor_realizado'] !== '') {
            $this->numeric('valor_realizado', 'O valor realizado deve ser um número');
            $this->notNegative('valor_realizado', 'O valor realizado não pode ser negativo');
        }
    }
    
    /**
     * Valida formato de mês/ano
     */
    private function validateMesAno(string $mesAno): void
    {
        $formatos = [
            '/^\d{4}-\d{2}$/',
            '/^\d{4}-\d{2}-01$/',
            '/^\d{2}\/\d{4}$/'
        ];
        
        $valido = false;
        foreach ($formatos as $formato) {
            if (preg_match($formato, $mesAno)) {
                $valido = true;
                break;
            }
        }
        
        if (!$valido) {
            $this->addError('mes_ano', 'Formato de mês/ano inválido. Use: YYYY-MM ou MM/YYYY');
            return;
        }
        
        if (preg_match('/^\d{4}-\d{2}/', $mesAno)) {
            $parts = explode('-', $mesAno);
            $ano = (int) $parts[0];
            $mes = (int) $parts[1];
        } else {
            $parts = explode('/', $mesAno);
            $mes = (int) $parts[0];
            $ano = (int) $parts[1];
        }
        
        if ($mes < 1 || $mes > 12) {
            $this->addError('mes_ano', 'Mês inválido. Deve ser entre 01 e 12');
        }
        
        if ($ano < 2000 || $ano > 2100) {
            $this->addError('mes_ano', 'Ano inválido. Deve ser entre 2000 e 2100');
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
        
        if (isset($this->data['mes_ano']) && !empty($this->data['mes_ano'])) {
            $this->validateMesAno($this->data['mes_ano']);
        }
        
        if (isset($this->data['valor_meta'])) {
            if ($this->data['valor_meta'] === '' || $this->data['valor_meta'] === null) {
                $this->addError('valor_meta', 'O valor da meta não pode ficar vazio');
            } else {
                $this->numeric('valor_meta', 'O valor da meta deve ser um número');
                $this->positive('valor_meta', 'O valor da meta deve ser maior que zero');
            }
        }
        
        if (isset($this->data['horas_diarias_ideal']) && $this->data['horas_diarias_ideal'] !== '') {
            $this->integer('horas_diarias_ideal', 'As horas diárias devem ser um número inteiro');
            $this->minValue('horas_diarias_ideal', 1, 'Horas diárias deve ser pelo menos 1');
            $this->maxValue('horas_diarias_ideal', 24, 'Horas diárias não pode exceder 24');
        }
        
        if (isset($this->data['dias_trabalho_semana']) && $this->data['dias_trabalho_semana'] !== '') {
            $this->integer('dias_trabalho_semana', 'Os dias de trabalho devem ser um número inteiro');
            $this->minValue('dias_trabalho_semana', 1, 'Dias de trabalho deve ser pelo menos 1');
            $this->maxValue('dias_trabalho_semana', 7, 'Dias de trabalho não pode exceder 7');
        }
        
        return empty($this->errors);
    }
    
    /**
     * Normaliza mês/ano para formato padrão (YYYY-MM-01)
     */
    public static function normalizeMesAno(string $mesAno): string
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $mesAno, $matches)) {
            return "{$matches[1]}-{$matches[2]}-01";
        }
        
        if (preg_match('/^(\d{2})\/(\d{4})$/', $mesAno, $matches)) {
            return "{$matches[2]}-{$matches[1]}-01";
        }
        
        if (preg_match('/^\d{4}-\d{2}-01$/', $mesAno)) {
            return $mesAno;
        }
        
        return $mesAno;
    }
}
