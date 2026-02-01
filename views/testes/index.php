<?php
/**
 * VIEW: Sistema de Testes e Diagnóstico
 * GET /testes
 * 
 * Interface visual para executar e visualizar testes do sistema.
 * 
 * Variáveis:
 * - $resultados: Array com resultados por categoria
 * - $resumo: Array com totais e estatísticas
 * - $moduloAtual: Módulo selecionado ('all' ou específico)
 */
$currentPage = 'testes';

// Função para ícone de status
function statusIcon(string $status): string {
    return match($status) {
        'pass' => '<i class="bi bi-check-circle-fill text-success"></i>',
        'fail' => '<i class="bi bi-x-circle-fill text-danger"></i>',
        'warn' => '<i class="bi bi-exclamation-triangle-fill text-warning"></i>',
        'skip' => '<i class="bi bi-dash-circle text-secondary"></i>',
        'info' => '<i class="bi bi-info-circle text-info"></i>',
        default => '<i class="bi bi-question-circle text-muted"></i>'
    };
}

// Função para badge de status
function statusBadge(string $status): string {
    $classes = match($status) {
        'pass' => 'bg-success',
        'fail' => 'bg-danger',
        'warn' => 'bg-warning text-dark',
        'skip' => 'bg-secondary',
        'info' => 'bg-info',
        default => 'bg-light text-dark'
    };
    $texto = match($status) {
        'pass' => 'OK',
        'fail' => 'FALHOU',
        'warn' => 'AVISO',
        'skip' => 'PULADO',
        'info' => 'INFO',
        default => $status
    };
    return "<span class='badge {$classes}'>{$texto}</span>";
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-bug text-warning"></i> Sistema de Testes
        </h2>
        <p class="text-muted mb-0">
            Diagnóstico e verificação do ArtFlow 2.0 | 
            <small><?= date('d/m/Y H:i:s') ?></small>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-house"></i> Dashboard
        </a>
        <a href="<?= url('/testes?refresh=1') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-clockwise"></i> Executar Todos
        </a>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h2 class="mb-0"><?= $resumo['passou'] ?? 0 ?></h2>
                <small>Passou</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h2 class="mb-0"><?= $resumo['falhou'] ?? 0 ?></h2>
                <small>Falhou</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h2 class="mb-0"><?= $resumo['avisos'] ?? 0 ?></h2>
                <small>Avisos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?= ($resumo['status_geral'] ?? 'secondary') === 'success' ? 'bg-primary' : 'bg-secondary' ?> text-white">
            <div class="card-body text-center">
                <h2 class="mb-0"><?= $resumo['taxa_sucesso'] ?? 0 ?>%</h2>
                <small>Taxa de Sucesso</small>
            </div>
        </div>
    </div>
</div>

<!-- Barra de Progresso -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="text-muted">Progresso Geral</small>
            <small class="text-muted"><?= $resumo['passou'] ?? 0 ?> / <?= ($resumo['passou'] ?? 0) + ($resumo['falhou'] ?? 0) ?> testes</small>
        </div>
        <div class="progress" style="height: 10px;">
            <?php 
            $total = max(1, ($resumo['passou'] ?? 0) + ($resumo['falhou'] ?? 0) + ($resumo['avisos'] ?? 0));
            $passouPct = (($resumo['passou'] ?? 0) / $total) * 100;
            $falhouPct = (($resumo['falhou'] ?? 0) / $total) * 100;
            $avisosPct = (($resumo['avisos'] ?? 0) / $total) * 100;
            ?>
            <div class="progress-bar bg-success" style="width: <?= $passouPct ?>%"></div>
            <div class="progress-bar bg-danger" style="width: <?= $falhouPct ?>%"></div>
            <div class="progress-bar bg-warning" style="width: <?= $avisosPct ?>%"></div>
        </div>
    </div>
</div>

<!-- Navegação de Módulos -->
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'all' ? 'active' : '' ?>" href="<?= url('/testes?modulo=all') ?>">
            <i class="bi bi-grid-3x3-gap"></i> Todos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'ambiente' ? 'active' : '' ?>" href="<?= url('/testes?modulo=ambiente') ?>">
            <i class="bi bi-gear"></i> Ambiente
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'banco' ? 'active' : '' ?>" href="<?= url('/testes?modulo=banco') ?>">
            <i class="bi bi-database"></i> Banco
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'rotas' ? 'active' : '' ?>" href="<?= url('/testes?modulo=rotas') ?>">
            <i class="bi bi-signpost"></i> Rotas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'seguranca' ? 'active' : '' ?>" href="<?= url('/testes?modulo=seguranca') ?>">
            <i class="bi bi-shield-lock"></i> Segurança
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'modulos' ? 'active' : '' ?>" href="<?= url('/testes?modulo=modulos') ?>">
            <i class="bi bi-boxes"></i> Módulos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'helpers' ? 'active' : '' ?>" href="<?= url('/testes?modulo=helpers') ?>">
            <i class="bi bi-tools"></i> Helpers
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $moduloAtual === 'views' ? 'active' : '' ?>" href="<?= url('/testes?modulo=views') ?>">
            <i class="bi bi-file-earmark-code"></i> Views
        </a>
    </li>
</ul>

<!-- Resultados por Categoria -->
<?php foreach ($resultados as $categoria => $testes): ?>
    <?php if ($categoria === 'resumo' || !is_array($testes)) continue; ?>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php
                $icone = match($categoria) {
                    'ambiente' => 'bi-gear',
                    'banco' => 'bi-database',
                    'rotas' => 'bi-signpost',
                    'seguranca' => 'bi-shield-lock',
                    'modulos' => 'bi-boxes',
                    'helpers' => 'bi-tools',
                    'views' => 'bi-file-earmark-code',
                    default => 'bi-check2-square'
                };
                ?>
                <i class="bi <?= $icone ?> me-2"></i>
                <?= ucfirst($categoria) ?>
            </h5>
            <?php
            $catPassou = 0;
            $catFalhou = 0;
            foreach ($testes as $t) {
                if (($t['status'] ?? '') === 'pass') $catPassou++;
                if (($t['status'] ?? '') === 'fail') $catFalhou++;
            }
            ?>
            <span class="badge <?= $catFalhou === 0 ? 'bg-success' : 'bg-danger' ?>">
                <?= $catPassou ?>/<?= $catPassou + $catFalhou ?>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if ($categoria === 'modulos'): ?>
                <!-- Layout especial para módulos (accordion) -->
                <div class="accordion accordion-flush" id="accordionModulos">
                    <?php foreach ($testes as $key => $modulo): ?>
                        <?php if (!isset($modulo['detalhes'])) continue; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $key ?>">
                                    <?= statusIcon($modulo['status'] ?? 'info') ?>
                                    <span class="ms-2"><?= e($modulo['nome'] ?? $key) ?></span>
                                    <small class="ms-auto me-3 text-muted"><?= e($modulo['mensagem'] ?? '') ?></small>
                                </button>
                            </h2>
                            <div id="collapse<?= $key ?>" class="accordion-collapse collapse" data-bs-parent="#accordionModulos">
                                <div class="accordion-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <?php foreach ($modulo['detalhes'] as $tipo => $detalhe): ?>
                                            <tr>
                                                <td width="120"><?= ucfirst($tipo) ?></td>
                                                <td><?= statusIcon($detalhe['status'] ?? 'fail') ?></td>
                                                <td class="text-muted small"><?= e($detalhe['classe'] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Layout padrão (tabela) -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">Status</th>
                                <th>Teste</th>
                                <th>Resultado</th>
                                <?php if ($categoria === 'rotas'): ?>
                                    <th width="80">Tempo</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testes as $key => $teste): ?>
                                <?php if (!isset($teste['status'])) continue; ?>
                                <tr class="<?= $teste['status'] === 'fail' ? 'table-danger' : '' ?>">
                                    <td class="text-center"><?= statusIcon($teste['status']) ?></td>
                                    <td>
                                        <strong><?= e($teste['nome'] ?? $key) ?></strong>
                                        <?php if (!empty($teste['descricao'])): ?>
                                            <br><small class="text-muted"><?= e($teste['descricao']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($teste['valor'])): ?>
                                            <code><?= e($teste['valor']) ?></code> - 
                                        <?php endif; ?>
                                        <?= e($teste['mensagem'] ?? '-') ?>
                                    </td>
                                    <?php if ($categoria === 'rotas'): ?>
                                        <td>
                                            <small class="text-muted"><?= $teste['tempo'] ?? '-' ?></small>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<!-- Lista de Problemas -->
<?php
$problemas = [];
foreach ($resultados as $categoria => $testes) {
    if (!is_array($testes)) continue;
    foreach ($testes as $key => $teste) {
        if (($teste['status'] ?? '') === 'fail') {
            $problemas[] = [
                'categoria' => $categoria,
                'teste' => $teste['nome'] ?? $key,
                'mensagem' => $teste['mensagem'] ?? ''
            ];
        }
    }
}
?>

<?php if (!empty($problemas)): ?>
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle"></i> 
            Problemas Encontrados (<?= count($problemas) ?>)
        </h5>
    </div>
    <div class="card-body">
        <ul class="list-unstyled mb-0">
            <?php foreach ($problemas as $p): ?>
                <li class="mb-2">
                    <span class="badge bg-secondary me-2"><?= ucfirst($p['categoria']) ?></span>
                    <strong><?= e($p['teste']) ?></strong>
                    <?php if ($p['mensagem']): ?>
                        <br><small class="text-muted ms-4"><?= e($p['mensagem']) ?></small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<!-- Informações do Sistema -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informações do Sistema</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted d-block">PHP Version</small>
                <strong><?= phpversion() ?></strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Memória Usada</small>
                <strong><?= round(memory_get_peak_usage(true) / 1024 / 1024, 2) ?> MB</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Tempo de Execução</small>
                <strong><?= round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000) ?>ms</strong>
            </div>
        </div>
    </div>
</div>

<!-- Alerta de Produção -->
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Atenção:</strong> Esta página de testes deve ser removida ou protegida com senha em ambiente de produção!
</div>
