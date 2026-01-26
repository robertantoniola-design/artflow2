<?php

namespace App\Validators;

/**
 * ============================================
 * VENDA VALIDATOR
 * ============================================
 * 
 * Valida dados de entrada para registro de vendas.
 * Usa $this->data que é preenchido pelo BaseValidator.
 */
class VendaValidator extends BaseValidator
{
    /**
     * Implementa validação específica de Venda
     */
    protected function doValidation(): void
    {
        // Arte ID (obrigatório)
        $this->required('arte_id', 'Selecione uma arte para a venda');
        
        if (!empty($this->data['arte_id'])) {
            $this->integer('arte_id', 'ID da arte inválido');
            
            if (is_numeric($this->data['arte_id']) && $this->data['arte_id'] <= 0) {
                $this->addError('arte_id', 'Selecione uma arte válida');
            }
        }
        
        // Cliente ID (opcional, mas se fornecido deve ser válido)
        if (!empty($this->data['cliente_id'])) {
            $this->integer('cliente_id', 'ID do cliente inválido');
            
            if (is_numeric($this->data['cliente_id']) && $this->data['cliente_id'] <= 0) {
                $this->addError('cliente_id', 'Selecione um cliente válido');
            }
        }
        
        // Valor (obrigatório)
        $this->required('valor', 'O valor da venda é obrigatório');
        
        if (isset($this->data['valor']) && $this->data['valor'] !== '') {
            $this->numeric('valor', 'O valor deve ser um número');
            $this->positive('valor', 'O valor deve ser maior que zero');
            
            if (is_numeric($this->data['valor']) && $this->data['valor'] > 9999999.99) {
                $this->addError('valor', 'O valor parece muito alto. Verifique se está correto');
            }
        }
        
        // Data da venda (obrigatória)
        $this->required('data_venda', 'A data da venda é obrigatória');
        
        if (!empty($this->data['data_venda'])) {
            $this->date('data_venda', 'Y-m-d', 'Data da venda inválida');
            $this->validateDataNaoFutura($this->data['data_venda']);
        }
    }
    
    /**
     * Valida que a data não é futura
     */
    private function validateDataNaoFutura(string $data): void
    {
        $dataVenda = strtotime($data);
        $hoje = strtotime(date('Y-m-d'));
        
        if ($dataVenda > $hoje) {
            $this->addError('data_venda', 'A data da venda não pode ser no futuro');
        }
    }
    
    /**
     * Valida dados para criação de venda
     */
    public function validateCreate(array $data): bool
    {
        return $this->isValid($data);
    }
    
    /**
     * Valida dados para atualização de venda
     */
    public function validateUpdate(array $data): bool
    {
        $this->data = $data;
        $this->errors = [];
        
        if (isset($this->data['arte_id'])) {
            if (empty($this->data['arte_id'])) {
                $this->addError('arte_id', 'A arte não pode ficar vazia');
            } else {
                $this->integer('arte_id', 'ID da arte inválido');
            }
        }
        
        if (isset($this->data['cliente_id']) && !empty($this->data['cliente_id'])) {
            $this->integer('cliente_id', 'ID do cliente inválido');
        }
        
        if (isset($this->data['valor'])) {
            if ($this->data['valor'] === '' || $this->data['valor'] === null) {
                $this->addError('valor', 'O valor não pode ficar vazio');
            } else {
                $this->numeric('valor', 'O valor deve ser um número');
                $this->positive('valor', 'O valor deve ser maior que zero');
            }
        }
        
        if (isset($this->data['data_venda']) && !empty($this->data['data_venda'])) {
            $this->date('data_venda', 'Y-m-d', 'Data da venda inválida');
            $this->validateDataNaoFutura($this->data['data_venda']);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Valida se arte está disponível para venda
     */
    public function validateArteDisponivel(string $statusArte): bool
    {
        $statusPermitidos = ['disponivel', 'em_producao'];
        
        if (!in_array($statusArte, $statusPermitidos)) {
            $this->addError('arte_id', 'Esta arte já foi vendida e não pode ser vendida novamente');
            return false;
        }
        
        return true;
    }
}
