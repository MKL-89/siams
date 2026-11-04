<?php
/** Attachment coordinator dashboard: the state of attachment across the school. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('coordinator');

$students      = count_of("SELECT COUNT(*) FROM students s JOIN users u ON u.user_id = s.user_id WHERE u.status = 'active'");
$pending_stu   = count_of("SELECT COUNT(*) FROM students s JOIN users u ON u.user_id = s.user_id WHERE u.status = 'pending'");
$organizations = count_of('SELECT COUNT(*) FROM organizations');
$pending_apps  = count_of("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$unassigned    = count_of("SELECT COUNT(*) FROM placements WHERE status IN ('approved','ongoing') AND lecturer_id IS NULL");
$pending_orgs  = count_of("SELECT COUNT(*) FROM organizations WHERE status = 'pending'");
$pending_plac  = count_of("SELECT COUNT(*) FROM placements WHERE status = 'pending'");

$page_title = 'Coordinator dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading('Office of Industrial Linkages & Attachment', 'bi-clipboard-data');
?>
<?php if ($pending_orgs): ?>
    <div class="alert alert-warning">
        <i class="bi bi-building-exclamation me-1"></i><?= $pending_orgs ?> host organization registration(s) awaiting approval.
        <a href="organizations.php?status=pending">Review now</a>
    </div>
<?php endif; ?>
<?php if ($pending_stu): ?>
    <div class="alert alert-warning">
        <i class="bi bi-person-exclamation me-1"></i><?= $pending_stu ?> student account(s) awaiting approval.
        <a href="students.php?status=pending">Review now</a>
    </div>
<?php endif; ?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Registered students', $students, 'bi-mortarboard', 'students.php');
    stat_card('Host organizations', $organizations, 'bi-building', 'organizations.php');
    stat_card('Applications awaiting approval', $pending_apps, 'bi-hourglass-split', 'placements.php');
    stat_card('Placements without a supervisor', $unassigned, 'bi-person-exclamation', 'assign.php');
    ?>
</div>

<?php
if ($pending_plac) {
    next_step('bi-check2-square', $pending_plac . ' placement' . ($pending_plac === 1 ? '' : 's') . ' waiting for your approval',
        'Host organizations have accepted these students. Approve or reject each placement.', 'Review placements', 'placements.php', 'warning');
} elseif ($unassigned) {
    next_step('bi-person-plus', $unassigned . ' placement' . ($unassigned === 1 ? '' : 's') . ' without a university supervisor',
        'Assign a supervisor to each approved placement, sharing the students fairly.', 'Assign supervisors', 'assign.php', 'warning');
}

quick_actions([
    ['bi-check2-square', 'Placements', 'Approve or reject the placements accepted by host organizations.', 'placements.php'],
    ['bi-person-plus', 'Supervisors', 'Assign university supervisors to approved placements.', 'assign.php'],
    ['bi-mortarboard', 'Student accounts', 'See the students who have created accounts.', 'students.php'],
    ['bi-building', 'Organizations', 'See host organizations and reject any that are not genuine.', 'organizations.php'],
    ['bi-award', 'Results', 'See every student\'s scores and final attachment mark.', 'results.php'],
    ['bi-folder2-open', 'Documents', 'Open the acceptance letters and reports students uploaded.', 'documents.php'],
    ['bi-bar-chart', 'Reports', 'Print or export placement, workload and results reports.', 'reports.php'],
    ['bi-calendar-range', 'Attachment periods', 'Create attachment periods and choose the active one.', 'periods.php'],
]);
?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
