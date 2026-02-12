<?php
/**
 * ============================================
 * VIEW: Detalhes da Tag (Melhoria 3 + Melhoria 4)
 * ============================================
 * GET /tags/{id}
 * 
 * Variáveis:
 * - $tag: Objeto Tag (com métodos getDescricao(), getIcone(), hasDescricao(), hasIcone(), getStyleInline())
 * - $artes: Array de arrays associativos (FETCH_ASSOC — usar $arte['campo'])
 * - $todasTags: Array de Tag objects com artesCount (Melhoria 4 — para dropdown de merge)
 * 
 * LEMBRETE: $artes vem como arrays, NÃO objetos Arte.
 * Sempre usar $arte['nome'], nunca $arte->getNome().
 * 
 * MELHORIAS:
 * - [Melhoria 3] Descrição + ícone no badge + card de descrição
 * - [Melhoria 4] Card "Mesclar Tag" + modal de confirmação + JavaScript
 * 
 * CORREÇÕES VISUAIS (M4 v2):
 * - Botão merge: cinza (btn-secondary) quando desabilitado, amarelo (btn-warning) quando ativo
 * - Badge destino no modal: inline style (sem bg-secondary que usa !important)
 * - Badge origem no modal: fallback caso getStyleInline() retorne vazio
 * - Estrutura HTML: card merge FORA do card Ações (aninhamento correto)
 */
$currentPage = 'tags';
$totalArtes = count($artes ?? []);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/tags') ?>">Tags</a></li>
        <li class="breadcrumb-item active"><?= e($tag->getNome()) ?></li>
    </ol>
</nav>

