<?php
namespace App\Models;

/**
 * ============================================
 * MODEL: META
 * ============================================
 * 
 * Representa uma meta mensal de vendas.
 * 
 * ATUALIZAÇÃO (01/02/2026):
 * - Adicionado campo 'status' com constantes
 * - Adicionados métodos getStatusLabel(), getStatusIcon(), getStatusBadgeClass()
 * - Atualizado toArray() e fromArray() para incluir status
 * 
 * MELHORIA 1 — Status "Superado" (01/02/2026):
 * - Adicionada constante STATUS_SUPERADO = 'superado'
 * - Adicionada constante THRESHOLD_SUPERADO = 120.0 (limiar configurável)
 * - Adicionado método isSuperado(): bool
 * - Atualizado getStatusLabel() → 'Superado'
 * - Atualizado getStatusIcon() → 'bi-trophy-fill' (troféu)
 * - Atualizado getStatusBadgeClass() → 'bg-warning text-dark' (dourado)
 * 
 * CICLO DE VIDA DO STATUS:
 * 'iniciado'     → Meta criada, nenhuma venda registrada no mês
 * 'em_progresso' → Pelo menos uma venda registrada no mês
 * 'superado'     → Porcentagem atingida >= 120% (NOVO)
 * 'finalizado'   → Mês encerrado (sem ter superado)
 * 
 * NOTA: Status 'superado' tem prioridade sobre 'finalizado'.
 * Se a meta atingiu >= 120%, ela fica como 'superado' permanentemente,
 * mesmo após o mês encerrar. Só vira 'finalizado' se terminou < 120%.
 */
class Meta
{
    // ========================================
    // CONSTANTES DE STATUS
    // ========================================
    
    /** Meta recém-criada, sem vendas no mês */
    const STATUS_INICIADO = 'iniciado';
    
    /** Pelo menos uma venda registrada no mês */
    const STATUS_EM_PROGRESSO = 'em_progresso';
    
    /** Mês encerrado (porcentagem < 120%) */
    const STATUS_FINALIZADO = 'finalizado';
    
    /** NOVO: Meta superou o objetivo (porcentagem >= 120%) */
    const STATUS_SUPERADO = 'superado';
    
    /**
     * Limiar (threshold) para considerar meta como "superada"
     * 
     * Valor: 120.0 significa que precisa atingir pelo menos 120% da meta.
     * Se quiser mudar para 110%, basta alterar aqui.
     * Centralizado como constante para facilitar ajustes futuros.
     */
    const THRESHOLD_SUPERADO = 120.0;
    
    /** Lista de status válidos (para validação) */
    const STATUS_VALIDOS = [
        self::STATUS_INICIADO,
        self::STATUS_EM_PROGRESSO,
        self::STATUS_FINALIZADO,
        self::STATUS_SUPERADO,      // NOVO: adicionado
    ];
    
    // ========================================
    // PROPRIEDADES
    // ========================================
    
    private ?int $id = null;
    private ?string $mes_ano = null;
    private float $valor_meta = 0;
    private int $horas_diarias_ideal = 8;
    private int $dias_trabalho_semana = 5;
    private float $valor_realizado = 0;
    private float $porcentagem_atingida = 0;
    private string $status = self::STATUS_INICIADO;
    private ?string $observacoes = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    
    // ========================================
    // GETTERS
    // ========================================
    
