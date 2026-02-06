# ArtFlow 2.0 — Módulo Metas: Roadmap de Melhorias

**Data:** 05/02/2026  
**Status Geral:** Em desenvolvimento  
**Versão Base:** Sistema funcional com upgrade de status concluído

---

## 📋 RESUMO EXECUTIVO

O módulo de Metas do ArtFlow 2.0 gerencia metas mensais de faturamento, permitindo acompanhar progresso, projeções e histórico. Recentemente foi implementado um sistema de status com transições automáticas. Este documento detalha as 6 melhorias planejadas, sendo 1 já implementada e 5 pendentes.

---

## ✅ MELHORIA 1: STATUS "SUPERADO" — IMPLEMENTADA

### Descrição
Adiciona 4º status para metas que ultrapassam 120% de realização, oferecendo reconhecimento visual para super-performance.

### Arquivos Modificados

#### Migration: `database/migrations/012_add_status_superado.php`
```php
<?php
// Altera ENUM do campo status para incluir 'superado'
// ALTER TABLE metas MODIFY COLUMN status ENUM('iniciado', 'em_progresso', 'finalizado', 'superado')
// UPDATE metas SET status = 'superado' WHERE porcentagem_atingida >= 120 AND status = 'finalizado'
```

#### Model: `src/Models/Meta.php`
```php
// Constantes adicionadas:
public const STATUS_SUPERADO = 'superado';
public const STATUS_VALIDOS = ['iniciado', 'em_progresso', 'finalizado', 'superado'];

// Métodos adicionados/atualizados:
public function isSuperado(): bool
{
    return $this->status === self::STATUS_SUPERADO;
}

public function getStatusLabel(): string
{
    return match($this->status) {
        self::STATUS_INICIADO => 'Iniciado',
        self::STATUS_EM_PROGRESSO => 'Em Progresso',
        self::STATUS_FINALIZADO => 'Finalizado',
        self::STATUS_SUPERADO => 'Superado',
        default => 'Desconhecido'
    };
}

public function getStatusIcon(): string
{
    return match($this->status) {
        self::STATUS_INICIADO => 'bi-hourglass-start',
        self::STATUS_EM_PROGRESSO => 'bi-arrow-repeat',
        self::STATUS_FINALIZADO => 'bi-check-circle-fill',
        self::STATUS_SUPERADO => 'bi-trophy-fill',
        default => 'bi-question-circle'
    };
}

public function getStatusBadgeClass(): string
{
    return match($this->status) {
        self::STATUS_INICIADO => 'bg-secondary',
        self::STATUS_EM_PROGRESSO => 'bg-primary',
        self::STATUS_FINALIZADO => 'bg-success',
        self::STATUS_SUPERADO => 'bg-warning text-dark',
        default => 'bg-light text-dark'
    };
}
```

#### Repository: `src/Repositories/MetaRepository.php`
```php
// Método atualizarProgresso() atualizado:
// Se porcentagem >= 120% E mês atual/passado → status = 'superado'
// Lógica de transição:
// iniciado → em_progresso (primeira venda)
// em_progresso → superado (>=120%)
// superado permanece superado
// Ao virar o mês: em_progresso → finalizado (se <120%)
```

#### View: `views/metas/index.php`
```php
// Badge especial para status 'superado':
// Ícone de troféu (bi-trophy-fill)
// Cor dourada/laranja (bg-warning)
```

### Regras de Negócio Implementadas
1. **Threshold:** 120% de porcentagem_atingida ativa status "superado"
2. **Transição automática:** Ao registrar venda que ultrapassa 120%, status muda automaticamente
3. **Permanência:** Uma vez "superado", permanece superado mesmo se cair abaixo de 120%
4. **Visual:** Troféu dourado diferencia visualmente das outras categorias

### Status
✅ **IMPLEMENTADA E FUNCIONANDO**

---

## 🔄 MELHORIA 2: RESUMO ESTATÍSTICO POR ANO — PENDENTE

### Descrição
Cards acima da listagem mostrando totais e médias do ano selecionado.

### Especificação Técnica

