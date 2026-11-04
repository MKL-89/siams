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

// Newest opportunities, shown to students who are not yet placed.
require_once __DIR__ . '/../includes/opportunities.php';
$placed = $placement && in_array($placement['status'], ['approved', 'ongoing', 'completed'], true);
$latest = $placed ? [] : array_slice(search_open_opportunities(['sort' => 'newest'])['rows'], 0, 3);

$page_title = 'Student dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading(trim($student['reg_no'] . ' · ' . $student['programme'], ' ·'), 'bi-mortarboard');
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Placement status', $placement ? ucfirst($placement['status']) : 'Not placed', 'bi-briefcase', 'applications.php');
    stat_card('Applications made', $applications, 'bi-send', 'applications.php');
    stat_card('Logbook entries', $entries, 'bi-journal-text', 'logbook.php');
    stat_card('Documents uploaded', $documents, 'bi-file-earmark-arrow-up', 'documents.php');
    ?>
</div>

<?php
// What the student should do next, depending on where they are in the process.
if ($placed) {
    next_step('bi-journal-check', 'Placed at ' . $placement['org_name'],
        'Fill in your logbook every week and upload your documents. Your supervisors will confirm your entries and assess you.',
        'Open my logbook', 'logbook.php', 'success');
} elseif ($placement && $placement['status'] === 'pending') {
    next_step('bi-hourglass-split', 'Waiting for the attachment office',
        $placement['org_name'] . ' has accepted you. The attachment office will now approve your placement.',
        'View my applications', 'applications.php', 'warning');
} elseif ($applications) {
    next_step('bi-send-check', 'Your applications are being considered',
        'Follow their progress under My applications, and keep applying for other opportunities.',
        'View my applications', 'applications.php');
} else {
    next_step('bi-search', 'Find your attachment',
        'Browse the open opportunities and apply for the ones that match your skills.',
        'Find opportunities', 'opportunities.php');
}

quick_actions([
    ['bi-search', 'Find opportunities', 'Search open attachment opportunities by keyword, county or sector.', 'opportunities.php'],
    ['bi-send', 'My applications', 'Follow each application and your placement status.', 'applications.php'],
    ['bi-journal-text', 'My logbook', 'Write your weekly logbook entries during the attachment.', 'logbook.php'],
    ['bi-award', 'My results', 'See your assessment scores and final attachment mark.', 'results.php'],
]);
?>

<?php if ($latest): ?>
    <div class="d-flex justify-content-between align-items-end mb-3">
        <h2 class="h5 fw-bold mb-0">New opportunities</h2>
        <a class="small" href="opportunities.php">See all<i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach ($latest as $op): ?>
            <div class="col-md-4">
                <div class="card h-100 opportunity-card">
                    <div class="card-body">
                        <span class="badge text-bg-light border mb-2"><?= e($op['sector']) ?></span>
                        <h3 class="h6 fw-bold mb-1"><?= e($op['title']) ?></h3>
                        <p class="text-muted small mb-0"><i class="bi bi-building me-1"></i><?= e($op['org_name']) ?> · <?= e($op['county']) ?></p>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center small">
                        <span class="text-danger"><i class="bi bi-calendar-event me-1"></i><?= nice_date($op['deadline']) ?></span>
                        <a class="btn btn-sm btn-outline-primary" href="opportunity.php?id=<?= (int) $op['opportunity_id'] ?>">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
