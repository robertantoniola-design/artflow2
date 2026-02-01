<?php
namespace App\Models;

/**
 * ============================================
 * MODEL: ARTE
 * ============================================
 * 
 * Representa uma arte/trabalho artístico no sistema.
 * 
 * Responsabilidades:
 * - Armazenar dados da entidade
 * - Conversão de/para array
 * - Validação básica de tipos
 * 
 * NÃO deve:
 * - Acessar banco de dados (isso é do Repository)
 * - Conter lógica de negócio complexa (isso é do Service)
 */
class Arte
{
    // ========================================
    // PROPRIEDADES
    // ========================================
    
    private ?int $id = null;
    private string $nome = '';
    private ?string $descricao = null;
    private ?float $tempo_medio_horas = null;
    private string $complexidade = 'media';
    private float $preco_custo = 0;
    private float $horas_trabalhadas = 0;
    private string $status = 'disponivel';
    private ?string $imagem = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    
    // Relacionamentos carregados
    private array $tags = [];
    
    // ========================================
    // STATUS VÁLIDOS (constantes)
    // ========================================
    
    public const STATUS_DISPONIVEL = 'disponivel';
    public const STATUS_EM_PRODUCAO = 'em_producao';
    public const STATUS_VENDIDA = 'vendida';
    public const STATUS_RESERVADA = 'reservada';
    
    public const COMPLEXIDADE_BAIXA = 'baixa';
    public const COMPLEXIDADE_MEDIA = 'media';
    public const COMPLEXIDADE_ALTA = 'alta';
    
    // ========================================
    // GETTERS
    // ========================================
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getNome(): string
    {
        return $this->nome;
    }
    
    public function getDescricao(): ?string
    {
        return $this->descricao;
    }
    
    public function getTempoMedioHoras(): ?float
    {
        return $this->tempo_medio_horas;
    }
    
    public function getComplexidade(): string
    {
        return $this->complexidade;
    }
    
    public function getPrecoCusto(): float
    {
        return $this->preco_custo;
    }
    
