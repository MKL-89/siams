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
$listIntro = 'Browse open opportunities from host organizations. Log in or create a student account when you are ready to apply.';
$listAction = current_role() ? '' : '<a class="btn btn-light btn-sm text-siams fw-semibold" href="' . BASE_URL . '/register.php"><i class="bi bi-person-plus me-1"></i>Create a student account</a>';

$page_title = 'Attachment opportunities';
require __DIR__ . '/includes/header.php';
?>
<?php show_flash(); ?>
<?php require __DIR__ . '/includes/opportunity_list.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
