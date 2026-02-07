<?php
/**
 * VIEW: Detalhes da Meta
 * GET /metas/{id}
 * 
 * Variáveis disponíveis (passadas pelo MetaController::show):
 * - $meta: Objeto Meta com todos os dados
 * - $projecao: Array com projeções de fechamento
 * - $horasNecessarias: Array com cálculo de horas (opcional)
 * - $historicoTransicoes: Array de transições de status (Melhoria 6)
 */
$currentPage = 'metas';

// ==========================================
// PREPARAÇÃO DOS DADOS
// ==========================================

// Dados da meta
$mesAno = $meta->getMesAno();
$porcentagem = $meta->getPorcentagemAtingida();
$foiAtingida = $porcentagem >= 100;

// Formata mês/ano para exibição
$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 
    4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 
    10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$mesNum = (int) date('n', strtotime($mesAno));
$ano = date('Y', strtotime($mesAno));
$mesNome = $mesesNomes[$mesNum] ?? '';
$mesAnoFormatado = "{$mesNome} de {$ano}";

// Calcula valores auxiliares
$faltaVender = max(0, $meta->getValorMeta() - $meta->getValorRealizado());

// Dias do mês e dia atual
$diasNoMes = (int) date('t', strtotime($mesAno));
$diaAtual = $meta->isMesAtual() ? (int) date('j') : $diasNoMes;
$diasRestantes = max(0, $diasNoMes - $diaAtual);

// Média diária atual e necessária
$mediaDiariaAtual = $diaAtual > 0 ? $meta->getValorRealizado() / $diaAtual : 0;
$mediaDiariaNecessaria = $diasRestantes > 0 ? $faltaVender / $diasRestantes : 0;

// Projeção de fechamento
$projecaoTotal = $mediaDiariaAtual * $diasNoMes;
$vaiBaterMeta = $projecaoTotal >= $meta->getValorMeta();
?>

<!-- ==========================================
     HEADER — Título + Status + Botões
     ========================================== -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-bullseye text-primary"></i> Meta: <?= $mesAnoFormatado ?>
        </h2>
        <p class="text-muted mb-0">
            <!-- Badge de status com ícone (Melhoria 1: inclui "Superado") -->
            <span class="badge <?= $meta->getStatusBadgeClass() ?>">
                <i class="bi <?= $meta->getStatusIcon() ?>"></i>
                <?= $meta->getStatusLabel() ?>
            </span>
            
            <?php if ($foiAtingida): ?>
                <span class="badge bg-success ms-1">✓ Meta Atingida!</span>
            <?php elseif ($meta->isMesAtual()): ?>
                <span class="badge bg-info ms-1">Mês Atual</span>
            <?php elseif ($meta->isMesPassado()): ?>
                <span class="badge bg-secondary ms-1">Encerrada</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/metas/' . $meta->getId() . '/editar') ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= url('/metas') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<!-- ==========================================
     CARD: PROGRESSO PRINCIPAL
     ========================================== -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <!-- Barra de Progresso (col-8) -->
            <div class="col-md-8">
                <h5 class="mb-3">Progresso</h5>
                <div class="progress mb-3" style="height: 30px;">
                    <?php 
                    // Cor da barra baseada na porcentagem
                    $corBarra = $foiAtingida 
                        ? 'success' 
                        : ($porcentagem >= 75 ? 'info' : ($porcentagem >= 50 ? 'warning' : 'danger'));
                    $larguraBarra = min(100, $porcentagem);
                    ?>
                    <div class="progress-bar bg-<?= $corBarra ?> fw-bold" 
                         role="progressbar"
                         style="width: <?= $larguraBarra ?>%;"
                         aria-valuenow="<?= $porcentagem ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?= number_format($porcentagem, 1, ',', '.') ?>%
                    </div>
                </div>
                
                <!-- Valores: Meta / Realizado / Falta -->
                <div class="row text-center">
                    <div class="col-4">
                        <small class="text-muted">Meta</small>
                        <h5>R$ <?= number_format($meta->getValorMeta(), 2, ',', '.') ?></h5>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Realizado</small>
                        <h5 class="text-success">R$ <?= number_format($meta->getValorRealizado(), 2, ',', '.') ?></h5>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Falta</small>
                        <h5 class="text-danger">R$ <?= number_format($faltaVender, 2, ',', '.') ?></h5>
                    </div>
                </div>
            </div>
            
            <!-- Porcentagem grande (col-4) -->
            <div class="col-md-4 text-center">
                <div class="display-3 fw-bold text-<?= $corBarra ?>">
                    <?= number_format($porcentagem, 1, ',', '.') ?>%
                </div>
                <small class="text-muted">de realização</small>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     CARDS: DETALHES E PROJEÇÃO (Lado a lado)
     ========================================== -->
