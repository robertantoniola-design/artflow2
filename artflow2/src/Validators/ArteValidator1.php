<?php

namespace App\Validators;

/**
 * ============================================
 * ARTE VALIDATOR — CORRIGIDO Fase 1
 * ============================================
 * 
 * Valida dados de entrada para criação/edição de artes.
 * Usa $this->data que é preenchido pelo BaseValidator.
 * 
 * CORREÇÕES APLICADAS:
 * ─────────────────────
 * [Bug A1] 'reservada' adicionada ao $statusValidos
 *          → A migration 001 define ENUM('disponivel','em_producao','vendida','reservada')
 *          → O Validator original só aceitava 3 dos 4 valores
 *          → Resultado: criar/editar arte com status 'reservada' era rejeitado silenciosamente
 *          → Correção: $statusValidos agora inclui os 4 valores do ENUM
 * 
 * [Melhoria] Mensagens de erro atualizadas para listar 'reservada' como opção válida
 * [Melhoria] Limite de nome aumentado para 150 (consistente com VARCHAR(150) da migration)
 */
class ArteValidator extends BaseValidator
{
    /**
     * Status válidos para arte
     * 
     * ⚠️ [Bug A1 CORRIGIDO] — Adicionado 'reservada'
     * DEVE corresponder EXATAMENTE ao ENUM da migration 001:
     * ENUM('disponivel', 'em_producao', 'vendida', 'reservada')
     * 
     * ANTES (BUG):  ['disponivel', 'em_producao', 'vendida']
     * DEPOIS (FIX):  ['disponivel', 'em_producao', 'vendida', 'reservada']
     */
    private array $statusValidos = ['disponivel', 'em_producao', 'vendida', 'reservada'];
    
    /**
     * Níveis de complexidade válidos
     * Corresponde ao ENUM da migration 001: ENUM('baixa','media','alta')
     */
    private array $complexidadeValidas = ['baixa', 'media', 'alta'];
    
    /**
     * Implementa validação específica de Arte
     * Usa $this->data (preenchido pelo BaseValidator)
     * 
     * Chamado por: BaseValidator::validate() → lança ValidationException se há erros
     *              BaseValidator::isValid()   → retorna bool
     */
    protected function doValidation(): void
    {
        // ─── Nome (obrigatório) ───
        $this->required('nome', 'O nome da arte é obrigatório');
        
        if (!empty($this->data['nome'])) {
            $this->minLength('nome', 3, 'O nome deve ter pelo menos 3 caracteres');
            // [Melhoria] Limite 150 = VARCHAR(150) da migration (antes era 100)
            $this->maxLength('nome', 150, 'O nome deve ter no máximo 150 caracteres');
        }
        
        // ─── Descrição (opcional) ───
        if (!empty($this->data['descricao'])) {
            $this->maxLength('descricao', 1000, 'A descrição deve ter no máximo 1000 caracteres');
        }
        
        // ─── Tempo médio (obrigatório) ───
        $this->required('tempo_medio_horas', 'O tempo médio de produção é obrigatório');
        
        if (isset($this->data['tempo_medio_horas'])) {
            $this->numeric('tempo_medio_horas', 'O tempo médio deve ser um número');
            $this->positive('tempo_medio_horas', 'O tempo médio deve ser maior que zero');
        }
        
        // ─── Complexidade (obrigatória) ───
        $this->required('complexidade', 'A complexidade é obrigatória');
        
        if (!empty($this->data['complexidade'])) {
            $this->in('complexidade', $this->complexidadeValidas, 
                'Complexidade inválida. Use: baixa, media ou alta');
        }
        
        // ─── Preço de custo (opcional, mas não negativo) ───
        if (isset($this->data['preco_custo']) && $this->data['preco_custo'] !== '') {
            $this->numeric('preco_custo', 'O preço de custo deve ser um número');
            $this->notNegative('preco_custo', 'O preço de custo não pode ser negativo');
        }
        
        // ─── Horas trabalhadas (opcional, mas não negativo) ───
        if (isset($this->data['horas_trabalhadas']) && $this->data['horas_trabalhadas'] !== '') {
            $this->numeric('horas_trabalhadas', 'As horas trabalhadas devem ser um número');
            $this->notNegative('horas_trabalhadas', 'As horas trabalhadas não podem ser negativas');
        }
        
        // ─── Status (obrigatório se fornecido) ───
        // [Bug A1 CORRIGIDO] — Mensagem agora lista 'reservada' como opção válida
        if (!empty($this->data['status'])) {
            $this->in('status', $this->statusValidos, 
                'Status inválido. Use: disponivel, em_producao, vendida ou reservada');
        }
    }
    
    /**
     * Valida dados para criação
     * Delega para isValid() que chama doValidation()
     * 
     * @param array $data
     * @return bool
     */
    public function validateCreate(array $data): bool
    {
        return $this->isValid($data);
    }
    
    /**
     * Valida dados para atualização (mais flexível)
     * 
     * Na edição, campos são opcionais (só valida se fornecidos).
     * Diferente do doValidation() que exige nome e tempo_medio_horas.
     * 
     * @param array $data
     * @return bool true = válido, false = há erros em $this->errors
     */
    public function validateUpdate(array $data): bool
    {
        $this->data = $data;
        $this->errors = [];
        
        // ─── Nome (se fornecido, não pode ficar vazio) ───
        if (isset($this->data['nome'])) {
            if (empty($this->data['nome'])) {
                $this->addError('nome', 'O nome não pode ficar vazio');
            } else {
                $this->minLength('nome', 3, 'O nome deve ter pelo menos 3 caracteres');
                // [Melhoria] Limite 150 = VARCHAR(150) da migration
                $this->maxLength('nome', 150, 'O nome deve ter no máximo 150 caracteres');
            }
        }
        
        // ─── Descrição (se fornecida) ───
        if (isset($this->data['descricao']) && !empty($this->data['descricao'])) {
            $this->maxLength('descricao', 1000, 'A descrição deve ter no máximo 1000 caracteres');
        }
        
        // ─── Tempo médio (se fornecido) ───
        if (isset($this->data['tempo_medio_horas'])) {
            $this->numeric('tempo_medio_horas', 'O tempo médio deve ser um número');
            $this->positive('tempo_medio_horas', 'O tempo médio deve ser maior que zero');
        }
        
        // ─── Complexidade (se fornecida) ───
        if (isset($this->data['complexidade']) && !empty($this->data['complexidade'])) {
            $this->in('complexidade', $this->complexidadeValidas, 
                'Complexidade inválida. Use: baixa, media ou alta');
        }
        
        // ─── Preço de custo (se fornecido) ───
        if (isset($this->data['preco_custo']) && $this->data['preco_custo'] !== '') {
            $this->numeric('preco_custo', 'O preço de custo deve ser um número');
            $this->notNegative('preco_custo', 'O preço de custo não pode ser negativo');
        }
        
        // ─── Horas trabalhadas (se fornecido) ───
        if (isset($this->data['horas_trabalhadas']) && $this->data['horas_trabalhadas'] !== '') {
            $this->numeric('horas_trabalhadas', 'As horas trabalhadas devem ser um número');
            $this->notNegative('horas_trabalhadas', 'As horas trabalhadas não podem ser negativas');
        }
        
        // ─── Status (se fornecido) ───
        // [Bug A1 CORRIGIDO] — Agora aceita 'reservada' e mensagem atualizada
        if (isset($this->data['status']) && !empty($this->data['status'])) {
            $this->in('status', $this->statusValidos, 
                'Status inválido. Use: disponivel, em_producao, vendida ou reservada');
        }
        
        return empty($this->errors);
    }
}