<!-- Header com badge (agora com ícone) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <!-- Badge grande com ícone (Melhoria 3) -->
        <span class="badge fs-3 py-2 px-3" style="<?= $tag->getStyleInline() ?>">
            <?php if ($tag->hasIcone()): ?>
                <i class="<?= e($tag->getIcone()) ?> me-1"></i>
            <?php endif; ?>
            <?= e($tag->getNome()) ?>
        </span>
        <span class="text-muted"><?= $totalArtes ?> arte(s) associada(s)</span>
    </div>
    
    <div class="btn-group">
        <a href="<?= url('/tags/' . $tag->getId() . '/editar') ?>" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="row">
    <!-- ══════════════════════════════════════════════════ -->
    <!-- COLUNA PRINCIPAL: Artes Associadas                -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-palette"></i> Artes com esta Tag
                </h5>
                <a href="<?= url('/artes?tag_id=' . $tag->getId()) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Ver no módulo Artes
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($artes)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-image text-muted fs-1"></i>
                        <p class="text-muted mt-2">Nenhuma arte com esta tag</p>
                        <a href="<?= url('/artes/criar') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Criar Arte
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th>Preço Custo</th>
                                    <th>Horas</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($artes as $arte): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/artes/' . $arte['id']) ?>" class="text-decoration-none">
                                                <?= e($arte['nome']) ?>
                                            </a>
                                            <?php if (!empty($arte['descricao'])): ?>
                                                <br><small class="text-muted"><?= e(mb_substr($arte['descricao'] ?? '', 0, 60)) ?><?= mb_strlen($arte['descricao'] ?? '') > 60 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusLabels = [
                                                'disponivel' => '<span class="badge bg-success">Disponível</span>',
                                                'em_producao' => '<span class="badge bg-warning text-dark">Em Produção</span>',
                                                'vendida' => '<span class="badge bg-info">Vendida</span>',
                                                'reservada' => '<span class="badge bg-secondary">Reservada</span>',
                                            ];
                                            echo $statusLabels[$arte['status']] ?? '<span class="badge bg-light text-dark">' . e($arte['status']) . '</span>';
                                            ?>
                                        </td>
                                        <td><?= 'R$ ' . number_format((float)($arte['preco_custo'] ?? 0), 2, ',', '.') ?></td>
                                        <td><?= number_format((float)($arte['horas_trabalhadas'] ?? 0), 1, ',', '.') ?>h</td>
                                        <td class="text-end">
                                            <a href="<?= url('/artes/' . $arte['id']) ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /col-lg-8 -->
    
    <!-- ══════════════════════════════════════════════════ -->
    <!-- COLUNA LATERAL: Informações + Ações + Merge       -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="col-lg-4">
        
        <!-- ── Card de Informações ── -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> Informações
                </h5>
            </div>
            <div class="card-body">
                <!-- Cor -->
                <div class="mb-3">
                    <small class="text-muted d-block">Cor</small>
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded" 
                              style="width: 24px; height: 24px; display: inline-block; background-color: <?= e($tag->getCor()) ?>;">
                        </span>
                        <code><?= e($tag->getCor()) ?></code>
                    </div>
                </div>
                
                <!-- Ícone (Melhoria 3) -->
                <div class="mb-3">
                    <small class="text-muted d-block">Ícone</small>
                    <?php if ($tag->hasIcone()): ?>
                        <div class="d-flex align-items-center gap-2">
                            <i class="<?= e($tag->getIcone()) ?> fs-5"></i>
                            <code><?= e($tag->getIcone()) ?></code>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic">Sem ícone</span>
                    <?php endif; ?>
                </div>
                
                <!-- Total de Artes -->
                <div class="mb-3">
                    <small class="text-muted d-block">Total de Artes</small>
                    <span class="fs-5 fw-bold"><?= $totalArtes ?></span>
                </div>
                
                <!-- Datas -->
                <div class="mb-3">
                    <small class="text-muted d-block">Criada em</small>
                    <span><?= $tag->getCreatedAt() ? date_br($tag->getCreatedAt()) : '—' ?></span>
                </div>
                
                <?php if ($tag->getUpdatedAt()): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Última atualização</small>
                        <span><?= datetime_br($tag->getUpdatedAt()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- /card Informações -->
        
        <!-- ── Card de Descrição (Melhoria 3) ── -->
        <?php if ($tag->hasDescricao()): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-text-paragraph"></i> Descrição
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(e($tag->getDescricao())) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- ── Card de Ações Rápidas ── -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning"></i> Ações
                </h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="<?= url('/tags/' . $tag->getId() . '/editar') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil"></i> Editar Tag
                </a>
                <a href="<?= url('/artes?tag_id=' . $tag->getId()) ?>" class="btn btn-outline-info">
                    <i class="bi bi-images"></i> Ver Artes com esta Tag
                </a>
                <form action="<?= url('/tags/' . $tag->getId()) ?>" method="POST"
                      onsubmit="return confirm('Excluir a tag \'<?= e($tag->getNome()) ?>\'? Associações com artes serão removidas.');">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Excluir Tag
                    </button>
                </form>
            </div>
        </div><!-- /card Ações — FECHADO CORRETAMENTE -->

        <!-- ══════════════════════════════════════════════════ -->
        <!-- MELHORIA 4: Card de Merge (Mesclar Tags)          -->
        <!-- POSIÇÃO: APÓS o card Ações, DENTRO da col-lg-4    -->
        <!-- ══════════════════════════════════════════════════ -->
        <?php 
        // Filtra lista de tags removendo a tag atual (não pode mesclar consigo mesma)
        $tagsParaMerge = array_filter($todasTags ?? [], function($t) use ($tag) {
            return $t->getId() !== $tag->getId();
        });
        ?>
        
        <?php if (!empty($tagsParaMerge)): ?>
        <div class="card border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="card-title mb-0">
                    <i class="bi bi-arrow-left-right text-warning"></i> Mesclar Tag
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Transfere todas as artes desta tag para outra tag escolhida.
                    A tag "<strong><?= e($tag->getNome()) ?></strong>" será <strong class="text-danger">excluída</strong> após a mesclagem.
                </p>
                
                <!-- Select de tag destino -->
                <div class="mb-3">
                    <label for="tagDestinoSelect" class="form-label small fw-bold">
                        Mesclar com:
                    </label>
                    <select id="tagDestinoSelect" class="form-select form-select-sm">
                        <option value="">— Selecione a tag de destino —</option>
                        <?php foreach ($tagsParaMerge as $tagOpcao): ?>
                            <option value="<?= $tagOpcao->getId() ?>"
                                    data-nome="<?= e($tagOpcao->getNome()) ?>"
                                    data-cor="<?= e($tagOpcao->getCor()) ?>"
                                    data-artes="<?= $tagOpcao->getArtesCount() ?>">
                                <?= e($tagOpcao->getNome()) ?> 
                                (<?= $tagOpcao->getArtesCount() ?> arte<?= $tagOpcao->getArtesCount() !== 1 ? 's' : '' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- CORREÇÃO: Botão inicia CINZA (btn-secondary) quando desabilitado -->
                <!-- JavaScript alterna para btn-warning quando uma tag é selecionada  -->
                <button type="button" 
                        id="btnAbrirModalMerge" 
                        class="btn btn-secondary btn-sm w-100" 
                        disabled
                        onclick="abrirModalMerge()">
                    <i class="bi bi-arrow-left-right"></i> Mesclar Tags
                </button>
            </div>
        </div><!-- /card Merge -->
        <?php endif; ?>
        
    </div><!-- /col-lg-4 -->
</div><!-- /row -->

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- MELHORIA 4: Modal de Confirmação de Merge                    -->
<!-- Posicionado FORA da row para evitar problemas de layout      -->
<!-- ══════════════════════════════════════════════════════════════ -->
<?php
// Prepara style inline da tag origem com fallback
// Garante que o badge sempre terá cor, mesmo se getStyleInline() retornar vazio
$styleOrigem = $tag->getStyleInline();
if (empty(trim($styleOrigem))) {
    $styleOrigem = "background-color: " . e($tag->getCor()) . "; color: " . e($tag->getCorTexto()) . ";";
}
?>
<div class="modal fade" id="modalMerge" tabindex="-1" aria-labelledby="modalMergeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title" id="modalMergeLabel">
                    <i class="bi bi-arrow-left-right text-warning"></i> Confirmar Mesclagem
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <!-- Resumo visual: [Tag Origem] → [Tag Destino] -->
                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                    
                    <!-- Tag Origem (esta tag — renderizada no servidor) -->
                    <div class="text-center">
                        <!-- CORREÇÃO: usa $styleOrigem com fallback -->
                        <span class="badge fs-6 py-2 px-3" style="<?= $styleOrigem ?>">
                            <?php if ($tag->hasIcone()): ?>
                                <i class="<?= e($tag->getIcone()) ?> me-1"></i>
                            <?php endif; ?>
                            <?= e($tag->getNome()) ?>
                        </span>
                        <br>
                        <small class="text-muted"><?= $totalArtes ?> arte(s)</small>
                    </div>
                    
                    <!-- Seta indicando direção do merge -->
                    <div class="text-warning fs-4">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                    
                    <!-- Tag Destino (preenchida via JavaScript ao abrir o modal) -->
                    <div class="text-center">
                        <!-- CORREÇÃO: inline style em vez de bg-secondary (que usa !important) -->
                        <!-- O JavaScript consegue sobrescrever style inline normalmente         -->
                        <span id="mergeDestinoBadge" class="badge fs-6 py-2 px-3" 
                              style="background-color: #6c757d; color: #FFFFFF;">
                            ?
                        </span>
                        <br>
                        <small class="text-muted"><span id="mergeDestinoArtes">0</span> arte(s)</small>
                    </div>
                </div>
                
                <!-- Alerta com detalhes da operação -->
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Atenção:</strong> Esta ação é irreversível.
                    <ul class="mb-0 mt-1">
                        <li>As artes de "<strong><?= e($tag->getNome()) ?></strong>" serão transferidas para a tag selecionada.</li>
                        <li>Artes que já possuem ambas as tags terão a duplicata ignorada.</li>
                        <li>A tag "<strong><?= e($tag->getNome()) ?></strong>" será <strong>excluída permanentemente</strong>.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <!-- Formulário de merge (POST /tags/{id}/merge) -->
                <form id="formMerge" action="<?= url('/tags/' . $tag->getId() . '/merge') ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="tag_destino_id" id="mergeDestinoId" value="">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-left-right"></i> Confirmar Mesclagem
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- MELHORIA 4: JavaScript — Controle do Merge                   -->
<!-- ══════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('tagDestinoSelect');
    const btnMerge = document.getElementById('btnAbrirModalMerge');
    
    // Só executa se os elementos existem (caso não haja tags para merge)
    if (!select || !btnMerge) return;
    
    /**
     * CORREÇÃO: Toggle de classe do botão
     * - Sem seleção → btn-secondary (cinza) + disabled
     * - Com seleção → btn-warning (amarelo) + enabled
     */
    select.addEventListener('change', function() {
        if (this.value === '') {
            btnMerge.disabled = true;
            btnMerge.classList.remove('btn-warning');
            btnMerge.classList.add('btn-secondary');
        } else {
            btnMerge.disabled = false;
            btnMerge.classList.remove('btn-secondary');
            btnMerge.classList.add('btn-warning');
        }
    });
});

