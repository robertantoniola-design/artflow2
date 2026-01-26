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
 * Metas são objetivos financeiros por mês/ano.
 * 
 * Principais operações:
 * - CRUD padrão (herdado)
 * - Busca por mês/ano
 * - Atualização de progresso
 * - Estatísticas de desempenho
 */
class MetaRepository extends BaseRepository
{
    /**
     * Tabela do banco de dados
     */
    protected string $table = 'metas';
    
    /**
     * Classe do Model
     */
    protected string $model = Meta::class;
    
    /**
     * Campos permitidos para mass assignment
     */
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
     * 
     * @param string $mesAno Formato: 'YYYY-MM-01' ou 'YYYY-MM'
     * @return Meta|null
     */
    public function findByMesAno(string $mesAno): ?Meta
    {
        // Normaliza formato para YYYY-MM-01
        if (strlen($mesAno) === 7) {
            $mesAno .= '-01';
        }
        
        return $this->findFirstBy('mes_ano', $mesAno);
    }
    
    /**
     * Busca meta do mês atual
     * 
     * @return Meta|null
     */
    public function findMesAtual(): ?Meta
    {
        $mesAno = date('Y-m-01');
        return $this->findByMesAno($mesAno);
    }
    
    /**
     * Busca meta do mês anterior
     * 
     * @return Meta|null
     */
    public function findMesAnterior(): ?Meta
    {
        $mesAno = date('Y-m-01', strtotime('-1 month'));
        return $this->findByMesAno($mesAno);
    }
    
    /**
     * Lista metas de um ano específico
     * 
     * @param int $ano
     * @return array
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
     * Lista últimas N metas (mais recentes primeiro)
     * 
     * @param int $limit
     * @return array
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
     * @param int $id
     * @param float $valorRealizado
     * @return bool
     */
    public function atualizarProgresso(int $id, float $valorRealizado): bool
    {
        // Busca meta atual para calcular porcentagem
        $meta = $this->find($id);
        if (!$meta) {
            return false;
        }
        
        // Calcula porcentagem (evita divisão por zero)
        $porcentagem = $meta->getValorMeta() > 0 
            ? ($valorRealizado / $meta->getValorMeta()) * 100 
            : 0;
        
        return $this->update($id, [
            'valor_realizado' => $valorRealizado,
            'porcentagem_atingida' => round($porcentagem, 2)
        ]);
    }
    
    /**
     * Incrementa valor realizado (adiciona venda à meta)
     * 
     * @param string $mesAno
     * @param float $valor
     * @return bool
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
     * 
     * @param string $mesAno
     * @param float $valor
     * @return bool
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
    // ESTATÍSTICAS E ANÁLISES
    // ==========================================
    
    /**
     * Retorna estatísticas gerais de metas
     * 
     * @return array
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
            'total_metas' => (int) $result['total_metas'],
            'metas_atingidas' => (int) $result['metas_atingidas'],
            'metas_nao_atingidas' => (int) $result['metas_nao_atingidas'],
            'media_porcentagem' => round((float) $result['media_porcentagem'], 2),
            'soma_metas' => (float) $result['soma_metas'],
            'soma_realizado' => (float) $result['soma_realizado'],
            'taxa_sucesso' => $result['total_metas'] > 0 
                ? round(($result['metas_atingidas'] / $result['total_metas']) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * Retorna desempenho mensal para gráfico
     * 
     * @param int $meses Quantidade de meses
     * @return array
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
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Inverte para ordem cronológica (mais antigo primeiro)
        return array_reverse($resultados);
    }
    
    /**
     * Verifica se existe meta para determinado mês/ano
     * 
     * @param string $mesAno
     * @return bool
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
     * 
     * @param string $mesAno
     * @param array $dados Dados padrão caso precise criar
     * @return Meta
     */
    public function findOrCreate(string $mesAno, array $dados = []): Meta
    {
        $meta = $this->findByMesAno($mesAno);
        
        if ($meta) {
            return $meta;
        }
        
        // Normaliza formato
        if (strlen($mesAno) === 7) {
            $mesAno .= '-01';
        }
        
        // Dados padrão se não fornecidos
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
