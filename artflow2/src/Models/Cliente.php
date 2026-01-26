<?php
namespace App\Models;

/**
 * MODEL: CLIENTE
 * 
 * Representa um cliente/comprador no sistema.
 */
class Cliente
{
    private ?int $id = null;
    private string $nome = '';
    private ?string $email = null;
    private ?string $telefone = null;
    private ?string $empresa = null;
    private ?string $endereco = null;
    private ?string $cidade = null;
    private ?string $estado = null;
    private ?string $observacoes = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    
    // Estatísticas carregadas (opcional)
    private int $total_compras = 0;
    private float $valor_total_compras = 0;
    
    // ========================================
    // GETTERS
    // ========================================
    
    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): ?string { return $this->email; }
    public function getTelefone(): ?string { return $this->telefone; }
    public function getEmpresa(): ?string { return $this->empresa; }
    public function getEndereco(): ?string { return $this->endereco; }
    public function getCidade(): ?string { return $this->cidade; }
    public function getEstado(): ?string { return $this->estado; }
    public function getObservacoes(): ?string { return $this->observacoes; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    public function getTotalCompras(): int { return $this->total_compras; }
    public function getValorTotalCompras(): float { return $this->valor_total_compras; }
    
    // ========================================
    // SETTERS
    // ========================================
    
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setNome(string $nome): self { $this->nome = trim($nome); return $this; }
    public function setEmail(?string $email): self { $this->email = $email ? trim($email) : null; return $this; }
    public function setTelefone(?string $telefone): self { $this->telefone = $telefone; return $this; }
    public function setEmpresa(?string $empresa): self { $this->empresa = $empresa; return $this; }
    public function setEndereco(?string $endereco): self { $this->endereco = $endereco; return $this; }
    public function setCidade(?string $cidade): self { $this->cidade = $cidade; return $this; }
    public function setEstado(?string $estado): self { $this->estado = $estado; return $this; }
    public function setObservacoes(?string $obs): self { $this->observacoes = $obs; return $this; }
    public function setCreatedAt(?string $dt): self { $this->created_at = $dt; return $this; }
    public function setUpdatedAt(?string $dt): self { $this->updated_at = $dt; return $this; }
    public function setTotalCompras(int $total): self { $this->total_compras = $total; return $this; }
    public function setValorTotalCompras(float $valor): self { $this->valor_total_compras = $valor; return $this; }
    
    // ========================================
    // MÉTODOS DE CONVENIÊNCIA
    // ========================================
    
    /**
     * Retorna localização formatada (Cidade/UF)
     */
    public function getLocalizacao(): string
    {
        if ($this->cidade && $this->estado) {
            return "{$this->cidade}/{$this->estado}";
        }
        return $this->cidade ?? $this->estado ?? '';
    }
    
    /**
     * Formata telefone para exibição
     */
    public function getTelefoneFormatado(): string
    {
        if (!$this->telefone) return '';
        
        // Remove não-numéricos
        $tel = preg_replace('/\D/', '', $this->telefone);
        
        // Formata (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
        if (strlen($tel) === 11) {
            return sprintf('(%s) %s-%s', 
                substr($tel, 0, 2),
                substr($tel, 2, 5),
                substr($tel, 7)
            );
        } elseif (strlen($tel) === 10) {
            return sprintf('(%s) %s-%s',
                substr($tel, 0, 2),
                substr($tel, 2, 4),
                substr($tel, 6)
            );
        }
        
        return $this->telefone;
    }
    
    // ========================================
    // CONVERSÃO
    // ========================================
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'empresa' => $this->empresa,
            'endereco' => $this->endereco,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'observacoes' => $this->observacoes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'total_compras' => $this->total_compras,
            'valor_total_compras' => $this->valor_total_compras,
        ];
    }
    
    public static function fromArray(array $data): self
    {
        $cliente = new self();
        $cliente->id = isset($data['id']) ? (int)$data['id'] : null;
        $cliente->nome = $data['nome'] ?? '';
        $cliente->email = $data['email'] ?? null;
        $cliente->telefone = $data['telefone'] ?? null;
        $cliente->empresa = $data['empresa'] ?? null;
        $cliente->endereco = $data['endereco'] ?? null;
        $cliente->cidade = $data['cidade'] ?? null;
        $cliente->estado = $data['estado'] ?? null;
        $cliente->observacoes = $data['observacoes'] ?? null;
        $cliente->created_at = $data['created_at'] ?? null;
        $cliente->updated_at = $data['updated_at'] ?? null;
        $cliente->total_compras = (int)($data['total_compras'] ?? 0);
        $cliente->valor_total_compras = (float)($data['valor_total_compras'] ?? 0);
        return $cliente;
    }
}
