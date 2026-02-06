<?php
/**
 * ============================================
 * COMPONENTE: Alerta de Meta em Risco
 * ============================================
 * 
 * MELHORIA 4 (06/02/2026)
 * 
 * Exibe banner de alerta no topo do dashboard quando
 * a projeção indica que a meta do mês não será batida.
 * 
 * Variável esperada da view pai:
 *   $metaEmRisco (array) — retorno de MetaService::getMetasEmRisco()
 *     - ['alerta' => false] → não exibe nada
 *     - ['alerta' => true, 'meta' => [...], 'projecao' => [...], 'mensagem' => '...']
 * 
 * Incluir em views/dashboard/index.php logo após o header:
 *   <?php include __DIR__ . '/../components/alerta-meta-risco.php'; ?>
 */

// Verificação defensiva: só exibe se dados existem e alerta é true
if (!isset($metaEmRisco) || !is_array($metaEmRisco) || empty($metaEmRisco['alerta'])) {
    return; // Não exibe nada — sem meta, meta batida, ou projeção OK
}

// Extrai dados para uso no template
$meta = $metaEmRisco['meta'];
$projecao = $metaEmRisco['projecao'];
$mensagem = $metaEmRisco['mensagem'];

// Calcula nível de severidade para cor do alerta
// < 50% projetado = danger (vermelho), 50-80% = warning (amarelo)
$porcentagemProjetada = $projecao['porcentagem_projetada'] ?? 0;
$nivelAlerta = $porcentagemProjetada < 50 ? 'danger' : 'warning';
$iconeAlerta = $nivelAlerta === 'danger' ? 'exclamation-triangle-fill' : 'exclamation-circle-fill';

// Nome do mês para exibição
$nomeMes = '';
if (!empty($meta['mes_ano'])) {
    $timestamp = strtotime($meta['mes_ano']);
    if ($timestamp !== false) {
        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        $nomeMes = ($meses[(int)date('n', $timestamp)] ?? '') . '/' . date('Y', $timestamp);
    }
}
?>

<!-- ============================================ -->
<!-- MELHORIA 4: ALERTA DE META EM RISCO         -->
<!-- ============================================ -->
<div class="alert alert-<?= $nivelAlerta ?> alert-dismissible fade show mb-4 shadow-sm" role="alert" data-persist="true">
    <div class="d-flex align-items-start">
        
        <!-- Ícone -->
        <i class="bi bi-<?= $iconeAlerta ?> me-3 fs-4 mt-1"></i>
        
        <!-- Conteúdo -->
        <div class="flex-grow-1">
            
            <!-- Título -->
            <h6 class="alert-heading mb-1 fw-bold">
                <i class="bi bi-bullseye me-1"></i>
                Meta de <?= htmlspecialchars($nomeMes) ?> em Risco!
            </h6>
            
            <!-- Mensagem principal com dados da projeção -->
            <p class="mb-2 small">
                <?= htmlspecialchars($mensagem) ?>
            </p>
            
            <!-- Barra de progresso visual -->
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="progress flex-grow-1" style="height: 8px;">
                    <?php
                    // Barra mostra progresso atual (sólido) + projeção (transparente)
                    $pctAtual = min($meta['porcentagem_atingida'], 100);
                    $pctProjetado = min($porcentagemProjetada, 100);
                    $corBarra = $nivelAlerta === 'danger' ? 'bg-danger' : 'bg-warning';
                    ?>
                    <!-- Progresso atual -->
                    <div class="progress-bar <?= $corBarra ?>" 
                         style="width: <?= $pctAtual ?>%"
                         title="Atual: <?= number_format($pctAtual, 1) ?>%">
                    </div>
                    <!-- Projeção (trecho extra, mais transparente) -->
                    <?php if ($pctProjetado > $pctAtual): ?>
                    <div class="progress-bar <?= $corBarra ?> opacity-25" 
                         style="width: <?= $pctProjetado - $pctAtual ?>%"
                         title="Projeção: <?= number_format($pctProjetado, 1) ?>%">
                    </div>
                    <?php endif; ?>
                </div>
                <small class="text-<?= $nivelAlerta ?> fw-bold text-nowrap">
                    <?= number_format($pctAtual, 1) ?>% atual
                </small>
            </div>
            
            <!-- Resumo rápido em badges -->
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-<?= $nivelAlerta ?> bg-opacity-75">
                    <i class="bi bi-currency-dollar me-1"></i>
                    Meta: R$ <?= number_format($meta['valor_meta'], 2, ',', '.') ?>
                </span>
                <span class="badge bg-secondary">
                    <i class="bi bi-check2 me-1"></i>
                    Realizado: R$ <?= number_format($meta['valor_realizado'], 2, ',', '.') ?>
                </span>
                <span class="badge bg-dark">
                    <i class="bi bi-graph-up-arrow me-1"></i>
                    Projeção: R$ <?= number_format($projecao['projecao_total'], 2, ',', '.') ?>
                </span>
                <span class="badge bg-<?= $nivelAlerta ?>">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= $projecao['dias_restantes'] ?> dias restantes
                </span>
            </div>
        </div>
        
        <!-- Link direto para a meta -->
        <a href="<?= url('/metas/' . $meta['id']) ?>" 
           class="btn btn-sm btn-outline-<?= $nivelAlerta ?> ms-3 text-nowrap align-self-center"
           title="Ver detalhes da meta">
            <i class="bi bi-eye me-1"></i> Ver Meta
        </a>
    </div>
    
    <!-- Botão de fechar (dismiss) -->
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>