<div class="row mb-4">
    <!-- Card Detalhes -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Detalhes</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="text-muted">Período</td>
                        <td class="fw-bold"><?= $mesAnoFormatado ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dias no Mês</td>
                        <td><?= $diasNoMes ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dia Atual</td>
                        <td><?= $meta->isMesAtual() ? $diaAtual . '/' . $diasNoMes : 'Encerrado' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dias Restantes</td>
                        <td><?= $diasRestantes ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dias Trabalho/Semana</td>
                        <td><?= $meta->getDiasTrabalhoSemana() ?? 5 ?></td>
                    </tr>
                    <?php if ($meta->getObservacoes()): ?>
                    <tr>
                        <td class="text-muted">Observações</td>
                        <td><?= nl2br(htmlspecialchars($meta->getObservacoes())) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Card Projeção -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Projeção</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="text-muted">Média Diária Atual</td>
                        <td class="fw-bold">R$ <?= number_format($mediaDiariaAtual, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Média Necessária/Dia</td>
                        <td class="fw-bold text-<?= $mediaDiariaNecessaria > $mediaDiariaAtual ? 'danger' : 'success' ?>">
                            R$ <?= number_format($mediaDiariaNecessaria, 2, ',', '.') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Projeção Fim do Mês</td>
                        <td class="fw-bold text-<?= $vaiBaterMeta ? 'success' : 'danger' ?>">
                            R$ <?= number_format($projecaoTotal, 2, ',', '.') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Vai Bater a Meta?</td>
                        <td>
                            <?php if ($vaiBaterMeta): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Sim!</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-lg"></i> Em risco</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (isset($horasNecessarias) && is_array($horasNecessarias)): ?>
                    <tr>
                        <td class="text-muted">Horas Necessárias/Dia</td>
                        <td class="fw-bold text-<?= ($horasNecessarias['viavel'] ?? false) ? 'success' : 'warning' ?>">
                            <?= number_format($horasNecessarias['horas_por_dia'] ?? 0, 1, ',', '.') ?>h
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MELHORIA 6: TIMELINE DE TRANSIÇÕES DE STATUS
     ==========================================
     
     Exibe o histórico completo de mudanças de status da meta.
     Cada transição mostra:
     - Data/hora formatada
     - Badge do status anterior → seta → badge do status novo
     - Porcentagem e valor no momento da transição
     - Observação automática ou manual
     
     Se não há registros, exibe mensagem informativa.
     ========================================== -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-clock-history"></i> Histórico de Status
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($historicoTransicoes)): ?>
            
            <!-- Timeline vertical com CSS inline (sem dependências externas) -->
            <div class="timeline-container">
                <?php foreach ($historicoTransicoes as $index => $transicao): ?>
                    <div class="d-flex mb-3 <?= $index < count($historicoTransicoes) - 1 ? 'pb-3 border-bottom' : '' ?>">
                        
                        <!-- Ícone da transição (coluna esquerda) -->
                        <div class="flex-shrink-0 me-3 text-center" style="width: 40px;">
                            <span class="badge rounded-pill <?= $transicao['status_novo_badge'] ?>" 
                                  style="width: 36px; height: 36px; line-height: 24px; font-size: 1rem;">
                                <i class="bi <?= $transicao['status_novo_icon'] ?>"></i>
                            </span>
                        </div>
                        
                        <!-- Conteúdo da transição (coluna direita) -->
                        <div class="flex-grow-1">
                            <!-- Linha 1: Badges de status anterior → novo -->
                            <div class="mb-1">
                                <?php if ($transicao['is_criacao']): ?>
                                    <!-- Criação inicial: apenas badge do status novo -->
                                    <span class="badge <?= $transicao['status_novo_badge'] ?>">
                                        <?= $transicao['status_novo_label'] ?>
                                    </span>
                                    <small class="text-muted ms-1">— Meta criada</small>
                                <?php else: ?>
                                    <!-- Transição: anterior → novo -->
                                    <span class="badge <?= $transicao['status_anterior_badge'] ?>">
                                        <?= $transicao['status_anterior_label'] ?>
                                    </span>
                                    <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                    <span class="badge <?= $transicao['status_novo_badge'] ?>">
                                        <?= $transicao['status_novo_label'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Linha 2: Observação -->
                            <?php if (!empty($transicao['observacao'])): ?>
                                <div class="text-muted small mb-1">
                                    <?= htmlspecialchars($transicao['observacao']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Linha 3: Dados do momento + Data/hora -->
                            <div class="d-flex flex-wrap gap-3 small text-muted">
                                <!-- Porcentagem no momento (se disponível) -->
                                <?php if ($transicao['porcentagem_momento'] !== null): ?>
                                    <span>
                                        <i class="bi bi-percent"></i>
                                        <?= number_format($transicao['porcentagem_momento'], 1, ',', '.') ?>%
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Valor realizado no momento (se disponível) -->
                                <?php if ($transicao['valor_realizado_momento'] !== null): ?>
                                    <span>
                                        <i class="bi bi-cash"></i>
                                        R$ <?= number_format($transicao['valor_realizado_momento'], 2, ',', '.') ?>
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Data/hora da transição -->
                                <span>
                                    <i class="bi bi-calendar-event"></i>
                                    <?= $transicao['data_formatada'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- Nenhuma transição registrada -->
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history display-6 d-block mb-2 opacity-25"></i>
                <p class="mb-1">Nenhuma transição de status registrada.</p>
                <small>O histórico será preenchido automaticamente a partir de agora.</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ==========================================
     BOTÕES DE AÇÃO
     ========================================== -->
<div class="d-flex justify-content-between">
    <a href="<?= url('/metas') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar para Lista
    </a>
    <div class="d-flex gap-2">
        <a href="<?= url('/metas/' . $meta->getId() . '/editar') ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Editar Meta
        </a>
        <!-- Botão de excluir com confirmação -->
        <form method="POST" action="<?= url('/metas/' . $meta->getId()) ?>" 
              onsubmit="return confirm('Tem certeza que deseja excluir esta meta? Esta ação não pode ser desfeita.')">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash"></i> Excluir
            </button>
        </form>
    </div>
</div>