#### Repository: `src/Repositories/MetaRepository.php`
```php
/**
 * Retorna estatísticas agregadas de um ano específico
 * 
 * @param int $ano Ano para filtrar (ex: 2025)
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
    
    $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    // Calcula taxa de sucesso
    $resultado['taxa_sucesso'] = $resultado['total_metas'] > 0 
        ? round(($resultado['metas_atingidas'] / $resultado['total_metas']) * 100, 1)
        : 0;
    
    return $resultado;
}
```

#### Service: `src/Services/MetaService.php`
```php
/**
 * Obtém estatísticas formatadas do ano
 */
public function getEstatisticasAno(int $ano): array
{
    return $this->metaRepository->getEstatisticasAno($ano);
}
```

#### Controller: `src/Controllers/MetaController.php`
```php
// No método index(), adicionar:
$estatisticasAno = $this->metaService->getEstatisticasAno($anoSelecionado);

// Passar para view:
$this->view('metas/index', [
    'metas' => $metas,
    'anoAtual' => $anoSelecionado,
    'anosDisponiveis' => $anosDisponiveis,
    'estatisticasAno' => $estatisticasAno  // NOVO
]);
```

#### View: `views/metas/index.php`
```php
<!-- Cards de Estatísticas do Ano (inserir ANTES da tabela de metas) -->
<div class="row mb-4">
    <!-- Card: Total de Metas -->
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Total de Metas</h6>
                <h3 class="card-title"><?= $estatisticasAno['total_metas'] ?></h3>
            </div>
        </div>
    </div>
    
    <!-- Card: Metas Atingidas -->
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Atingidas</h6>
                <h3 class="card-title text-success">
                    <?= $estatisticasAno['metas_atingidas'] ?>
                    <small class="fs-6">(<?= $estatisticasAno['taxa_sucesso'] ?>%)</small>
                </h3>
            </div>
        </div>
    </div>
    
    <!-- Card: Média de Realização -->
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Média Realização</h6>
                <h3 class="card-title"><?= number_format($estatisticasAno['media_porcentagem'], 1) ?>%</h3>
            </div>
        </div>
    </div>
    
    <!-- Card: Faturamento Total -->
    <div class="col-md-3">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Faturamento <?= $anoAtual ?></h6>
                <h3 class="card-title text-primary">
                    R$ <?= number_format($estatisticasAno['soma_realizado'], 2, ',', '.') ?>
                </h3>
                <small class="text-muted">
                    Meta: R$ <?= number_format($estatisticasAno['soma_metas'], 2, ',', '.') ?>
                </small>
            </div>
        </div>
    </div>
</div>
```

### Dependências
- Nenhuma migration necessária
- Usa estrutura existente do banco

### Estimativa
- **Complexidade:** Baixa
- **Arquivos:** 3 (Repository, Service, Controller) + 1 View
- **Tempo:** ~30 minutos

---

## 📊 MELHORIA 3: GRÁFICO DE EVOLUÇÃO ANUAL — PENDENTE

### Descrição
Gráfico de barras comparando meta vs realizado mês a mês usando Chart.js.

### Especificação Técnica

#### Repository: `src/Repositories/MetaRepository.php`
```php
/**
 * Retorna desempenho mensal de um ano para gráfico
 * Preenche meses sem meta com null
 * 
 * @param int $ano Ano para filtrar
 * @return array Array de 12 posições (jan-dez)
 */
public function getDesempenhoAnual(int $ano): array
{
    // Busca metas do ano
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
    $metas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    // Indexa por mês
    $metasPorMes = [];
    foreach ($metas as $meta) {
        $metasPorMes[(int)$meta['mes']] = $meta;
    }
    
    // Monta array de 12 meses
    $resultado = [];
    $nomesMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                   'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    
    for ($mes = 1; $mes <= 12; $mes++) {
        $resultado[] = [
            'mes' => $mes,
            'nome_mes' => $nomesMeses[$mes - 1],
            'valor_meta' => $metasPorMes[$mes]['valor_meta'] ?? null,
            'valor_realizado' => $metasPorMes[$mes]['valor_realizado'] ?? null,
            'porcentagem' => $metasPorMes[$mes]['porcentagem_atingida'] ?? null,
            'status' => $metasPorMes[$mes]['status'] ?? null
        ];
    }
    
    return $resultado;
}
```

