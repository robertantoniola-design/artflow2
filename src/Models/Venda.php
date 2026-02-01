<?php
namespace App\Models;

/**
 * MODEL: VENDA
 * 
 * Representa uma venda de arte no sistema.
 * Contém cálculos de lucro e rentabilidade.
 */
class Venda
{
    private ?int $id = null;
    private ?int $arte_id = null;
    private ?int $cliente_id = null;
    private float $valor = 0;
    private ?string $data_venda = null;
    private ?float $lucro_calculado = null;
    private ?float $rentabilidade_hora = null;
    private string $forma_pagamento = 'pix';
    private ?string $observacoes = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    
    // Relacionamentos carregados
    private ?Arte $arte = null;
    private ?Cliente $cliente = null;
    
    // Constantes
    public const PAGAMENTO_DINHEIRO = 'dinheiro';
    public const PAGAMENTO_PIX = 'pix';
    public const PAGAMENTO_CREDITO = 'cartao_credito';
    public const PAGAMENTO_DEBITO = 'cartao_debito';
    public const PAGAMENTO_TRANSFERENCIA = 'transferencia';
    public const PAGAMENTO_OUTRO = 'outro';
    
    // ========================================
    // GETTERS
    // ========================================
    
    public function getId(): ?int { return $this->id; }
    public function getArteId(): ?int { return $this->arte_id; }
    public function getClienteId(): ?int { return $this->cliente_id; }
    public function getValor(): float { return $this->valor; }
    public function getDataVenda(): ?string { return $this->data_venda; }
    public function getLucroCalculado(): ?float { return $this->lucro_calculado; }
    public function getRentabilidadeHora(): ?float { return $this->rentabilidade_hora; }
    public function getFormaPagamento(): string { return $this->forma_pagamento; }
    public function getObservacoes(): ?string { return $this->observacoes; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    public function getArte(): ?Arte { return $this->arte; }
    public function getCliente(): ?Cliente { return $this->cliente; }
    
    // ========================================
    // SETTERS
    // ========================================
    
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setArteId(?int $id): self { $this->arte_id = $id; return $this; }
    public function setClienteId(?int $id): self { $this->cliente_id = $id; return $this; }
    public function setValor(float $valor): self { $this->valor = max(0, $valor); return $this; }
    public function setDataVenda(?string $data): self { $this->data_venda = $data; return $this; }
    public function setLucroCalculado(?float $lucro): self { $this->lucro_calculado = $lucro; return $this; }
    public function setRentabilidadeHora(?float $rent): self { $this->rentabilidade_hora = $rent; return $this; }
    public function setFormaPagamento(string $forma): self { $this->forma_pagamento = $forma; return $this; }
    public function setObservacoes(?string $obs): self { $this->observacoes = $obs; return $this; }
    public function setCreatedAt(?string $dt): self { $this->created_at = $dt; return $this; }
    public function setUpdatedAt(?string $dt): self { $this->updated_at = $dt; return $this; }
    public function setArte(?Arte $arte): self { $this->arte = $arte; return $this; }
    public function setCliente(?Cliente $cliente): self { $this->cliente = $cliente; return $this; }
    
    // ========================================
    // MÉTODOS DE CONVENIÊNCIA
    // ========================================
    
    /**
     * Retorna data formatada (DD/MM/YYYY)
     */
    public function getDataVendaFormatada(): string
    {
        if (!$this->data_venda) return '';
        return date('d/m/Y', strtotime($this->data_venda));
    }
    
    /**
     * Retorna label da forma de pagamento
     */
    public function getFormaPagamentoLabel(): string
    {
        return match($this->forma_pagamento) {
            self::PAGAMENTO_DINHEIRO => 'Dinheiro',
            self::PAGAMENTO_PIX => 'PIX',
            self::PAGAMENTO_CREDITO => 'Cartão de Crédito',
            self::PAGAMENTO_DEBITO => 'Cartão de Débito',
            self::PAGAMENTO_TRANSFERENCIA => 'Transferência',
            self::PAGAMENTO_OUTRO => 'Outro',
            default => 'Não informado'
        };
    }
    
    /**
     * Retorna margem de lucro em porcentagem
     */
    public function getMargemLucro(): float
    {
        if ($this->valor <= 0) return 0;
        if ($this->lucro_calculado === null) return 0;
        return ($this->lucro_calculado / $this->valor) * 100;
    }
    
    /**
     * Verifica se teve lucro
     */
    public function teveLucro(): bool
    {
        return $this->lucro_calculado !== null && $this->lucro_calculado > 0;
    }
    
    // ========================================
    // CONVERSÃO
    // ========================================
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'arte_id' => $this->arte_id,
            'cliente_id' => $this->cliente_id,
            'valor' => $this->valor,
            'data_venda' => $this->data_venda,
            'lucro_calculado' => $this->lucro_calculado,
            'rentabilidade_hora' => $this->rentabilidade_hora,
            'forma_pagamento' => $this->forma_pagamento,
            'observacoes' => $this->observacoes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    
    public static function fromArray(array $data): self
    {
        $venda = new self();
        $venda->id = isset($data['id']) ? (int)$data['id'] : null;
        $venda->arte_id = isset($data['arte_id']) ? (int)$data['arte_id'] : null;
        $venda->cliente_id = isset($data['cliente_id']) ? (int)$data['cliente_id'] : null;
        $venda->valor = (float)($data['valor'] ?? 0);
        $venda->data_venda = $data['data_venda'] ?? null;
        $venda->lucro_calculado = isset($data['lucro_calculado']) ? (float)$data['lucro_calculado'] : null;
        $venda->rentabilidade_hora = isset($data['rentabilidade_hora']) ? (float)$data['rentabilidade_hora'] : null;
        $venda->forma_pagamento = $data['forma_pagamento'] ?? 'pix';
        $venda->observacoes = $data['observacoes'] ?? null;
        $venda->created_at = $data['created_at'] ?? null;
        $venda->updated_at = $data['updated_at'] ?? null;
        
        // Hydrata relacionamentos se existirem
        if (isset($data['arte']) && is_array($data['arte'])) {
            $venda->arte = Arte::fromArray($data['arte']);
        }
        if (isset($data['cliente']) && is_array($data['cliente'])) {
            $venda->cliente = Cliente::fromArray($data['cliente']);
        }
        
        return $venda;
    }
}
