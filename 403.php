<?php
/** Shown by require_role() when a user opens a page that belongs to another role. */
require_once __DIR__ . '/includes/auth.php';
start_secure_session();
$page_title = 'Access denied';
require __DIR__ . '/includes/header.php';
?>
<div class="text-center py-5">
    <div class="display-4 text-danger mb-3"><i class="bi bi-shield-lock"></i></div>
    <h1 class="h3">Access denied</h1>
    <p class="text-muted">Your account does not have permission to open this page.</p>
    <?php if (current_role()): ?>
        <a class="btn btn-siams" href="<?= BASE_URL . ROLE_HOME[current_role()] ?>">Back to my dashboard</a>
    <?php else: ?>
        <a class="btn btn-siams" href="<?= BASE_URL ?>/login.php">Go to login</a>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
