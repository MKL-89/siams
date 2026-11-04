<?php
/** Student: view and search open attachment opportunities (FR-06). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_role('student');

$result    = search_open_opportunities($_GET);
$detailUrl = 'opportunity.php?id=';
$listIntro = 'Search the open attachment places, open one to read the details, and apply with one click.';
$listAction = '<a class="btn btn-outline-light btn-sm" href="applications.php"><i class="bi bi-send me-1"></i>My applications</a>';

$page_title = 'Find opportunities';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/opportunity_list.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