    public function getHorasTrabalhadas(): float
    {
        return $this->horas_trabalhadas;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function getImagem(): ?string
    {
        return $this->imagem;
    }
    
    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }
    
    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }
    
    public function getTags(): array
    {
        return $this->tags;
    }
    
    // ========================================
    // SETTERS (com validação básica)
    // ========================================
    
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    public function setNome(string $nome): self
    {
        $this->nome = trim($nome);
        return $this;
    }
    
    public function setDescricao(?string $descricao): self
    {
        $this->descricao = $descricao ? trim($descricao) : null;
        return $this;
    }
    
    public function setTempoMedioHoras(?float $tempo): self
    {
        $this->tempo_medio_horas = $tempo;
        return $this;
    }
    
    public function setComplexidade(string $complexidade): self
    {
        $validos = [self::COMPLEXIDADE_BAIXA, self::COMPLEXIDADE_MEDIA, self::COMPLEXIDADE_ALTA];
        if (!in_array($complexidade, $validos)) {
            throw new \InvalidArgumentException("Complexidade inválida: {$complexidade}");
        }
        $this->complexidade = $complexidade;
        return $this;
    }
    
    public function setPrecoCusto(float $preco): self
    {
        $this->preco_custo = max(0, $preco); // Não permite negativo
        return $this;
    }
    
    public function setHorasTrabalhadas(float $horas): self
    {
        $this->horas_trabalhadas = max(0, $horas);
        return $this;
    }
    
    public function setStatus(string $status): self
    {
        $validos = [
            self::STATUS_DISPONIVEL,
            self::STATUS_EM_PRODUCAO,
            self::STATUS_VENDIDA,
            self::STATUS_RESERVADA
        ];
        if (!in_array($status, $validos)) {
            throw new \InvalidArgumentException("Status inválido: {$status}");
        }
        $this->status = $status;
        return $this;
    }
    
    public function setImagem(?string $imagem): self
    {
        $this->imagem = $imagem;
        return $this;
    }
    
    public function setCreatedAt(?string $datetime): self
    {
        $this->created_at = $datetime;
        return $this;
    }
    
    public function setUpdatedAt(?string $datetime): self
    {
        $this->updated_at = $datetime;
        return $this;
    }
    
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }
    
    // ========================================
    // MÉTODOS DE CONVENIÊNCIA
    // ========================================
    
    /**
     * Verifica se a arte está disponível para venda
     */
    public function isDisponivel(): bool
    {
        return $this->status === self::STATUS_DISPONIVEL;
    }
    
    /**
     * Verifica se está em produção
     */
    public function isEmProducao(): bool
    {
        return $this->status === self::STATUS_EM_PRODUCAO;
    }
    
    /**
     * Verifica se já foi vendida
     */
    public function isVendida(): bool
    {
        return $this->status === self::STATUS_VENDIDA;
    }
    
    /**
     * Retorna label formatado do status
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DISPONIVEL => 'Disponível',
            self::STATUS_EM_PRODUCAO => 'Em Produção',
            self::STATUS_VENDIDA => 'Vendida',
            self::STATUS_RESERVADA => 'Reservada',
            default => 'Desconhecido'
        };
    }
    
    /**
     * Retorna classe CSS do status (para badges)
     */
    public function getStatusClass(): string
    {
        return match($this->status) {
            self::STATUS_DISPONIVEL => 'success',
            self::STATUS_EM_PRODUCAO => 'warning',
            self::STATUS_VENDIDA => 'secondary',
            self::STATUS_RESERVADA => 'info',
            default => 'dark'
        };
    }
    
    /**
     * Retorna label da complexidade
     */
    public function getComplexidadeLabel(): string
    {
        return match($this->complexidade) {
            self::COMPLEXIDADE_BAIXA => 'Baixa',
            self::COMPLEXIDADE_MEDIA => 'Média',
            self::COMPLEXIDADE_ALTA => 'Alta',
            default => 'Desconhecida'
        };
    }
    
    /**
     * Calcula custo por hora trabalhada
     */
    public function getCustoPorHora(): float
    {
        if ($this->horas_trabalhadas <= 0) {
            return 0;
        }
        return $this->preco_custo / $this->horas_trabalhadas;
    }
    
    // ========================================
    // CONVERSÃO DE/PARA ARRAY
    // ========================================
    
    /**
     * Converte Model para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'tempo_medio_horas' => $this->tempo_medio_horas,
            'complexidade' => $this->complexidade,
            'preco_custo' => $this->preco_custo,
            'horas_trabalhadas' => $this->horas_trabalhadas,
            'status' => $this->status,
            'imagem' => $this->imagem,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tags' => $this->tags,
        ];
    }
    
    /**
     * Cria Model a partir de array (hydration)
     */
    public static function fromArray(array $data): self
    {
        $arte = new self();
        
        $arte->id = isset($data['id']) ? (int)$data['id'] : null;
        $arte->nome = $data['nome'] ?? '';
        $arte->descricao = $data['descricao'] ?? null;
        $arte->tempo_medio_horas = isset($data['tempo_medio_horas']) ? (float)$data['tempo_medio_horas'] : null;
        $arte->complexidade = $data['complexidade'] ?? 'media';
        $arte->preco_custo = isset($data['preco_custo']) ? (float)$data['preco_custo'] : 0;
        $arte->horas_trabalhadas = isset($data['horas_trabalhadas']) ? (float)$data['horas_trabalhadas'] : 0;
        $arte->status = $data['status'] ?? 'disponivel';
        $arte->imagem = $data['imagem'] ?? null;
        $arte->created_at = $data['created_at'] ?? null;
        $arte->updated_at = $data['updated_at'] ?? null;
        $arte->tags = $data['tags'] ?? [];
        
        return $arte;
    }
}
