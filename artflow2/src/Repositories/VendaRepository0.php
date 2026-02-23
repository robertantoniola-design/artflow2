<?php
namespace App\Repositories;

use App\Models\Venda;
use App\Models\Arte;
use App\Models\Cliente;

/**
 * REPOSITORY: VENDAS
 */
class VendaRepository extends BaseRepository
{
    protected string $table = 'vendas';
    protected string $model = Venda::class;
    protected array $fillable = [
        'arte_id', 'cliente_id', 'valor', 'data_venda',
        'lucro_calculado', 'rentabilidade_hora',
        'forma_pagamento', 'observacoes'
    ];
    
    /**
     * Busca vendas com relacionamentos (arte e cliente)
     */
    public function allWithRelations(): array
    {
        $sql = "SELECT v.*,
                    a.nome as arte_nome, a.status as arte_status,
                    c.nome as cliente_nome, c.email as cliente_email
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                ORDER BY v.data_venda DESC";
        
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Hydrata com relacionamentos
        return array_map(function($row) {
            $venda = Venda::fromArray($row);
            
            if ($row['arte_id']) {
                $venda->setArte(Arte::fromArray([
                    'id' => $row['arte_id'],
                    'nome' => $row['arte_nome'],
                    'status' => $row['arte_status']
                ]));
            }
            
            if ($row['cliente_id']) {
                $venda->setCliente(Cliente::fromArray([
                    'id' => $row['cliente_id'],
                    'nome' => $row['cliente_nome'],
                    'email' => $row['cliente_email']
                ]));
            }
            
            return $venda;
        }, $rows);
    }
    
    /**
     * Busca venda por ID com relacionamentos
     */
    public function findWithRelations(int $id): ?Venda
    {
        $sql = "SELECT v.*,
                    a.id as arte_id, a.nome as arte_nome, a.status as arte_status,
                    a.preco_custo as arte_preco_custo, a.horas_trabalhadas as arte_horas,
                    c.id as cliente_id, c.nome as cliente_nome, c.email as cliente_email
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE v.id = ?";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        $venda = Venda::fromArray($row);
        
        if ($row['arte_id']) {
            $venda->setArte(Arte::fromArray([
                'id' => $row['arte_id'],
                'nome' => $row['arte_nome'],
                'status' => $row['arte_status'],
                'preco_custo' => $row['arte_preco_custo'],
                'horas_trabalhadas' => $row['arte_horas']
            ]));
        }
        
        if ($row['cliente_id']) {
            $venda->setCliente(Cliente::fromArray([
                'id' => $row['cliente_id'],
                'nome' => $row['cliente_nome'],
                'email' => $row['cliente_email']
            ]));
        }
        
        return $venda;
    }
    
    /**
     * Busca vendas por período
     */
    public function findByPeriodo(string $dataInicio, string $dataFim): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE data_venda BETWEEN :inicio AND :fim
                ORDER BY data_venda DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['inicio' => $dataInicio, 'fim' => $dataFim]);
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca vendas do mês/ano
     */
    public function findByMesAno(int $ano, int $mes): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE YEAR(data_venda) = :ano AND MONTH(data_venda) = :mes
                ORDER BY data_venda DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca vendas do mês (formato YYYY-MM)
     */
    public function findByMes(string $mesAno): array
    {
        [$ano, $mes] = explode('-', $mesAno);
        return $this->findByMesAno((int)$ano, (int)$mes);
    }
    
    /**
     * Total de vendas do mês (formato YYYY-MM)
     */
    public function getTotalVendasMes(string $mesAno): float
    {
        [$ano, $mes] = explode('-', $mesAno);
        return $this->somaVendasMes((int)$ano, (int)$mes);
    }
    
    /**
     * Soma vendas do mês
     */
    public function somaVendasMes(int $ano, int $mes): float
    {
        $sql = "SELECT COALESCE(SUM(valor), 0) FROM {$this->table}
                WHERE YEAR(data_venda) = :ano AND MONTH(data_venda) = :mes";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Estatísticas gerais de vendas
     */
    public function getEstatisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_vendas,
                    COALESCE(SUM(valor), 0) as valor_total,
                    COALESCE(AVG(valor), 0) as ticket_medio,
                    COALESCE(SUM(lucro_calculado), 0) as lucro_total,
                    COALESCE(AVG(rentabilidade_hora), 0) as rentabilidade_media
                FROM {$this->table}";
        
        return $this->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Vendas agrupadas por mês (para gráficos)
     */
    public function vendasPorMes(int $meses = 12): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(data_venda, '%Y-%m') as mes,
                    COUNT(*) as quantidade,
                    SUM(valor) as total,
                    SUM(lucro_calculado) as lucro
                FROM {$this->table}
                WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                GROUP BY DATE_FORMAT(data_venda, '%Y-%m')
                ORDER BY mes ASC";
        
        return $this->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Vendas por cliente
     */
    public function vendasPorCliente(): array
    {
        $sql = "SELECT 
                    c.id, c.nome,
                    COUNT(v.id) as total_vendas,
                    SUM(v.valor) as valor_total
                FROM vendas v
                INNER JOIN clientes c ON v.cliente_id = c.id
                GROUP BY c.id
                ORDER BY valor_total DESC";
        
        return $this->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Paginação com filtros
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['cliente_id'])) {
            $where[] = "v.cliente_id = :cliente_id";
            $params['cliente_id'] = $filters['cliente_id'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $where[] = "v.data_venda >= :data_inicio";
            $params['data_inicio'] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $where[] = "v.data_venda <= :data_fim";
            $params['data_fim'] = $filters['data_fim'];
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // Total
        $stmt = $this->getConnection()->prepare(
            "SELECT COUNT(*) FROM {$this->table} v {$whereClause}"
        );
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT v.*, a.nome as arte_nome, c.nome as cliente_nome
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                {$whereClause}
                ORDER BY v.data_venda DESC
                LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return [
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page
        ];
    }
    
    /**
     * Busca vendas por cliente
     */
    public function findByCliente(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE cliente_id = :cliente_id
                ORDER BY data_venda DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['cliente_id' => $clienteId]);
        
        return $this->hydrateMany($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Busca vendas recentes
     */
    public function getRecentes(int $limit = 10): array
    {
        $sql = "SELECT v.*, a.nome as arte_nome, c.nome as cliente_nome
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                ORDER BY v.data_venda DESC, v.created_at DESC
                LIMIT {$limit}";
        
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Vendas agrupadas por mês (alias para vendasPorMes)
     */
    public function getVendasPorMes(int $meses = 12): array
    {
        return $this->vendasPorMes($meses);
    }
    
    /**
     * Vendas mais rentáveis
     */
    public function getMaisRentaveis(int $limit = 10): array
    {
        $sql = "SELECT v.*, a.nome as arte_nome, c.nome as cliente_nome
                FROM {$this->table} v
                LEFT JOIN artes a ON v.arte_id = a.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE v.rentabilidade_hora IS NOT NULL AND v.rentabilidade_hora > 0
                ORDER BY v.rentabilidade_hora DESC
                LIMIT {$limit}";
        
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
