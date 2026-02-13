<?php

namespace App\Validators;

/**
 * ============================================
 * CLIENTE VALIDATOR
 * ============================================
 * 
 * Valida dados de entrada para criação/edição de clientes.
 * Usa $this->data que é preenchido pelo BaseValidator.
 * 
 * FASE 1 (13/02/2026):
 * - [FIX-B3] Validação para cidade, estado, endereco, observacoes
 * - [FIX-B10] Telefone: exige 10-11 dígitos numéricos
 * - validateUpdate(): validação mais flexível para edição
 */
class ClienteValidator extends BaseValidator
{
    /**
     * UFs válidas do Brasil
     */
    private const UFS_VALIDAS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
        'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
        'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
    ];
    
    /**
     * Implementa validação específica de Cliente
     * Chamado por BaseValidator::validate() e isValid()
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
        
        // Telefone (opcional, mas se fornecido deve ter 10-11 dígitos)
        // [FIX-B10] Reforçado: rejeita entradas incompletas
        if (!empty($this->data['telefone'])) {
            $this->maxLength('telefone', 20, 'O telefone deve ter no máximo 20 caracteres');
            $this->validarTelefoneBR();
        }
        
        // Empresa (opcional)
        if (!empty($this->data['empresa'])) {
            $this->maxLength('empresa', 100, 'O nome da empresa deve ter no máximo 100 caracteres');
        }
        
        // [FIX-B3] Campos adicionais da migration
        
        // Cidade (opcional)
        if (!empty($this->data['cidade'])) {
            $this->maxLength('cidade', 100, 'A cidade deve ter no máximo 100 caracteres');
        }
        
        // Estado/UF (opcional, valida código de 2 letras)
        if (!empty($this->data['estado'])) {
            $uf = mb_strtoupper(trim($this->data['estado']), 'UTF-8');
            if (!in_array($uf, self::UFS_VALIDAS)) {
                $this->addError('estado', 'Informe uma UF válida (ex: SP, RJ, PR)');
            }
        }
        
        // Endereço (opcional)
        if (!empty($this->data['endereco'])) {
            $this->maxLength('endereco', 255, 'O endereço deve ter no máximo 255 caracteres');
        }
        
        // Observações (opcional)
        if (!empty($this->data['observacoes'])) {
            $this->maxLength('observacoes', 1000, 'As observações devem ter no máximo 1000 caracteres');
        }
    }
    
    /**
     * Validação customizada de telefone brasileiro
     * 
     * [FIX-B10] Remove tudo que não é número, depois verifica:
     * - Mínimo 10 dígitos (fixo: DDD + 8 dígitos)
     * - Máximo 11 dígitos (celular: DDD + 9 dígitos)
     */
    private function validarTelefoneBR(): void
    {
        $apenasNumeros = preg_replace('/[^0-9]/', '', $this->data['telefone']);
        $total = strlen($apenasNumeros);
        
        if ($total < 10 || $total > 11) {
            $this->addError('telefone', 'Telefone inválido. Informe DDD + número (10 ou 11 dígitos)');
        }
    }
    
    /**
     * Valida dados para atualização (mais flexível que doValidation)
     * 
     * Na edição, o nome pode já estar preenchido e não precisa da regra
     * "obrigatório" se o campo nem foi enviado. Mas se enviado vazio, rejeita.
     * 
     * @param array $data
     * @return bool
     */
    public function validateUpdate(array $data): bool
    {
        $this->data = $data;
        $this->errors = [];
        
        // Nome: se presente, não pode ficar vazio
        if (isset($this->data['nome'])) {
            if (empty(trim($this->data['nome']))) {
                $this->addError('nome', 'O nome não pode ficar vazio');
            } else {
                $this->minLength('nome', 2, 'O nome deve ter pelo menos 2 caracteres');
                $this->maxLength('nome', 150, 'O nome deve ter no máximo 150 caracteres');
            }
        }
        
        // Email
        if (!empty($this->data['email'])) {
            $this->email('email', 'O e-mail informado não é válido');
            $this->maxLength('email', 150, 'O e-mail deve ter no máximo 150 caracteres');
        }
        
        // Telefone
        if (!empty($this->data['telefone'])) {
            $this->maxLength('telefone', 20, 'O telefone deve ter no máximo 20 caracteres');
            $this->validarTelefoneBR();
        }
        
        // Empresa
        if (!empty($this->data['empresa'])) {
            $this->maxLength('empresa', 100, 'O nome da empresa deve ter no máximo 100 caracteres');
        }
        
        // Cidade
        if (!empty($this->data['cidade'])) {
            $this->maxLength('cidade', 100, 'A cidade deve ter no máximo 100 caracteres');
        }
        
        // Estado/UF
        if (!empty($this->data['estado'])) {
            $uf = mb_strtoupper(trim($this->data['estado']), 'UTF-8');
            if (!in_array($uf, self::UFS_VALIDAS)) {
                $this->addError('estado', 'Informe uma UF válida (ex: SP, RJ, PR)');
            }
        }
        
        // Endereço
        if (!empty($this->data['endereco'])) {
            $this->maxLength('endereco', 255, 'O endereço deve ter no máximo 255 caracteres');
        }
        
        // Observações
        if (!empty($this->data['observacoes'])) {
            $this->maxLength('observacoes', 1000, 'As observações devem ter no máximo 1000 caracteres');
        }
        
        return empty($this->errors);
    }
}