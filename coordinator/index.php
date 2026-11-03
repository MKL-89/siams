<?php
/** Attachment coordinator dashboard: the state of attachment across the school. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('coordinator');

$students      = count_of('SELECT COUNT(*) FROM students');
$organizations = count_of('SELECT COUNT(*) FROM organizations');
$pending_apps  = count_of("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$unassigned    = count_of("SELECT COUNT(*) FROM placements WHERE status IN ('approved','ongoing') AND lecturer_id IS NULL");
$pending_orgs  = count_of("SELECT COUNT(*) FROM organizations WHERE status = 'pending'");

$page_title = 'Coordinator dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading('Office of Industrial Linkages & Attachment');
?>
<?php if ($pending_orgs): ?>
    <div class="alert alert-warning">
        <i class="bi bi-building-exclamation me-1"></i><?= $pending_orgs ?> host organization registration(s) awaiting approval.
        <a href="organizations.php?status=pending">Review now</a>
    </div>
<?php endif; ?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Registered students', $students, 'bi-mortarboard');
    stat_card('Host organizations', $organizations, 'bi-building');
    stat_card('Applications awaiting approval', $pending_apps, 'bi-hourglass-split');
    stat_card('Placements without a supervisor', $unassigned, 'bi-person-exclamation');
    ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