#### Service: `src/Services/MetaService.php`
```php
/**
 * Obtém dados para gráfico de evolução anual
 */
public function getDesempenhoAnual(int $ano): array
{
    return $this->metaRepository->getDesempenhoAnual($ano);
}
```

#### Controller: `src/Controllers/MetaController.php`
```php
// No método index(), adicionar:
$desempenhoAnual = $this->metaService->getDesempenhoAnual($anoSelecionado);

// Passar para view:
$this->view('metas/index', [
    'metas' => $metas,
    'anoAtual' => $anoSelecionado,
    'anosDisponiveis' => $anosDisponiveis,
    'estatisticasAno' => $estatisticasAno,
    'desempenhoAnual' => $desempenhoAnual  // NOVO
]);
```

#### View: `views/metas/index.php`
```php
<!-- Gráfico de Evolução (inserir APÓS cards de estatísticas) -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Evolução <?= $anoAtual ?></h5>
    </div>
    <div class="card-body">
        <canvas id="graficoEvolucao" height="100"></canvas>
    </div>
</div>

<!-- Script Chart.js (inserir antes do </body>) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const desempenhoAnual = <?= json_encode($desempenhoAnual) ?>;

const ctx = document.getElementById('graficoEvolucao').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: desempenhoAnual.map(d => d.nome_mes),
        datasets: [
            {
                label: 'Meta',
                data: desempenhoAnual.map(d => d.valor_meta),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Realizado',
                data: desempenhoAnual.map(d => d.valor_realizado),
                backgroundColor: 'rgba(75, 192, 92, 0.6)',
                borderColor: 'rgba(75, 192, 92, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.raw;
                        if (value === null) return 'Sem meta';
                        return context.dataset.label + ': R$ ' + 
                               value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'R$ ' + value.toLocaleString('pt-BR');
                    }
                }
            }
        }
    }
});
</script>
```

### Dependências
- Chart.js (CDN ou já incluído no projeto)
- Melhoria 2 deve estar implementada (compartilham estrutura no Controller)

### Estimativa
- **Complexidade:** Baixa-Média
- **Arquivos:** 3 (Repository, Service, Controller) + 1 View
- **Tempo:** ~45 minutos

---

## ⚠️ MELHORIA 4: NOTIFICAÇÃO DE METAS EM RISCO — PENDENTE

### Descrição
Alerta no dashboard quando projeção indica que meta do mês atual não será batida.

### Especificação Técnica

#### Service: `src/Services/MetaService.php`
```php
/**
 * Verifica se meta atual está em risco de não ser batida
 * Usa calcularProjecao() existente
 * 
 * @return array Dados de risco ou alerta=false
 */
public function getMetasEmRisco(): array
{
    // Busca meta do mês atual
    $metaAtual = $this->buscarMesAtual();
    
    if (!$metaAtual) {
        return ['alerta' => false, 'motivo' => 'sem_meta'];
    }
    
    // Usa método existente de projeção
    $projecao = $this->calcularProjecao($metaAtual);
    
    // Se projeção indica que não vai bater
    if (!$projecao['vai_bater_meta']) {
        return [
            'alerta' => true,
            'meta' => [
                'id' => $metaAtual->getId(),
                'mes_ano' => $metaAtual->getMesAno(),
                'valor_meta' => $metaAtual->getValorMeta(),
                'valor_realizado' => $metaAtual->getValorRealizado(),
                'porcentagem_atingida' => $metaAtual->getPorcentagemAtingida()
            ],
            'projecao' => $projecao,
            'mensagem' => sprintf(
                'Meta em risco! Projeção: R$ %s (%.1f%%). Faltam R$ %s. Necessário: R$ %s/dia.',
                number_format($projecao['projecao_total'], 2, ',', '.'),
                $projecao['porcentagem_projetada'],
                number_format($projecao['falta_vender'], 2, ',', '.'),
                number_format($projecao['media_diaria_necessaria'], 2, ',', '.')
            )
        ];
    }
    
    return ['alerta' => false, 'motivo' => 'meta_ok'];
}
```

