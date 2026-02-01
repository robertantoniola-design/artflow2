<?php
/**
 * Componente: Alertas Flash
 * Exibe mensagens de feedback (success, error, warning, info)
 * 
 * Uso:
 * <?php include __DIR__ . '/../components/alerts.php'; ?>
 * 
 * Depende das funções session() e flash() dos helpers.
 */

// Obtém mensagens flash
$flashSuccess = $_SESSION['_flash']['success'] ?? null;
$flashError = $_SESSION['_flash']['error'] ?? null;
$flashWarning = $_SESSION['_flash']['warning'] ?? null;
$flashInfo = $_SESSION['_flash']['info'] ?? null;
$flashErrors = $_SESSION['_flash']['errors'] ?? [];

// Limpa mensagens exibidas
unset(
    $_SESSION['_flash']['success'],
    $_SESSION['_flash']['error'],
    $_SESSION['_flash']['warning'],
    $_SESSION['_flash']['info'],
    $_SESSION['_flash']['errors']
);
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?= e($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>
<?php endif; ?>

<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= e($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>
<?php endif; ?>

<?php if ($flashWarning): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <?= e($flashWarning) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>
<?php endif; ?>

<?php if ($flashInfo): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <?= e($flashInfo) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>
<?php endif; ?>

<?php if (!empty($flashErrors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Por favor, corrija os erros:
    </h6>
    <ul class="mb-0 ps-3">
        <?php foreach ($flashErrors as $field => $error): ?>
            <li><?= e(is_array($error) ? implode(', ', $error) : $error) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>
<?php endif; ?>