/**
 * Abre o modal de confirmação de merge.
 * Preenche o badge da tag destino com cor e nome via data-attributes.
 */
function abrirModalMerge() {
    const select = document.getElementById('tagDestinoSelect');
    const option = select.options[select.selectedIndex];
    
    if (!option || !option.value) return;
    
    // Extrai dados do option selecionado
    const destinoId    = option.value;
    const destinoNome  = option.getAttribute('data-nome');
    const destinoCor   = option.getAttribute('data-cor');
    const destinoArtes = option.getAttribute('data-artes');
    
    // CORREÇÃO: Atualiza badge destino via inline style
    // Funciona porque o badge NÃO tem classe bg-* (que usa !important)
    const badge = document.getElementById('mergeDestinoBadge');
    badge.textContent = destinoNome;
    badge.style.backgroundColor = destinoCor;
    
    // Calcula contraste automático (luminância ITU-R BT.601)
    // Mesmo algoritmo usado no PHP pelo Tag::getCorTexto()
    const hex = destinoCor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    badge.style.color = luminance > 0.5 ? '#000000' : '#FFFFFF';
    
    // Atualiza contagem de artes do destino
    document.getElementById('mergeDestinoArtes').textContent = destinoArtes;
    
    // Configura o ID no formulário hidden
    document.getElementById('mergeDestinoId').value = destinoId;
    
    // Abre o modal Bootstrap 5
    new bootstrap.Modal(document.getElementById('modalMerge')).show();
}
</script>