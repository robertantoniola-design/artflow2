<?php
/**
 * VIEW: Listagem de Metas
 * GET /metas
 * 
 * ATUALIZAÇÃO (01/02/2026):
 * - Filtro de ano: trocado dropdown+filtrar/limpar por abas/pills clicáveis
 * - Status: badges agora usam campo 'status' do banco (iniciado/em_progresso/finalizado)
 * - Cards: melhor organização visual
 * 
 * MELHORIA 1 — Status "Superado" (01/02/2026):
 * - Badge dourado com troféu para metas com status 'superado'
 * - Barra de progresso permite visualizar > 100% (limitada visualmente a 100%)
 * - Texto de porcentagem mostra valor real (ex: "135%") sem truncar
 * - Destaque visual especial para metas superadas (borda dourada)
 * 
 * Variáveis esperadas:
 * - $metas: array de objetos Meta
 * - $estatisticas: array com totais
 * - $anoSelecionado: int (ano ativo)
 * - $anos: array de inteiros (anos disponíveis)
 */
$currentPage = 'metas';
?>

<!-- ============================================ -->
<!-- HEADER                                       -->
<!-- ============================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-bullseye text-primary"></i> Metas
        </h2>
        <p class="text-muted mb-0">Acompanhe suas metas mensais</p>
    </div>
    <a href="<?= url('/metas/criar') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Meta
    </a>
</div>

<!-- ============================================ -->
<!-- NAVEGAÇÃO POR ANO (PILLS/ABAS)               -->
<!-- Cada pill é um link direto: /metas?ano=XXXX   -->
<!-- ============================================ -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Label -->
            <span class="text-muted fw-semibold me-2">
                <i class="bi bi-calendar3"></i> Ano:
            </span>
            
            <!-- Pills de anos -->
            <?php foreach ($anos ?? [] as $ano): ?>
                <?php 
                // Verifica se este é o ano selecionado
                $isAtivo = ($ano == ($anoSelecionado ?? date('Y')));
                ?>
                <a href="<?= url('/metas?ano=' . $ano) ?>" 
                   class="btn btn-sm <?= $isAtivo ? 'btn-primary' : 'btn-outline-secondary' ?>"
                   title="Ver metas de <?= $ano ?>">
                    <?= $ano ?>
                    <?php if ($ano == date('Y')): ?>
                        <span class="badge bg-light text-primary ms-1" style="font-size: 0.65em;">atual</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- LISTA DE METAS (CARDS)                       -->
