<?php
namespace App\Models;

/**
 * MODEL: TAG
 * 
 * Representa uma etiqueta/categoria para artes.
 */
class Tag
{
    private ?int $id = null;
    private string $nome = '';
    private string $cor = '#6c757d';
    private ?string $icone = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    
    // Contagem de artes (opcional)
    private int $artes_count = 0;
    
    // ========================================
    // GETTERS
    // ========================================
    
    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getCor(): string { return $this->cor; }
    public function getIcone(): ?string { return $this->icone; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    public function getArtesCount(): int { return $this->artes_count; }
    
    // ========================================
    // SETTERS
    // ========================================
    
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setNome(string $nome): self { $this->nome = trim($nome); return $this; }
    public function setCor(string $cor): self { $this->cor = $cor; return $this; }
    public function setIcone(?string $icone): self { $this->icone = $icone; return $this; }
    public function setCreatedAt(?string $dt): self { $this->created_at = $dt; return $this; }
    public function setUpdatedAt(?string $dt): self { $this->updated_at = $dt; return $this; }
    public function setArtesCount(int $count): self { $this->artes_count = $count; return $this; }
    
    // ========================================
    // MÉTODOS DE CONVENIÊNCIA
    // ========================================
    
    /**
     * Retorna HTML do badge da tag
     */
    public function getBadgeHtml(): string
    {
        $nome = htmlspecialchars($this->nome);
        $icone = $this->icone ? "<i class=\"{$this->icone} me-1\"></i>" : '';
        return "<span class=\"badge\" style=\"background-color: {$this->cor}\">{$icone}{$nome}</span>";
    }
    
    /**
     * Retorna cor de texto contrastante (preto ou branco)
     */
    public function getCorTexto(): string
    {
        // Remove # se existir
        $hex = ltrim($this->cor, '#');
        
        // Converte para RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Calcula luminância (percepção humana)
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        // Se claro, usa texto escuro; se escuro, usa texto claro
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
    
    /**
     * Retorna estilo inline para uso em HTML
     */
    public function getStyleInline(): string
    {
        return "background-color: {$this->cor}; color: {$this->getCorTexto()};";
    }
    
    // ========================================
    // CONVERSÃO
    // ========================================
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'cor' => $this->cor,
            'icone' => $this->icone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'artes_count' => $this->artes_count,
        ];
    }
    
    public static function fromArray(array $data): self
    {
        $tag = new self();
        $tag->id = isset($data['id']) ? (int)$data['id'] : null;
        $tag->nome = $data['nome'] ?? '';
        $tag->cor = $data['cor'] ?? '#6c757d';
        $tag->icone = $data['icone'] ?? null;
        $tag->created_at = $data['created_at'] ?? null;
        $tag->updated_at = $data['updated_at'] ?? null;
        $tag->artes_count = (int)($data['artes_count'] ?? 0);
        return $tag;
    }
}
