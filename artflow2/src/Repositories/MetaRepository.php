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
 * 
 * MELHORIA 6 (06/02/2026):
 * - Adicionado registrarTransicao() — grava log de mudança de status
 * - Adicionado gerarObservacaoTransicao() — texto descritivo automático
 * - Adicionado atualizarStatus() — atualiza status com log de transição
 * - Modificado atualizarProgresso() — agora faz transição automática de status + log
 * - Adicionado getHistoricoTransicoes() — busca timeline de transições
 * - Adicionado finalizarMetasPassadas() — finaliza metas de meses anteriores
 * - Adicionado getAnosComMetas() — lista anos disponíveis para filtro
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
    
    /**
     * Retorna anos que possuem metas cadastradas
     * Usado para popular o filtro de anos na view
     * 
     * @return array Lista de anos (ex: [2025, 2026])
     */
    public function getAnosComMetas(): array
    {
        $sql = "SELECT DISTINCT YEAR(mes_ano) as ano 
                FROM {$this->table} 
                ORDER BY ano DESC";
        
        $stmt = $this->getConnection()->query($sql);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // ==========================================
    // LOG DE TRANSIÇÕES DE STATUS (Melhoria 6)
    // ==========================================
    
    /**
     * Registra transição de status no log (Melhoria 6)
     * 
     * Grava um snapshot no momento da mudança de status:
     * - Status anterior e novo
     * - Porcentagem e valor realizado naquele instante
     * - Observação descritiva da transição
     * 
     * SEGURANÇA: Try/catch silencioso — falha no log NÃO bloqueia 
     * a operação principal. O registro é secundário.
     * 
     * @param int $metaId ID da meta
     * @param string|null $statusAnterior Status antes (NULL = criação inicial)
     * @param string $statusNovo Status para o qual está mudando
     * @param float|null $porcentagem Porcentagem atingida no momento
     * @param float|null $valorRealizado Valor realizado no momento
     * @param string|null $observacao Descrição da transição
     */
    private function registrarTransicao(
        int $metaId, 
        ?string $statusAnterior, 
        string $statusNovo, 
        ?float $porcentagem = null, 
        ?float $valorRealizado = null, 
        ?string $observacao = null
    ): void {
        // Só registra se houve mudança real de status
        // (evita logs duplicados quando status não muda)
        if ($statusAnterior === $statusNovo) {
            return;
        }
        
        try {
            $sql = "INSERT INTO meta_status_log 
                    (meta_id, status_anterior, status_novo, porcentagem_momento, 
                     valor_realizado_momento, observacao, created_at)
                    VALUES 
                    (:meta_id, :status_anterior, :status_novo, :porcentagem, 
                     :valor_realizado, :observacao, NOW())";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                'meta_id'          => $metaId,
                'status_anterior'  => $statusAnterior,
                'status_novo'      => $statusNovo,
                'porcentagem'      => $porcentagem !== null ? round($porcentagem, 2) : null,
                'valor_realizado'  => $valorRealizado !== null ? round($valorRealizado, 2) : null,
                'observacao'       => $observacao
            ]);
        } catch (\PDOException $e) {
            // Log silencioso — falha aqui NÃO deve impedir a operação principal
            error_log("Erro ao registrar transição de status (meta_id={$metaId}): " . $e->getMessage());
        }
    }
    
    /**
     * Gera observação automática baseada no tipo de transição (Melhoria 6)
     * 
     * Produz textos descritivos para o histórico, facilitando 
     * a leitura na timeline da view show.php.
     * 
     * @param string|null $de Status anterior
     * @param string $para Status novo
     * @return string Descrição legível
     */
    private function gerarObservacaoTransicao(?string $de, string $para): string
    {
        // Criação inicial
        if ($de === null) {
            return 'Meta criada';
        }
        
        // Mapeamento de transições conhecidas → descrições amigáveis
        $transicoes = [
            'iniciado_em_progresso'     => 'Primeira venda registrada no mês',
            'em_progresso_finalizado'   => 'Mês encerrado — meta finalizada automaticamente',
            'em_progresso_superado'     => 'Meta superada! Ultrapassou 120% de realização',
            'iniciado_finalizado'       => 'Mês encerrado sem vendas registradas',
            'iniciado_superado'         => 'Meta superada diretamente (recálculo)',
        ];
        
        $chave = $de . '_' . $para;
        
        return $transicoes[$chave] ?? "Status alterado de '{$de}' para '{$para}'";
    }
    
    /**
     * Registra a criação inicial de uma meta no log (Melhoria 6)
     * 
     * Método PÚBLICO chamado pelo MetaService::criar() logo após
     * a criação da meta. Registra a transição null → 'iniciado'.
     * 
     * Separado do create() porque o BaseRepository::create() é genérico
     * e não deve ser sobrecarregado com lógica de log.
     * 
     * @param int $metaId ID da meta recém-criada
     */
    public function registrarCriacaoInicial(int $metaId): void
    {
        $this->registrarTransicao(
            $metaId,
            null,           // status_anterior: null = criação
            'iniciado',     // status_novo: toda meta nasce como 'iniciado'
            0,              // porcentagem: 0% ao criar
            0,              // valor_realizado: R$ 0,00 ao criar
            $this->gerarObservacaoTransicao(null, 'iniciado')
        );
    }
    
    // ==========================================
    // ATUALIZAÇÕES DE PROGRESSO E STATUS
    // ==========================================
    
    /**
     * Atualiza valor realizado, recalcula porcentagem E faz transição de status
     * 
     * Este é o método principal chamado por VendaService ao registrar/excluir vendas.
     * 
     * FLUXO COMPLETO:
     * 1. Busca meta atual
     * 2. Calcula nova porcentagem
     * 3. Determina se status deve mudar (Melhoria 1):
     *    - iniciado → em_progresso (primeira venda, valor > 0)
     *    - em_progresso → superado (porcentagem ≥ 120%)
     *    - superado permanece superado (nunca regride)
     * 4. Se status mudou, registra transição no log (Melhoria 6)
     * 5. Atualiza tudo no banco (valor, porcentagem, status)
     * 
     * @param int $id ID da meta
     * @param float $valorRealizado Novo valor realizado total
     * @return bool True se atualizou com sucesso
     */
    public function atualizarProgresso(int $id, float $valorRealizado): bool
    {
        // 1. Busca meta atual para calcular porcentagem e verificar status
        $meta = $this->find($id);
        if (!$meta) {
            return false;
        }
        
        // 2. Calcula porcentagem (evita divisão por zero)
        $porcentagem = $meta->getValorMeta() > 0 
            ? ($valorRealizado / $meta->getValorMeta()) * 100 
            : 0;
        
        // 3. Determina novo status baseado na porcentagem (Melhoria 1)
        $statusAtual = $meta->getStatus();
        $novoStatus = $statusAtual; // Assume sem mudança
        
        // iniciado → em_progresso: quando há valor realizado (primeira venda)
        if ($statusAtual === 'iniciado' && $valorRealizado > 0) {
            $novoStatus = 'em_progresso';
        }
        
        // em_progresso → superado: quando ultrapassa 120% (Melhoria 1)
        if ($statusAtual === 'em_progresso' && $porcentagem >= 120) {
            $novoStatus = 'superado';
        }
        
        // superado NUNCA regride — permanece superado
        // (não precisa de código, pois $novoStatus já mantém o valor)
        
        // 4. MELHORIA 6: Registra transição se houve mudança de status
        if ($novoStatus !== $statusAtual) {
            $this->registrarTransicao(
                $id,
                $statusAtual,
                $novoStatus,
                round($porcentagem, 2),
                round($valorRealizado, 2),
                $this->gerarObservacaoTransicao($statusAtual, $novoStatus)
            );
        }
        
        // 5. Atualiza tudo no banco: valor + porcentagem + status
        try {
            $sql = "UPDATE {$this->table} 
                    SET valor_realizado = :valor, 
                        porcentagem_atingida = :porcentagem,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                'valor'       => round($valorRealizado, 2),
                'porcentagem' => round($porcentagem, 2),
                'status'      => $novoStatus,
                'id'          => $id
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar progresso da meta {$id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza apenas o status de uma meta (Melhoria 6)
     * 
     * Busca a meta, registra a transição no log, depois atualiza.
     * Usado por finalizarMetasPassadas() e eventuais atualizações manuais.
     * 
     * @param int $id ID da meta
     * @param string $status Novo status ('iniciado', 'em_progresso', 'finalizado', 'superado')
     * @return bool True se atualizou
     */
    public function atualizarStatus(int $id, string $status): bool
    {
        // Busca meta atual para obter status anterior e dados do momento
        $meta = $this->find($id);
        if (!$meta) {
            return false;
        }
        
        $statusAnterior = $meta->getStatus();
        
        // MELHORIA 6: Registra a transição ANTES de atualizar
        // Assim temos o snapshot exato do momento da mudança
        $this->registrarTransicao(
            $id,
            $statusAnterior,
            $status,
            $meta->getPorcentagemAtingida(),
            $meta->getValorRealizado(),
            $this->gerarObservacaoTransicao($statusAnterior, $status)
        );
        
        // Atualiza o status no banco
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = :status, updated_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                'status' => $status,
                'id'     => $id
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar status da meta {$id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Finaliza metas de meses anteriores que ainda estão em andamento
     * 
     * Chamado automaticamente (ex: no login ou acesso ao dashboard).
     * Metas de meses passados com status 'iniciado' ou 'em_progresso'
     * são finalizadas. Metas 'superado' permanecem como estão.
     * 
     * Cada finalização é registrada no log de transições (Melhoria 6).
     */
    public function finalizarMetasPassadas(): void
    {
        $mesAtual = date('Y-m-01');
        
        // Busca metas de meses anteriores que ainda não foram finalizadas
        $sql = "SELECT * FROM {$this->table} 
                WHERE mes_ano < :mes_atual 
                AND status IN ('iniciado', 'em_progresso')
                ORDER BY mes_ano ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['mes_atual' => $mesAtual]);
        $metas = $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Finaliza cada uma individualmente (para registrar transição)
        foreach ($metas as $meta) {
            $this->atualizarStatus($meta->getId(), 'finalizado');
        }
    }
    
    /**
     * Retorna histórico de transições de status de uma meta (Melhoria 6)
     * 
     * Busca na tabela meta_status_log todos os registros de mudança
     * de status, ordenados do mais recente para o mais antigo.
     * 
     * Cada registro inclui labels e classes CSS formatados para 
     * exibição direta na timeline da view show.php.
     * 
     * @param int $metaId ID da meta
     * @return array Lista de transições com dados formatados
     */
    public function getHistoricoTransicoes(int $metaId): array
    {
        $sql = "SELECT 
                    id,
                    meta_id,
                    status_anterior,
                    status_novo,
                    porcentagem_momento,
                    valor_realizado_momento,
                    observacao,
                    created_at
                FROM meta_status_log 
                WHERE meta_id = :meta_id 
                ORDER BY created_at DESC, id DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['meta_id' => $metaId]);
        
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapas de labels e classes CSS — mesmos do Model Meta (Melhoria 1)
        $statusLabels = [
            'iniciado'      => 'Iniciado',
            'em_progresso'  => 'Em Progresso',
            'finalizado'    => 'Finalizado',
            'superado'      => 'Superado'
        ];
        
        $statusBadgeClass = [
            'iniciado'      => 'bg-secondary',
            'em_progresso'  => 'bg-primary',
            'finalizado'    => 'bg-success',
            'superado'      => 'bg-warning text-dark'
        ];
        
        $statusIcons = [
            'iniciado'      => 'bi-circle',
            'em_progresso'  => 'bi-play-circle-fill',
            'finalizado'    => 'bi-check-circle-fill',
            'superado'      => 'bi-trophy-fill'
        ];
        
        // Formata cada registro para exibição na timeline
        return array_map(function($registro) use ($statusLabels, $statusBadgeClass, $statusIcons) {
            return [
                'id'                     => (int) $registro['id'],
                'meta_id'                => (int) $registro['meta_id'],
                
                // Status anterior (pode ser null = criação)
                'status_anterior'        => $registro['status_anterior'],
                'status_anterior_label'  => $registro['status_anterior'] 
                    ? ($statusLabels[$registro['status_anterior']] ?? $registro['status_anterior'])
                    : null,
                'status_anterior_badge'  => $registro['status_anterior']
                    ? ($statusBadgeClass[$registro['status_anterior']] ?? 'bg-secondary')
                    : null,
                
                // Status novo (sempre presente)
                'status_novo'            => $registro['status_novo'],
                'status_novo_label'      => $statusLabels[$registro['status_novo']] ?? $registro['status_novo'],
                'status_novo_badge'      => $statusBadgeClass[$registro['status_novo']] ?? 'bg-secondary',
                'status_novo_icon'       => $statusIcons[$registro['status_novo']] ?? 'bi-circle',
                
                // Snapshot numérico do momento
                'porcentagem_momento'    => $registro['porcentagem_momento'] !== null 
                    ? (float) $registro['porcentagem_momento'] : null,
                'valor_realizado_momento' => $registro['valor_realizado_momento'] !== null 
                    ? (float) $registro['valor_realizado_momento'] : null,
                
                // Texto e data
                'observacao'             => $registro['observacao'],
                'created_at'             => $registro['created_at'],
                
                // Data formatada em PT-BR para exibição direta na view
                'data_formatada'         => date('d/m/Y \à\s H:i', strtotime($registro['created_at'])),
                
                // Flag: é criação inicial?
                'is_criacao'             => $registro['status_anterior'] === null
            ];
        }, $registros);
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
     * @return array Estatísticas do ano
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
     * @param int $ano Ano para filtrar
     * @return array Array de 12 posições
     */
    public function getDesempenhoAnual(int $ano): array
    {
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