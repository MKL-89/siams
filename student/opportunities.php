<?php
/** Student: view and search open attachment opportunities (FR-06). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_role('student');

$result    = search_open_opportunities($_GET);
$detailUrl = 'opportunity.php?id=';

$page_title = 'Find opportunities';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Attachment opportunities</h1>
<?php require __DIR__ . '/../includes/opportunity_list.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
