<?php
/** Student dashboard: the student's own attachment progress. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('student');

$stmt = db()->prepare('SELECT student_id, reg_no, programme FROM students WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch() ?: ['student_id' => 0, 'reg_no' => '', 'programme' => ''];
$sid = [$student['student_id']];

$stmt = db()->prepare(
    'SELECT p.status, o.name AS org_name FROM placements p
     JOIN organizations o ON o.org_id = p.org_id
     WHERE p.student_id = ?
     ORDER BY FIELD(p.status, \'ongoing\', \'approved\', \'completed\', \'pending\', \'rejected\'), p.created_at DESC
     LIMIT 1'
);
$stmt->execute($sid);
$placement = $stmt->fetch();

$applications = count_of('SELECT COUNT(*) FROM applications WHERE student_id = ?', $sid);
$entries      = count_of('SELECT COUNT(*) FROM logbook_entries l JOIN placements p ON p.placement_id = l.placement_id WHERE p.student_id = ?', $sid);
$documents    = count_of('SELECT COUNT(*) FROM documents WHERE student_id = ?', $sid);

$page_title = 'Student dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading($student['reg_no'] . ' · ' . $student['programme']);
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Placement status', $placement ? ucfirst($placement['status']) : 'Not placed', 'bi-briefcase');
    stat_card('Applications made', $applications, 'bi-send');
    stat_card('Logbook entries', $entries, 'bi-journal-text');
    stat_card('Documents uploaded', $documents, 'bi-file-earmark-arrow-up');
    ?>
</div>
<?php if ($placement): ?>
    <p class="text-muted">Current placement: <strong><?= e($placement['org_name']) ?></strong></p>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
