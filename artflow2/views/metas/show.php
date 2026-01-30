<?php
/**
 * VIEW: Detalhes da Meta
 * GET /metas/{id}
 * 
 * Variáveis:
 * - $meta: Objeto Meta
 * - $projecao: Array com projeções (opcional)
 */
$currentPage = 'metas';

// Dados da meta
$mesAno = $meta->getMesAno();
$porcentagem = $meta->getPorcentagemAtingida();
$foiAtingida = $porcentagem >= 100;

// Formata mês/ano
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

// Calcula valores
$faltaVender = max(0, $meta->getValorMeta() - $meta->getValorRealizado());

// Dias do mês
$diasNoMes = (int) date('t', strtotime($mesAno));
$diaAtual = $meta->isMesAtual() ? (int) date('j') : $diasNoMes;
$diasRestantes = max(0, $diasNoMes - $diaAtual);

// Média diária
$mediaDiariaAtual = $diaAtual > 0 ? $meta->getValorRealizado() / $diaAtual : 0;
$mediaDiariaNecessaria = $diasRestantes > 0 ? $faltaVender / $diasRestantes : 0;

// Projeção
$projecaoTotal = $mediaDiariaAtual * $diasNoMes;
$vaiBaterMeta = $projecaoTotal >= $meta->getValorMeta();
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-bullseye text-primary"></i> Meta: <?= $mesAnoFormatado ?>
        </h2>
        <p class="text-muted mb-0">
            <?php if ($foiAtingida): ?>
                <span class="badge bg-success">✓ Meta Atingida!</span>
            <?php elseif ($meta->isMesAtual()): ?>
                <span class="badge bg-info">Mês Atual</span>
            <?php else: ?>
                <span class="badge bg-secondary">Encerrada</span>
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

<!-- Progresso Principal -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-3">Progresso</h5>
                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar bg-<?= $foiAtingida ? 'success' : ($porcentagem >= 75 ? 'info' : ($porcentagem >= 50 ? 'warning' : 'danger')) ?>" 
                         style="width: <?= min($porcentagem, 100) ?>%">
                        <strong><?= number_format($porcentagem, 1) ?>%</strong>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <span>
                        <strong class="text-success"><?= money($meta->getValorRealizado()) ?></strong>
                        <small class="text-muted">realizado</small>
                    </span>
                    <span>
                        <strong><?= money($meta->getValorMeta()) ?></strong>
                        <small class="text-muted">meta</small>
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <?php if ($foiAtingida): ?>
                    <div class="display-1 text-success">🎉</div>
                    <h5 class="text-success">Parabéns!</h5>
                    <p class="text-muted mb-0">Meta atingida!</p>
                <?php else: ?>
                    <h4 class="text-warning mb-1"><?= money($faltaVender) ?></h4>
                    <p class="text-muted mb-0">falta para a meta</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Detalhes da Meta -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Detalhes</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Período:</td>
                        <td class="text-end fw-bold"><?= $mesAnoFormatado ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Valor da Meta:</td>
                        <td class="text-end fw-bold"><?= money($meta->getValorMeta()) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Valor Realizado:</td>
                        <td class="text-end fw-bold text-success"><?= money($meta->getValorRealizado()) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Porcentagem:</td>
                        <td class="text-end fw-bold"><?= number_format($porcentagem, 1) ?>%</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Horas Diárias Ideal:</td>
                        <td class="text-end"><?= $meta->getHorasDiariasIdeal() ?>h</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dias de Trabalho/Semana:</td>
                        <td class="text-end"><?= $meta->getDiasTrabalhoSemana() ?> dias</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Projeção -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Projeção</h6>
            </div>
            <div class="card-body">
                <?php if ($meta->isMesAtual()): ?>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Dias no Mês:</td>
                            <td class="text-end"><?= $diasNoMes ?> dias</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dia Atual:</td>
                            <td class="text-end"><?= $diaAtual ?>º dia</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dias Restantes:</td>
                            <td class="text-end fw-bold"><?= $diasRestantes ?> dias</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Média Diária Atual:</td>
                            <td class="text-end"><?= money($mediaDiariaAtual) ?>/dia</td>
                        </tr>
                        <?php if (!$foiAtingida && $diasRestantes > 0): ?>
                        <tr>
                            <td class="text-muted">Média Necessária:</td>
                            <td class="text-end text-warning fw-bold"><?= money($mediaDiariaNecessaria) ?>/dia</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted">Projeção Final:</td>
                            <td class="text-end">
                                <span class="fw-bold <?= $vaiBaterMeta ? 'text-success' : 'text-danger' ?>">
                                    <?= money($projecaoTotal) ?>
                                </span>
                                <?php if ($vaiBaterMeta): ?>
                                    <i class="bi bi-check-circle text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle text-danger"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <?php if (!$foiAtingida): ?>
                        <div class="alert alert-<?= $vaiBaterMeta ? 'success' : 'warning' ?> mt-3 mb-0">
                            <small>
                                <?php if ($vaiBaterMeta): ?>
                                    <i class="bi bi-check-circle"></i> No ritmo atual, você <strong>vai atingir</strong> a meta!
                                <?php else: ?>
                                    <i class="bi bi-exclamation-triangle"></i> Você precisa vender <strong><?= money($mediaDiariaNecessaria) ?>/dia</strong> para atingir a meta.
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-check display-4"></i>
                        <p class="mt-2 mb-0">Este mês já encerrou.</p>
                        <p class="mb-0">
                            Resultado final: 
                            <strong class="<?= $foiAtingida ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($porcentagem, 1) ?>%
                            </strong>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ações -->
<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">
                    Criada em: <?= datetime_br($meta->getCreatedAt()) ?>
                    <?php if ($meta->getUpdatedAt()): ?>
                        | Atualizada: <?= datetime_br($meta->getUpdatedAt()) ?>
                    <?php endif; ?>
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('/metas/' . $meta->getId() . '/editar') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil"></i> Editar Meta
                </a>
                <a href="<?= url('/vendas?mes=' . date('Y-m', strtotime($mesAno))) ?>" class="btn btn-outline-info">
                    <i class="bi bi-cart"></i> Ver Vendas do Mês
                </a>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluir">
                    <i class="bi bi-trash"></i> Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a meta de <strong><?= $mesAnoFormatado ?></strong>?</p>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle"></i> As vendas do período não serão afetadas.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?= url('/metas/' . $meta->getId()) ?>" method="POST" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir Meta
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
