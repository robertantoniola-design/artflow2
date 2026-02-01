<?php

namespace App\Repositories;

use App\Models\Meta;
use App\Core\Database;
use PDO;

/**
 * ============================================
 * META REPOSITORY
 * ============================================
 * 
 * Gerencia acesso a dados de metas mensais.
 * 
 * CORREÇÃO (29/01/2026):
 * - Método atualizarProgresso() agora retorna bool corretamente
 * 
 * ATUALIZAÇÃO (01/02/2026):
 * - Adicionado 'status' ao fillable
 * - Novo método getAnosComMetas(): retorna anos que possuem metas no banco
 * - Novo método atualizarStatus(): atualiza o campo status de uma meta
 * - Novo método finalizarMetasPassadas(): marca metas de meses anteriores
 * 
 * MELHORIA 1 — Status "Superado" (01/02/2026):
 * - incrementarRealizado(): detecta transição para 'superado' (>= 120%)
 * - decrementarRealizado(): reverte de 'superado' se cair abaixo de 120%
 * - finalizarMetasPassadas(): usa 'superado' em vez de 'finalizado' para >= 120%
 */
class MetaRepository extends BaseRepository
{
    protected string $table = 'metas';
    protected string $model = Meta::class;
    protected array $fillable = [
        'mes_ano',
        'valor_meta',
        'horas_diarias_ideal',
        'dias_trabalho_semana',
        'valor_realizado',
        'porcentagem_atingida',
        'status'
    ];
    
    // ==========================================
    // BUSCAS ESPECÍFICAS
    // ==========================================
    
    /**
     * Busca meta por mês/ano
     */
    public function findByMesAno(string $mesAno): ?Meta
    {
        if (strlen($mesAno) === 7) {
            $mesAno .= '-01';
        }
        return $this->findFirstBy('mes_ano', $mesAno);
    }
    
    /**
     * Busca meta do mês atual
     */
    public function findMesAtual(): ?Meta
    {
        return $this->findByMesAno(date('Y-m-01'));
    }
    
    /**
     * Busca meta do mês anterior
     */
    public function findMesAnterior(): ?Meta
    {
        return $this->findByMesAno(date('Y-m-01', strtotime('-1 month')));
    }
    