#### Controller: `src/Controllers/DashboardController.php`
```php
// No método index(), adicionar:
$metaEmRisco = $this->metaService->getMetasEmRisco();

// Passar para view:
$this->view('dashboard/index', [
    // ... dados existentes ...
    'metaEmRisco' => $metaEmRisco  // NOVO
]);
```

#### View: `views/dashboard/index.php`
```php
<!-- Alerta de Meta em Risco (inserir no topo do dashboard) -->
<?php if (isset($metaEmRisco) && $metaEmRisco['alerta']): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
            <strong>Meta em Risco!</strong>
            <p class="mb-0 mt-1"><?= htmlspecialchars($metaEmRisco['mensagem']) ?></p>
        </div>
        <a href="/metas/<?= $metaEmRisco['meta']['id'] ?>" class="btn btn-outline-danger btn-sm ms-auto">
            Ver Meta
        </a>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
```

### Dependências
- Método `calcularProjecao()` já existe no MetaService
- Método `buscarMesAtual()` já existe no MetaService

### Estimativa
- **Complexidade:** Baixa
- **Arquivos:** 1 (Service) + 1 Controller + 1 View
- **Tempo:** ~20 minutos

---

## 🔁 MELHORIA 5: META RECORRENTE — PENDENTE

### Descrição
Criar múltiplas metas de uma vez a partir do formulário de criação.

### Especificação Técnica

#### Service: `src/Services/MetaService.php`
```php
/**
 * Cria metas recorrentes para múltiplos meses
 * 
 * @param array $dados Dados base da meta
 * @param int $quantidadeMeses Quantidade de meses a criar (1-12)
 * @return array Array de metas criadas e erros
 */
public function criarRecorrente(array $dados, int $quantidadeMeses): array
{
    $resultado = [
        'criadas' => [],
        'ignoradas' => [],
        'erros' => []
    ];
    
    // Valida quantidade
    $quantidadeMeses = max(1, min(12, $quantidadeMeses));
    
    // Data inicial
    $mesInicial = new \DateTime($dados['mes_ano']);
    
    for ($i = 0; $i < $quantidadeMeses; $i++) {
        $mesAno = $mesInicial->format('Y-m-01');
        
        // Verifica se já existe meta para este mês
        $metaExistente = $this->metaRepository->findByMesAno($mesAno);
        
        if ($metaExistente) {
            $resultado['ignoradas'][] = [
                'mes_ano' => $mesAno,
                'motivo' => 'Já existe meta para este mês'
            ];
        } else {
            try {
                $dadosMeta = array_merge($dados, ['mes_ano' => $mesAno]);
                $meta = $this->criar($dadosMeta);
                $resultado['criadas'][] = $meta;
            } catch (\Exception $e) {
                $resultado['erros'][] = [
                    'mes_ano' => $mesAno,
                    'erro' => $e->getMessage()
                ];
            }
        }
        
        // Avança um mês
        $mesInicial->modify('+1 month');
    }
    
    return $resultado;
}
```

#### Controller: `src/Controllers/MetaController.php`
```php
// No método store(), modificar:
public function store(): void
{
    try {
        $dados = $this->getFormData();
        
        // Verifica se é criação recorrente
        $recorrente = isset($_POST['recorrente']) && $_POST['recorrente'] === '1';
        $quantidadeMeses = isset($_POST['quantidade_meses']) 
            ? (int)$_POST['quantidade_meses'] 
            : 1;
        
        if ($recorrente && $quantidadeMeses > 1) {
            // Criação recorrente
            $resultado = $this->metaService->criarRecorrente($dados, $quantidadeMeses);
            
            $mensagem = sprintf(
                '%d meta(s) criada(s) com sucesso.',
                count($resultado['criadas'])
            );
            
            if (!empty($resultado['ignoradas'])) {
                $mensagem .= sprintf(
                    ' %d mês(es) ignorado(s) (já existiam).',
                    count($resultado['ignoradas'])
                );
            }
            
            $this->setFlashMessage('success', $mensagem);
        } else {
            // Criação simples (código existente)
            $this->metaService->criar($dados);
            $this->setFlashMessage('success', 'Meta criada com sucesso!');
        }
        
        $this->redirect('/metas');
        
    } catch (\Exception $e) {
        $this->setFlashMessage('error', $e->getMessage());
        $this->redirect('/metas/criar');
    }
}
```

