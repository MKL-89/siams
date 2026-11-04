<?php
/** University supervisor dashboard: students assigned to this lecturer. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('lecturer');

$me = 'SELECT lecturer_id FROM lecturers WHERE user_id = ?';
$uid = [$_SESSION['user_id']];

$assigned = count_of("SELECT COUNT(*) FROM placements WHERE lecturer_id = ($me)", $uid);
$visits   = count_of("SELECT COUNT(*) FROM supervision_visits WHERE lecturer_id = ($me)", $uid);
$to_mark  = count_of(
    "SELECT COUNT(*) FROM placements p
     WHERE p.lecturer_id = ($me)
       AND NOT EXISTS (SELECT 1 FROM evaluations e WHERE e.placement_id = p.placement_id AND e.component = 'university')",
    $uid
);

// The supervisor's students, with quick links to the three tasks for each one.
$stmt = db()->prepare(
    "SELECT p.placement_id, u.full_name, s.reg_no, o.name AS org_name, o.county,
            (SELECT COUNT(*) FROM supervision_visits v WHERE v.placement_id = p.placement_id) AS visits,
            EXISTS (SELECT 1 FROM evaluations e WHERE e.placement_id = p.placement_id AND e.component = 'university') AS assessed
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     JOIN organizations o ON o.org_id = p.org_id
     WHERE p.lecturer_id = ($me) AND p.status IN ('approved','ongoing','completed')
     ORDER BY assessed, u.full_name
     LIMIT 6"
);
$stmt->execute($uid);
$my_students = $stmt->fetchAll();

$page_title = 'Supervisor dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading('University supervisor', 'bi-person-workspace');
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Students assigned to me', $assigned, 'bi-people', 'students.php');
    stat_card('Supervision visits recorded', $visits, 'bi-geo-alt', 'students.php');
    stat_card('Students not yet assessed', $to_mark, 'bi-clipboard-check', 'students.php');
    ?>
</div>

<?php if ($to_mark): ?>
    <?php next_step('bi-clipboard-check', $to_mark . ' student' . ($to_mark === 1 ? '' : 's') . ' still to assess',
        'Record your supervision visits and give each student the university supervisor\'s score and the logbook score.',
        'Open my students', 'students.php', 'warning'); ?>
<?php elseif (!$assigned): ?>
    <?php next_step('bi-hourglass', 'No students assigned yet',
        'The attachment office will assign students to you once their placements are approved.'); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="h5 fw-bold mb-0">My students</h2>
    <a class="small" href="students.php">See all<i class="bi bi-arrow-right ms-1"></i></a>
</div>
<?php if (!$my_students): ?>
    <div class="card mb-4"><div class="card-body text-muted">No students have been assigned to you yet.</div></div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ($my_students as $st): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h3 class="h6 fw-bold mb-0"><?= e($st['full_name']) ?></h3>
                            <?= $st['assessed'] ? '<span class="badge bg-success">Assessed</span>' : '<span class="badge bg-warning text-dark">To assess</span>' ?>
                        </div>
                        <p class="small text-muted mb-2"><?= e($st['reg_no']) ?><br><i class="bi bi-building me-1"></i><?= e($st['org_name']) ?> · <?= e($st['county']) ?></p>
                        <p class="small mb-0"><i class="bi bi-geo-alt me-1"></i><?= (int) $st['visits'] ?> visit(s) recorded</p>
                    </div>
                    <div class="card-footer bg-white d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="logbook.php?placement=<?= (int) $st['placement_id'] ?>">Logbook</a>
                        <a class="btn btn-sm btn-outline-secondary" href="visits.php?placement=<?= (int) $st['placement_id'] ?>">Visits</a>
                        <a class="btn btn-sm btn-siams" href="assess.php?placement=<?= (int) $st['placement_id'] ?>">Assess</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
