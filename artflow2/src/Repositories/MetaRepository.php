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
 * - update() do BaseRepository retorna objeto, então convertemos para bool
 * 
 * MELHORIA 2 + 3 (05/02/2026):
 * - Adicionado getEstatisticasAno() — estatísticas agregadas por ano
 * - Adicionado getDesempenhoAnual() — dados mensais para gráfico Chart.js
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
        'porcentagem_atingida'
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
     * @param float $valorRealizado Novo valor realizado
     * @return bool True se atualizou com sucesso
     */
    public function atualizarProgresso(int $id, float $valorRealizado): bool
    {
        $meta = $this->find($id);
        if (!$meta) {
            return false;
        }
        
        // Calcula porcentagem
        $porcentagem = $meta->getValorMeta() > 0 
            ? ($valorRealizado / $meta->getValorMeta()) * 100 
            : 0;
        
        // CORREÇÃO: Usa SQL direto para garantir retorno bool
        // Isso evita conflito com o return type do update() herdado
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
     */
    public function incrementarRealizado(string $mesAno, float $valor): bool
    {
        $meta = $this->findByMesAno($mesAno);
        if (!$meta) {
            return false;
        }
        
        $novoValor = $meta->getValorRealizado() + $valor;
        return $this->atualizarProgresso($meta->getId(), $novoValor);
    }
    
    /**
     * Decrementa valor realizado (remove venda da meta)
     */
    public function decrementarRealizado(string $mesAno, float $valor): bool
    {
        $meta = $this->findByMesAno($mesAno);
        if (!$meta) {
            return false;
        }
        
        $novoValor = max(0, $meta->getValorRealizado() - $valor);
        return $this->atualizarProgresso($meta->getId(), $novoValor);
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
    /**
     * Retorna estatísticas gerais de metas (sem filtro de ano)
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
     * =====================================================
     * MELHORIA 2: Estatísticas agregadas de um ano específico
     * =====================================================
     * 
     * Retorna totais, médias e taxas de sucesso para o ano selecionado.
     * Usado para exibir cards de resumo acima da listagem.
     * 
     * Diferença do getEstatisticas():
     * - getEstatisticas() → dados GERAIS (todas as metas)
     * - getEstatisticasAno() → dados FILTRADOS por ano
     * 
     * @param int $ano Ano para filtrar (ex: 2025, 2026)
     * @return array Estatísticas do ano com chaves:
     *   total_metas, metas_atingidas, metas_superadas, metas_nao_atingidas,
     *   media_porcentagem, soma_metas, soma_realizado, taxa_sucesso
     */
    public function getEstatisticasAno(int $ano): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_metas,
                    SUM(CASE WHEN porcentagem_atingida >= 100 THEN 1 ELSE 0 END) as metas_atingidas,
                    SUM(CASE WHEN porcentagem_atingida >= 120 THEN 1 ELSE 0 END) as metas_superadas,
                    SUM(CASE WHEN porcentagem_atingida < 100 THEN 1 ELSE 0 END) as metas_nao_atingidas,
                    COALESCE(AVG(porcentagem_atingida), 0) as media_porcentagem,
                    COALESCE(SUM(valor_meta), 0) as soma_metas,
                    COALESCE(SUM(valor_realizado), 0) as soma_realizado
                FROM {$this->table}
                WHERE YEAR(mes_ano) = :ano";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['ano' => $ano]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Tipagem segura — evita erros se não houver metas no ano
        $totalMetas = (int) ($resultado['total_metas'] ?? 0);
        $metasAtingidas = (int) ($resultado['metas_atingidas'] ?? 0);
        
        return [
            'total_metas'        => $totalMetas,
            'metas_atingidas'    => $metasAtingidas,
            'metas_superadas'    => (int) ($resultado['metas_superadas'] ?? 0),
            'metas_nao_atingidas'=> (int) ($resultado['metas_nao_atingidas'] ?? 0),
            'media_porcentagem'  => round((float) ($resultado['media_porcentagem'] ?? 0), 1),
            'soma_metas'         => (float) ($resultado['soma_metas'] ?? 0),
            'soma_realizado'     => (float) ($resultado['soma_realizado'] ?? 0),
            // Taxa de sucesso: % de metas que atingiram >= 100%
            'taxa_sucesso'       => $totalMetas > 0 
                ? round(($metasAtingidas / $totalMetas) * 100, 1)
                : 0
        ];
    }
    
    /**
     * =====================================================
     * MELHORIA 3: Desempenho mensal de um ano para gráfico
     * =====================================================
     * 
     * Retorna array de 12 posições (Jan–Dez), preenchendo meses
     * sem meta com null. Usado pelo Chart.js para gráfico de barras.
     * 
     * Diferença do getDesempenhoMensal():
     * - getDesempenhoMensal() → últimos N meses (sem preencher lacunas)
     * - getDesempenhoAnual() → 12 meses fixos de um ano (preenche lacunas com null)
     * 
     * @param int $ano Ano para filtrar
     * @return array Array de 12 posições com chaves:
     *   mes, nome_mes, valor_meta, valor_realizado, porcentagem, status
     */
    public function getDesempenhoAnual(int $ano): array
    {
        // Busca todas as metas do ano
        $sql = "SELECT 
                    MONTH(mes_ano) as mes,
                    valor_meta,
                    valor_realizado,
                    porcentagem_atingida,
                    status
                FROM {$this->table}
                WHERE YEAR(mes_ano) = :ano
                ORDER BY mes_ano ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['ano' => $ano]);
        $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Indexa por número do mês para acesso rápido
        $metasPorMes = [];
        foreach ($metas as $meta) {
            $metasPorMes[(int)$meta['mes']] = $meta;
        }
        
        // Nomes abreviados dos meses em PT-BR
        $nomesMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                       'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        
        // Monta array de 12 meses — meses sem meta ficam com null
        $resultado = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $resultado[] = [
                'mes'             => $mes,
                'nome_mes'        => $nomesMeses[$mes - 1],
                'valor_meta'      => isset($metasPorMes[$mes]) ? (float) $metasPorMes[$mes]['valor_meta'] : null,
                'valor_realizado' => isset($metasPorMes[$mes]) ? (float) $metasPorMes[$mes]['valor_realizado'] : null,
                'porcentagem'     => isset($metasPorMes[$mes]) ? (float) $metasPorMes[$mes]['porcentagem_atingida'] : null,
                'status'          => $metasPorMes[$mes]['status'] ?? null
            ];
        }
        
        return $resultado;
    }
    
    /**
     * Retorna desempenho mensal para gráfico (últimos N meses)
     */
    public function getDesempenhoMensal(int $meses = 12): array
    {
        $sql = "SELECT 
                    mes_ano,
                    valor_meta,
                    valor_realizado,
                    porcentagem_atingida
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
            'porcentagem_atingida' => 0
        ];
        
        return $this->create(array_merge($dadosPadrao, $dados));
    }
}