#### View: `views/metas/create.php`
```php
<!-- Adicionar após o campo mes_ano -->
<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="recorrente" name="recorrente" value="1">
        <label class="form-check-label" for="recorrente">
            <i class="bi bi-arrow-repeat me-1"></i>
            Repetir meta para os próximos meses
        </label>
    </div>
</div>

<div class="mb-3" id="quantidade-meses-wrapper" style="display: none;">
    <label for="quantidade_meses" class="form-label">Quantidade de Meses</label>
    <input type="number" class="form-control" id="quantidade_meses" name="quantidade_meses" 
           min="2" max="12" value="3">
    <div class="form-text">
        Metas serão criadas para os próximos meses a partir do mês selecionado.
        Meses que já possuem meta serão ignorados.
    </div>
</div>

<!-- JavaScript para toggle -->
<script>
document.getElementById('recorrente').addEventListener('change', function() {
    const wrapper = document.getElementById('quantidade-meses-wrapper');
    wrapper.style.display = this.checked ? 'block' : 'none';
});
</script>
```

### Dependências
- Método `criar()` já existe no MetaService
- Método `findByMesAno()` já existe no MetaRepository

### Estimativa
- **Complexidade:** Média
- **Arquivos:** 1 (Service) + 1 Controller + 1 View
- **Tempo:** ~40 minutos

---

## 📜 MELHORIA 6: HISTÓRICO DE TRANSIÇÕES DE STATUS — PENDENTE

### Descrição
Registra todas as mudanças de status em tabela de log para auditoria.

### Especificação Técnica

#### Migration: `database/migrations/013_create_meta_status_log.php`
```php
<?php

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS meta_status_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meta_id INT NOT NULL,
            status_anterior VARCHAR(20) NULL COMMENT 'NULL para criação inicial',
            status_novo VARCHAR(20) NOT NULL,
            porcentagem_momento DECIMAL(10,2) NULL COMMENT 'Porcentagem no momento da transição',
            valor_realizado_momento DECIMAL(10,2) NULL COMMENT 'Valor realizado no momento',
            observacao TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (meta_id) REFERENCES metas(id) ON DELETE CASCADE,
            INDEX idx_meta_status_log_meta_id (meta_id),
            INDEX idx_meta_status_log_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
    }
    
    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS meta_status_log");
    }
};
```

#### Repository: `src/Repositories/MetaRepository.php`
```php
/**
 * Registra transição de status no log
 */
private function registrarTransicao(
    int $metaId, 
    ?string $statusAnterior, 
    string $statusNovo,
    ?float $porcentagem = null,
    ?float $valorRealizado = null,
    ?string $observacao = null
): void {
    $sql = "INSERT INTO meta_status_log 
            (meta_id, status_anterior, status_novo, porcentagem_momento, valor_realizado_momento, observacao) 
            VALUES 
            (:meta_id, :status_anterior, :status_novo, :porcentagem, :valor_realizado, :observacao)";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->execute([
        'meta_id' => $metaId,
        'status_anterior' => $statusAnterior,
        'status_novo' => $statusNovo,
        'porcentagem' => $porcentagem,
        'valor_realizado' => $valorRealizado,
        'observacao' => $observacao
    ]);
}

/**
 * Atualiza status COM registro de transição
 * MODIFICA método existente
 */
public function atualizarStatus(int $id, string $status): bool
{
    $meta = $this->find($id);
    if (!$meta) return false;
    
    $statusAnterior = $meta->getStatus();
    
    // Não registra se status é o mesmo
    if ($statusAnterior === $status) {
        return true;
    }
    
    // Registra transição ANTES de atualizar
    $this->registrarTransicao(
        $id,
        $statusAnterior,
        $status,
        $meta->getPorcentagemAtingida(),
        $meta->getValorRealizado()
    );
    
    // Atualiza status (código existente)
    $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $this->getConnection()->prepare($sql);
    return $stmt->execute(['status' => $status, 'id' => $id]);
}

/**
 * Obtém histórico de transições de uma meta
 */
public function getHistoricoTransicoes(int $metaId): array
{
    $sql = "SELECT 
                id,
                status_anterior,
                status_novo,
                porcentagem_momento,
                valor_realizado_momento,
                observacao,
                created_at
            FROM meta_status_log 
            WHERE meta_id = :meta_id 
            ORDER BY created_at DESC";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->execute(['meta_id' => $metaId]);
    
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
```