    /**
     * Lista metas de um ano específico
     */
    public function findByAno(int $ano): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE YEAR(mes_ano) = :ano 
                ORDER BY mes_ano ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['ano' => $ano]);
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Lista últimas N metas
     */
    public function getRecentes(int $limit = 12): array
    {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY mes_ano DESC 
                LIMIT :limit";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // ==========================================
    // ANOS COM METAS
    // ==========================================
    
    /**
     * Retorna lista de anos que possuem metas cadastradas no banco
     * 
     * Usado para gerar as abas/pills de navegação por ano.
     * Sempre inclui o ano atual, mesmo sem metas.
     * Resultado ordenado do mais recente ao mais antigo.
     * 
     * @return array Ex: [2026, 2025] — anos com metas + ano atual
     */
    public function getAnosComMetas(): array
    {
        $sql = "SELECT DISTINCT YEAR(mes_ano) as ano 
                FROM {$this->table} 
                ORDER BY ano DESC";
        
        $stmt = $this->getConnection()->query($sql);
        $anos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Garante que o ano atual sempre esteja presente
        $anoAtual = (int) date('Y');
        if (!in_array($anoAtual, $anos)) {
            $anos[] = $anoAtual;
        }
        
        // Converte para inteiros e ordena decrescente
        $anos = array_map('intval', $anos);
        rsort($anos);
        
        return $anos;
    }
    
    // ==========================================
    // ATUALIZAÇÕES DE STATUS
    // ==========================================
    
    /**
     * Atualiza o status de uma meta específica
     * 
     * @param int $id ID da meta
     * @param string $status Novo status ('iniciado', 'em_progresso', 'finalizado', 'superado')
     * @return bool True se atualizou com sucesso
     */
    public function atualizarStatus(int $id, string $status): bool
    {
        // Valida status antes de executar
        if (!in_array($status, Meta::STATUS_VALIDOS)) {
            return false;
        }
        
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = :status, updated_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                'status' => $status,
                'id' => $id
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    /**
     * Finaliza automaticamente todas as metas de meses passados
     * 
     * ATUALIZADO (Melhoria 1 — Superado):
     * Agora diferencia entre 'finalizado' e 'superado':
     * - Se porcentagem >= 120% → marca como 'superado' (destaque permanente)
     * - Se porcentagem < 120%  → marca como 'finalizado' (encerramento normal)
     * 
     * Não altera metas que já estão como 'superado' (protege status conquistado)
     * 
     * @return int Quantidade de metas atualizadas
     */
    public function finalizarMetasPassadas(): int
    {
        $mesAtual = date('Y-m-01');
        $threshold = Meta::THRESHOLD_SUPERADO;
        $totalAtualizadas = 0;
        
        try {
            // PASSO 1: Metas passadas com porcentagem >= 120% → 'superado'
            // Não toca em metas que já estão 'finalizado' ou 'superado'
            // (evita reprocessamento desnecessário)
            $sql = "UPDATE {$this->table} 
                    SET status = 'superado', updated_at = NOW() 
                    WHERE mes_ano < :mes_atual 
                    AND status NOT IN ('finalizado', 'superado')
                    AND porcentagem_atingida >= :threshold";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                'mes_atual' => $mesAtual,
                'threshold' => $threshold
            ]);
            $totalAtualizadas += $stmt->rowCount();
            
            // PASSO 2: Metas passadas com porcentagem < 120% → 'finalizado'
            // Apenas as que ainda estão 'iniciado' ou 'em_progresso'
            $sql = "UPDATE {$this->table} 
                    SET status = 'finalizado', updated_at = NOW() 
                    WHERE mes_ano < :mes_atual 
                    AND status NOT IN ('finalizado', 'superado')
                    AND porcentagem_atingida < :threshold";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                'mes_atual' => $mesAtual,
                'threshold' => $threshold
            ]);
            $totalAtualizadas += $stmt->rowCount();
            
            return $totalAtualizadas;
        } catch (\PDOException $e) {
            return 0;
        }
    }
    
    // ==========================================
    // ATUALIZAÇÕES DE PROGRESSO
    // ==========================================
    
    /**
     * Atualiza valor realizado e recalcula porcentagem
     * 
     * CORREÇÃO: Retorna bool explicitamente, não o resultado de update()
     * O método update() do BaseRepository retorna o objeto Meta,
     * mas a assinatura deste método promete retornar bool.
     * 
     * @param int $id ID da meta
     * @param float $valorRealizado Novo valor total realizado
     * @return bool
     */
    public function atualizarProgresso(int $id, float $valorRealizado): bool
    {
        $meta = $this->find($id);
        if (!$meta) {
            return false;
        }
        
        $porcentagem = $meta->getValorMeta() > 0 
            ? ($valorRealizado / $meta->getValorMeta()) * 100 
            : 0;
        
        try {
            $sql = "UPDATE {$this->table} 
                    SET valor_realizado = :valor, 
                        porcentagem_atingida = :porcentagem,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                'valor' => round($valorRealizado, 2),
                'porcentagem' => round($porcentagem, 2),
                'id' => $id
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    /**
     * Incrementa valor realizado (adiciona venda à meta)
     * 
     * ATUALIZADO (Melhoria 1 — Superado):
     * Agora detecta transição para 'superado' quando porcentagem cruza 120%:
     * 
     * Fluxo de transição ao incrementar:
     * 1. 'iniciado' → 'em_progresso'  (primeira venda)
     * 2. 'em_progresso' → 'superado'  (porcentagem >= 120%)
     * 
     * Não transiciona se já está 'finalizado' (proteção de meses passados)
     * 
     * @param string $mesAno Formato YYYY-MM ou YYYY-MM-DD
     * @param float $valor Valor a incrementar
     * @return bool
     */
    public function incrementarRealizado(string $mesAno, float $valor): bool
    {
        $meta = $this->findByMesAno($mesAno);
        if (!$meta) {
            return false;
        }
        
        $novoValor = $meta->getValorRealizado() + $valor;
        $resultado = $this->atualizarProgresso($meta->getId(), $novoValor);
        
        if (!$resultado) {
            return false;
        }
        
        // Recarrega meta para obter porcentagem atualizada
        $metaAtualizada = $this->find($meta->getId());
        if (!$metaAtualizada) {
            return $resultado;
        }
        
        // Não altera metas já finalizadas (proteção de meses passados com venda retroativa)
        if ($metaAtualizada->isFinalizado()) {
            return $resultado;
        }
        
        // TRANSIÇÃO 1: 'iniciado' → 'em_progresso' (primeira venda)
        if ($metaAtualizada->isIniciado()) {
            $this->atualizarStatus($metaAtualizada->getId(), Meta::STATUS_EM_PROGRESSO);
            
            // Após transicionar para 'em_progresso', verifica se já qualifica para 'superado'
            // (caso raro: venda grande que já ultrapassa 120% de uma vez)
            if ($metaAtualizada->qualificaParaSuperado()) {
                $this->atualizarStatus($metaAtualizada->getId(), Meta::STATUS_SUPERADO);
            }
            
            return $resultado;
        }
        
        // TRANSIÇÃO 2: 'em_progresso' → 'superado' (cruzou 120%)
        if ($metaAtualizada->isEmProgresso() && $metaAtualizada->qualificaParaSuperado()) {
            $this->atualizarStatus($metaAtualizada->getId(), Meta::STATUS_SUPERADO);
        }
        
        return $resultado;
    }
    
    /**
     * Decrementa valor realizado (remove venda da meta)
     * 
     * ATUALIZADO (Melhoria 1 — Superado):
     * Agora detecta reversão de 'superado' quando porcentagem cai abaixo de 120%:
     * 
     * Fluxo de reversão ao decrementar:
     * 1. 'superado' → 'em_progresso'  (porcentagem caiu abaixo de 120%)
     * 2. 'em_progresso' → 'iniciado'  (valor voltou a zero)
     * 
     * Não reverte se status é 'finalizado' (proteção de meses passados)
     * 
     * @param string $mesAno Formato YYYY-MM ou YYYY-MM-DD
     * @param float $valor Valor a decrementar
     * @return bool
     */
    public function decrementarRealizado(string $mesAno, float $valor): bool
    {
        $meta = $this->findByMesAno($mesAno);
        if (!$meta) {
            return false;
        }
        
        $novoValor = max(0, $meta->getValorRealizado() - $valor);
        $resultado = $this->atualizarProgresso($meta->getId(), $novoValor);
        
        if (!$resultado) {
            return $resultado;
        }
        
        // Recarrega meta com valores atualizados
        $metaAtualizada = $this->find($meta->getId());
        if (!$metaAtualizada) {
            return $resultado;
        }
        
        // Não altera metas finalizadas
        if ($metaAtualizada->isFinalizado()) {
            return $resultado;
        }
        
        // REVERSÃO 1: 'superado' → 'em_progresso' (caiu abaixo de 120%)
        if ($metaAtualizada->isSuperado() && !$metaAtualizada->qualificaParaSuperado()) {
            $this->atualizarStatus($metaAtualizada->getId(), Meta::STATUS_EM_PROGRESSO);
        }
        
        // REVERSÃO 2: 'em_progresso' → 'iniciado' (valor voltou a zero)
        // Verifica APÓS eventual reversão de superado para em_progresso
        $metaRecheck = $this->find($meta->getId());
        if ($metaRecheck && $novoValor == 0 && $metaRecheck->isEmProgresso()) {
            $this->atualizarStatus($metaRecheck->getId(), Meta::STATUS_INICIADO);
        }
        
        return $resultado;
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
    /**
     * Retorna estatísticas gerais de metas
     */
    public function getEstatisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_metas,
                    SUM(CASE WHEN porcentagem_atingida >= 100 THEN 1 ELSE 0 END) as metas_atingidas,
                    SUM(CASE WHEN porcentagem_atingida < 100 THEN 1 ELSE 0 END) as metas_nao_atingidas,
                    AVG(porcentagem_atingida) as media_porcentagem,
                    SUM(valor_meta) as soma_metas,
                    SUM(valor_realizado) as soma_realizado
                FROM {$this->table}";
        
        $result = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_metas' => (int) ($result['total_metas'] ?? 0),
            'metas_atingidas' => (int) ($result['metas_atingidas'] ?? 0),
            'metas_nao_atingidas' => (int) ($result['metas_nao_atingidas'] ?? 0),
            'media_porcentagem' => round((float) ($result['media_porcentagem'] ?? 0), 2),
            'soma_metas' => (float) ($result['soma_metas'] ?? 0),
            'soma_realizado' => (float) ($result['soma_realizado'] ?? 0),
            'taxa_sucesso' => ($result['total_metas'] ?? 0) > 0 
                ? round(($result['metas_atingidas'] / $result['total_metas']) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * Retorna desempenho mensal para gráfico
     */
    public function getDesempenhoMensal(int $meses = 12): array
    {
        $sql = "SELECT 
                    mes_ano,
                    valor_meta,
                    valor_realizado,
                    porcentagem_atingida,
                    status
                FROM {$this->table}
                ORDER BY mes_ano DESC
                LIMIT :meses";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':meses', $meses, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Verifica se existe meta para determinado mês/ano
     */
    public function existsMesAno(string $mesAno): bool
    {
        if (strlen($mesAno) === 7) {
            $mesAno .= '-01';
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE mes_ano = :mes_ano";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['mes_ano' => $mesAno]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Cria meta se não existir, ou retorna existente
     */
    public function findOrCreate(string $mesAno, array $dados = []): Meta
    {
        $meta = $this->findByMesAno($mesAno);
        
        if ($meta) {
            return $meta;
        }
        
        if (strlen($mesAno) === 7) {
            $mesAno .= '-01';
        }
        
        $dadosPadrao = [
            'mes_ano' => $mesAno,
            'valor_meta' => $dados['valor_meta'] ?? 0,
            'horas_diarias_ideal' => $dados['horas_diarias_ideal'] ?? 8,
            'dias_trabalho_semana' => $dados['dias_trabalho_semana'] ?? 5,
            'valor_realizado' => 0,
            'porcentagem_atingida' => 0,
            'status' => Meta::STATUS_INICIADO
        ];
        
        return $this->create(array_merge($dadosPadrao, $dados));
    }
}