    public function getId(): ?int { return $this->id; }
    public function getMesAno(): ?string { return $this->mes_ano; }
    public function getValorMeta(): float { return $this->valor_meta; }
    public function getHorasDiariasIdeal(): int { return $this->horas_diarias_ideal; }
    public function getDiasTrabalhoSemana(): int { return $this->dias_trabalho_semana; }
    public function getValorRealizado(): float { return $this->valor_realizado; }
    public function getPorcentagemAtingida(): float { return $this->porcentagem_atingida; }
    public function getStatus(): string { return $this->status; }
    public function getObservacoes(): ?string { return $this->observacoes; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    
    // ========================================
    // SETTERS
    // ========================================
    
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setMesAno(?string $mesAno): self { $this->mes_ano = $mesAno; return $this; }
    public function setValorMeta(float $valor): self { $this->valor_meta = max(0, $valor); return $this; }
    public function setHorasDiariasIdeal(int $horas): self { $this->horas_diarias_ideal = max(1, min(24, $horas)); return $this; }
    public function setDiasTrabalhoSemana(int $dias): self { $this->dias_trabalho_semana = max(1, min(7, $dias)); return $this; }
    public function setValorRealizado(float $valor): self { $this->valor_realizado = max(0, $valor); return $this; }
    public function setPorcentagemAtingida(float $pct): self { $this->porcentagem_atingida = max(0, $pct); return $this; }
    public function setObservacoes(?string $obs): self { $this->observacoes = $obs; return $this; }
    
    /**
     * Define o status da meta com validação
     * 
     * @param string $status Um dos valores: 'iniciado', 'em_progresso', 'finalizado', 'superado'
     * @return self
     * @throws \InvalidArgumentException Se status inválido
     */
    public function setStatus(string $status): self
    {
        if (!in_array($status, self::STATUS_VALIDOS)) {
            throw new \InvalidArgumentException(
                "Status inválido: '{$status}'. Valores permitidos: " . implode(', ', self::STATUS_VALIDOS)
            );
        }
        $this->status = $status;
        return $this;
    }
    
    // Helpers de data interna (necessários para setters protegidos)
    public function setCreatedAt(?string $dt): self { $this->created_at = $dt; return $this; }
    public function setUpdatedAt(?string $dt): self { $this->updated_at = $dt; return $this; }
    
    // ========================================
    // MÉTODOS DE STATUS
    // ========================================
    
    /**
     * Retorna o label legível do status para exibição na UI
     * Ex: 'em_progresso' → 'Em Progresso', 'superado' → 'Superado'
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_INICIADO     => 'Iniciado',
            self::STATUS_EM_PROGRESSO => 'Em Progresso',
            self::STATUS_FINALIZADO   => 'Finalizado',
            self::STATUS_SUPERADO     => 'Superado',        // NOVO
            default                   => 'Desconhecido'
        };
    }
    
    /**
     * Retorna o ícone Bootstrap Icons para o status
     * Usado nos badges da listagem e detalhes
     */
    public function getStatusIcon(): string
    {
        return match($this->status) {
            self::STATUS_INICIADO     => 'bi-hourglass',
            self::STATUS_EM_PROGRESSO => 'bi-activity',
            self::STATUS_FINALIZADO   => 'bi-check-circle',
            self::STATUS_SUPERADO     => 'bi-trophy-fill',   // NOVO: troféu para superado
            default                   => 'bi-question-circle'
        };
    }
    
    /**
     * Retorna a classe CSS do badge Bootstrap para o status
     * Cores diferenciadas para fácil identificação visual
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_INICIADO     => 'bg-secondary',           // cinza
            self::STATUS_EM_PROGRESSO => 'bg-primary',             // azul
            self::STATUS_FINALIZADO   => 'bg-dark',                // escuro
            self::STATUS_SUPERADO     => 'bg-warning text-dark',   // NOVO: dourado/amarelo
            default                   => 'bg-light text-dark'
        };
    }
    
    /**
     * Verifica se o status é 'iniciado'
     */
    public function isIniciado(): bool
    {
        return $this->status === self::STATUS_INICIADO;
    }
    
    /**
     * Verifica se o status é 'em_progresso'
     */
    public function isEmProgresso(): bool
    {
        return $this->status === self::STATUS_EM_PROGRESSO;
    }
    
    /**
     * Verifica se o status é 'finalizado'
     */
    public function isFinalizado(): bool
    {
        return $this->status === self::STATUS_FINALIZADO;
    }
    
    /**
     * NOVO: Verifica se o status é 'superado'
     */
    public function isSuperado(): bool
    {
        return $this->status === self::STATUS_SUPERADO;
    }
    
    /**
     * NOVO: Verifica se a porcentagem atingida qualifica como "superado"
     * 
     * Diferença para isSuperado():
     * - isSuperado() verifica o STATUS atual no banco
     * - qualificaParaSuperado() verifica se o VALOR NUMÉRICO qualifica
     * 
     * Útil para decidir se deve transicionar o status.
     * 
     * @return bool True se porcentagem >= THRESHOLD_SUPERADO (120%)
     */
    public function qualificaParaSuperado(): bool
    {
        return $this->porcentagem_atingida >= self::THRESHOLD_SUPERADO;
    }
    
    // ========================================
    // MÉTODOS CALCULADOS (existentes)
    // ========================================
    
    /**
     * Retorna o nome completo do mês/ano (ex: "Janeiro 2026")
     */
    public function getMesAnoFormatado(): string
    {
        if (!$this->mes_ano) return '';
        
        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
            4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
            7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
            10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        
        $mes = (int) date('n', strtotime($this->mes_ano));
        $ano = date('Y', strtotime($this->mes_ano));
        
        return ($meses[$mes] ?? '') . ' ' . $ano;
    }
    
    /**
     * Retorna mês/ano curto (Jan/25)
     */
    public function getMesAnoCurto(): string
    {
        if (!$this->mes_ano) return '';
        
        $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                  'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        
        $mes = (int)date('n', strtotime($this->mes_ano));
        $ano = date('y', strtotime($this->mes_ano));
        
        return $meses[$mes - 1] . '/' . $ano;
    }
    
    /**
     * Valor que falta para atingir a meta
     */
    public function getValorFaltante(): float
    {
        $faltante = $this->valor_meta - $this->valor_realizado;
        return max(0, $faltante);
    }
    
    /**
     * Verifica se meta foi atingida (100%+)
     */
    public function foiAtingida(): bool
    {
        return $this->porcentagem_atingida >= 100;
    }
    
    /**
     * Verifica se está no mês atual
     */
    public function isMesAtual(): bool
    {
        if (!$this->mes_ano) return false;
        return date('Y-m', strtotime($this->mes_ano)) === date('Y-m');
    }
    
    /**
     * Verifica se é um mês passado
     */
    public function isMesPassado(): bool
    {
        if (!$this->mes_ano) return false;
        return strtotime($this->mes_ano) < strtotime(date('Y-m-01'));
    }
    
    /**
     * Verifica se é um mês futuro
     */
    public function isMesFuturo(): bool
    {
        if (!$this->mes_ano) return false;
        return strtotime($this->mes_ano) > strtotime(date('Y-m-01'));
    }
    
    /**
     * Retorna classe CSS baseada no progresso
     */
    public function getProgressoClass(): string
    {
        if ($this->porcentagem_atingida >= 100) return 'success';
        if ($this->porcentagem_atingida >= 75) return 'info';
        if ($this->porcentagem_atingida >= 50) return 'warning';
        return 'danger';
    }
    
    /**
     * Calcula média diária necessária para atingir meta
     */
    public function getMediaDiariaNecessaria(): float
    {
        if (!$this->mes_ano) return 0;
        
        $ultimoDia = date('t', strtotime($this->mes_ano));
        $diaAtual = date('j');
        $diasRestantes = $ultimoDia - $diaAtual;
        
        if ($diasRestantes <= 0) return 0;
        
        $faltante = $this->getValorFaltante();
        return $faltante / $diasRestantes;
    }
    
    // ========================================
    // CONVERSÃO
    // ========================================
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mes_ano' => $this->mes_ano,
            'valor_meta' => $this->valor_meta,
            'horas_diarias_ideal' => $this->horas_diarias_ideal,
            'dias_trabalho_semana' => $this->dias_trabalho_semana,
            'valor_realizado' => $this->valor_realizado,
            'porcentagem_atingida' => $this->porcentagem_atingida,
            'status' => $this->status,
            'observacoes' => $this->observacoes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    
    public static function fromArray(array $data): self
    {
        $meta = new self();
        $meta->id = isset($data['id']) ? (int)$data['id'] : null;
        $meta->mes_ano = $data['mes_ano'] ?? null;
        $meta->valor_meta = (float)($data['valor_meta'] ?? 0);
        $meta->horas_diarias_ideal = (int)($data['horas_diarias_ideal'] ?? 8);
        $meta->dias_trabalho_semana = (int)($data['dias_trabalho_semana'] ?? 5);
        $meta->valor_realizado = (float)($data['valor_realizado'] ?? 0);
        $meta->porcentagem_atingida = (float)($data['porcentagem_atingida'] ?? 0);
        $meta->status = $data['status'] ?? self::STATUS_INICIADO;
        $meta->observacoes = $data['observacoes'] ?? null;
        $meta->created_at = $data['created_at'] ?? null;
        $meta->updated_at = $data['updated_at'] ?? null;
        return $meta;
    }
}