#### Service: `src/Services/MetaService.php`
```php
/**
 * Obtém histórico formatado de transições
 */
public function getHistoricoTransicoes(int $metaId): array
{
    $historico = $this->metaRepository->getHistoricoTransicoes($metaId);
    
    // Formata labels para exibição
    $statusLabels = [
        'iniciado' => 'Iniciado',
        'em_progresso' => 'Em Progresso',
        'finalizado' => 'Finalizado',
        'superado' => 'Superado'
    ];
    
    foreach ($historico as &$item) {
        $item['status_anterior_label'] = $item['status_anterior'] 
            ? ($statusLabels[$item['status_anterior']] ?? $item['status_anterior'])
            : 'Criação';
        $item['status_novo_label'] = $statusLabels[$item['status_novo']] ?? $item['status_novo'];
        $item['data_formatada'] = date('d/m/Y H:i', strtotime($item['created_at']));
    }
    
    return $historico;
}
```

#### Controller: `src/Controllers/MetaController.php`
```php
// No método show(), adicionar:
$historicoTransicoes = $this->metaService->getHistoricoTransicoes($id);

// Passar para view:
$this->view('metas/show', [
    'meta' => $meta,
    'historicoTransicoes' => $historicoTransicoes  // NOVO
]);
```

#### View: `views/metas/show.php`
```php
<!-- Seção Histórico de Status (adicionar ao final da página) -->
<?php if (!empty($historicoTransicoes)): ?>
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Histórico de Status</h5>
    </div>
    <div class="card-body">
        <div class="timeline">
            <?php foreach ($historicoTransicoes as $transicao): ?>
            <div class="timeline-item mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-secondary"><?= htmlspecialchars($transicao['status_anterior_label']) ?></span>
                        <i class="bi bi-arrow-right mx-2"></i>
                        <span class="badge bg-primary"><?= htmlspecialchars($transicao['status_novo_label']) ?></span>
                        
                        <?php if ($transicao['porcentagem_momento']): ?>
                        <small class="text-muted ms-2">
                            (<?= number_format($transicao['porcentagem_momento'], 1) ?>% - 
                            R$ <?= number_format($transicao['valor_realizado_momento'], 2, ',', '.') ?>)
                        </small>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted"><?= $transicao['data_formatada'] ?></small>
                </div>
                
                <?php if ($transicao['observacao']): ?>
                <p class="text-muted small mt-1 mb-0">
                    <?= htmlspecialchars($transicao['observacao']) ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
```

### Dependências
- Migration 012 (status superado) deve estar executada
- Método `atualizarStatus()` existente será modificado

### Estimativa
- **Complexidade:** Média-Alta
- **Arquivos:** 1 Migration + 2 (Repository, Service) + 1 Controller + 1 View
- **Tempo:** ~60 minutos

---

## 📋 CHECKLIST DE IMPLEMENTAÇÃO

### Ordem Recomendada

| # | Melhoria | Complexidade | Dependências | Status |
|---|----------|--------------|--------------|--------|
| 1 | Status "Superado" | Baixa | Nenhuma | ✅ IMPLEMENTADA |
| 2 | Resumo Estatístico | Baixa | Nenhuma | ⏳ Pendente |
| 3 | Gráfico Evolução | Baixa-Média | Melhoria 2 | ⏳ Pendente |
| 4 | Notificação Risco | Baixa | Nenhuma | ⏳ Pendente |
| 5 | Meta Recorrente | Média | Nenhuma | ⏳ Pendente |
| 6 | Histórico Transições | Média-Alta | Migration | ⏳ Pendente |