<!-- ============================================ -->
<?php if (empty($metas)): ?>
    <!-- Estado vazio: nenhuma meta no ano selecionado -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-bullseye display-4 text-muted"></i>
            <h5 class="mt-3">Nenhuma meta em <?= $anoSelecionado ?? date('Y') ?></h5>
            <p class="text-muted">Defina metas mensais para acompanhar seu desempenho.</p>
            <a href="<?= url('/metas/criar') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Criar Meta
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($metas as $meta): ?>
            <?php
            // --- Dados para o card ---
            
            // Porcentagem real (sem truncar — pode ser > 100%)
            $porcentagemReal = $meta->getPorcentagemAtingida() ?? 0;
            
            // Porcentagem para a barra visual (limitada a 100% para não estourar)
            $porcentagemBarra = min(100, $porcentagemReal);
            
            $mesAno = date('m/Y', strtotime($meta->getMesAno()));
            $isAtual = $meta->isMesAtual();
            
            // Cor da barra de progresso baseada no percentual
            if ($porcentagemReal >= 120) {
                // NOVO: Dourado/amarelo para metas superadas (>= 120%)
                $corBarra = 'warning';
            } elseif ($porcentagemReal >= 100) {
                $corBarra = 'success';
            } elseif ($porcentagemReal >= 50) {
                $corBarra = 'info';
            } elseif ($porcentagemReal >= 25) {
                $corBarra = 'warning';
            } else {
                $corBarra = 'danger';
            }
            
            // Status vem do Model (campo do banco)
            $statusLabel = $meta->getStatusLabel();
            $statusIcon = $meta->getStatusIcon();
            $statusBadgeClass = $meta->getStatusBadgeClass();
            
            // Flags para decisão visual
            $foiAtingida = $meta->foiAtingida();
            $isSuperado = $meta->isSuperado();
            ?>
            
            <div class="col-md-6 col-lg-4">
                <!-- 
                  NOVO: Borda especial para metas superadas
                  - border-primary = mês atual
                  - border-warning = meta superada (dourado)
                  - Ambos podem coexistir
                -->
                <div class="card h-100 <?= $isAtual ? 'border-primary' : '' ?> <?= $isSuperado ? 'border-warning' : '' ?>">
                    
                    <!-- Faixa do mês atual (destaque azul) -->
                    <?php if ($isAtual): ?>
                        <div class="card-header bg-primary text-white py-1 text-center">
                            <small><i class="bi bi-star-fill"></i> Mês Atual</small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- NOVO: Faixa de troféu para metas superadas (se não é mês atual) -->
                    <?php if ($isSuperado && !$isAtual): ?>
                        <div class="card-header bg-warning text-dark py-1 text-center">
                            <small><i class="bi bi-trophy-fill"></i> Meta Superada!</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <!-- Título + Menu de ações -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0"><?= $mesAno ?></h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="<?= url("/metas/{$meta->getId()}") ?>" class="dropdown-item">
                                            <i class="bi bi-eye"></i> Ver Detalhes
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?= url("/metas/{$meta->getId()}/editar") ?>" class="dropdown-item">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                    </li>
                                    <li>
                                        <form action="<?= url("/metas/{$meta->getId()}/recalcular") ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-arrow-clockwise"></i> Recalcular
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="confirmarExclusao(<?= $meta->getId() ?>)">
                                            <i class="bi bi-trash"></i> Excluir
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Barra de Progresso -->
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-<?= $corBarra ?>" 
                                 style="width: <?= $porcentagemBarra ?>%;">
                            </div>
                        </div>
                        
                        <!-- Valores -->
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <!-- NOVO: Mostra porcentagem real (pode ser > 100%) -->
                            <span>
                                <?= number_format($porcentagemReal, 0) ?>%
                                <?php if ($porcentagemReal > 100): ?>
                                    <i class="bi bi-arrow-up-circle-fill text-success" title="Acima da meta!"></i>
                                <?php endif; ?>
                            </span>
                            <span><?= money($meta->getValorRealizado()) ?> / <?= money($meta->getValorMeta()) ?></span>
                        </div>
                        
                        <!-- ============================================ -->
                        <!-- STATUS DO CICLO DE VIDA                      -->
                        <!-- ============================================ -->
                        <?php if ($isSuperado): ?>
                            <!-- NOVO: Status 'superado' — badge dourado com troféu -->
                            <span class="badge <?= $statusBadgeClass ?>">
                                <i class="bi <?= $statusIcon ?>"></i> <?= $statusLabel ?>
                            </span>
                            <small class="text-muted ms-1">
                                (<?= number_format($porcentagemReal, 0) ?>% da meta)
                            </small>
                        <?php elseif ($foiAtingida): ?>
                            <!-- Meta batida (100-119%): verde -->
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill"></i> Meta Batida!
                            </span>
                        <?php else: ?>
                            <!-- Status normal do ciclo de vida -->
                            <span class="badge <?= $statusBadgeClass ?>">
                                <i class="bi <?= $statusIcon ?>"></i> <?= $statusLabel ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- MODAL DE EXCLUSÃO                            -->
<!-- ============================================ -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta meta?</p>
                <p class="text-danger small">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
/**
 * Abre modal de confirmação de exclusão
 * Define a action do form com o ID da meta
 */
function confirmarExclusao(id) {
    const form = document.getElementById('formExcluir');
    form.action = '<?= url('/metas/') ?>' + id + '/deletar';
    
    const modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
    modal.show();
}
</script>