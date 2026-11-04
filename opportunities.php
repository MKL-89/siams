<?php
/**
 * Public list of open attachment opportunities. Anyone can browse and search;
 * an account is needed only to apply. Logged-in students use their own page.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/opportunities.php';
start_secure_session();

if (current_role() === 'student') {
    redirect('/student/opportunities.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
}

$result    = search_open_opportunities($_GET);
$detailUrl = BASE_URL . '/opportunity.php?id=';

$page_title = 'Attachment opportunities';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Attachment opportunities</h1>
        <p class="text-muted mb-0">Browse open opportunities from host organizations. Log in or create a student account when you are ready to apply.</p>
    </div>
    <?php if (!current_role()): ?>
        <a class="btn btn-siams" href="<?= BASE_URL ?>/register.php"><i class="bi bi-person-plus me-1"></i>Create a student account</a>
    <?php endif; ?>
</div>
<?php show_flash(); ?>
<?php require __DIR__ . '/includes/opportunity_list.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