### Arquivos a Criar

```
database/migrations/
├── 012_add_status_superado.php       ✅ CRIADO
└── 013_create_meta_status_log.php    ⏳ PENDENTE
```

### Arquivos a Modificar

```
src/
├── Models/
│   └── Meta.php                      ✅ ATUALIZADO (Melhoria 1)
├── Repositories/
│   └── MetaRepository.php            ✅ PARCIAL (falta Melhorias 2,3,6)
├── Services/
│   └── MetaService.php               ✅ PARCIAL (falta Melhorias 2,3,4,5,6)
└── Controllers/
    ├── MetaController.php            ✅ PARCIAL (falta Melhorias 2,3,5,6)
    └── DashboardController.php       ⏳ PENDENTE (Melhoria 4)

views/
├── metas/
│   ├── index.php                     ✅ PARCIAL (falta Melhorias 2,3)
│   ├── create.php                    ⏳ PENDENTE (Melhoria 5)
│   └── show.php                      ⏳ PENDENTE (Melhoria 6)
└── dashboard/
    └── index.php                     ⏳ PENDENTE (Melhoria 4)
```

---

## 🔧 INSTRUÇÕES PARA CONTINUAÇÃO

### Para iniciar nova sessão de desenvolvimento:

1. **Contexto necessário:**
   - Ler este documento (ARTFLOW_METAS_ROADMAP.md)
   - Consultar ARTFLOW_2_0_DOCUMENTACAO_COMPLETA.md para estrutura geral
   - Consultar ARTFLOW_2_0_ARQUITETURA_PROFISSIONAL.md para padrões

2. **Verificar estado atual:**
   ```bash
   # Verificar migrations executadas
   ls -la database/migrations/
   
   # Verificar estrutura tabela metas
   # Via phpMyAdmin ou MySQL CLI
   DESCRIBE metas;
   ```

3. **Implementar na ordem:**
   - Melhoria 2 (Estatísticas) → Melhoria 3 (Gráfico) → Melhoria 4 (Notificação)
   - Depois: Melhoria 5 (Recorrente) → Melhoria 6 (Histórico)

4. **Testar cada melhoria:**
   - Verificar se página /metas carrega sem erros
   - Testar funcionalidade específica
   - Verificar responsividade

---

## 📊 MÉTODOS EXISTENTES (REFERÊNCIA RÁPIDA)

### MetaRepository (já implementados)
- `findByAno(int $ano)` - Lista metas de um ano
- `getAnosComMetas()` - Anos que possuem metas
- `atualizarStatus(int $id, string $status)` - Atualiza status
- `atualizarProgresso(int $id, float $valor)` - Atualiza progresso com lógica de status
- `finalizarMetasPassadas()` - Finaliza metas de meses anteriores
- `getDesempenhoMensal(int $meses)` - Desempenho dos últimos N meses
- `getEstatisticas()` - Estatísticas gerais (não por ano)

### MetaService (já implementados)
- `calcularProjecao(Meta $meta)` - Calcula projeção de atingimento
- `getResumoDashboard()` - Resumo para dashboard
- `buscarMesAtual()` - Busca meta do mês atual
- `buscarPorAno(int $ano)` - Lista metas de um ano
- `getAnosDisponiveis()` - Anos disponíveis para filtro
- `finalizarMetasPassadas()` - Wrapper para repository

### Meta Model (já implementados)
- `getStatus()`, `setStatus()` - Getter/Setter status
- `isIniciado()`, `isEmProgresso()`, `isFinalizado()`, `isSuperado()` - Checks de status
- `getStatusLabel()`, `getStatusIcon()`, `getStatusBadgeClass()` - Formatação visual
- `foiAtingida()` - Verifica se >= 100%
- `isMesAtual()`, `isMesPassado()`, `isMesFuturo()` - Checks de data
- `getValorFaltante()` - Calcula quanto falta
- `getProgressoClass()` - Classe CSS do progresso

---

**Última atualização:** 05/02/2026  
**Próxima ação:** Implementar Melhoria 2 (Resumo Estatístico por Ano)
