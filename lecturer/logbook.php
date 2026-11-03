<?php
/** University supervisor: read an assigned student's logbook (FR-17). Read only. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/logbook.php';
require_role('lecturer');

$stmt = db()->prepare(
    'SELECT p.*, u.full_name, s.reg_no, o.name AS org_name
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     JOIN organizations o ON o.org_id = p.org_id
     WHERE p.placement_id = ? AND p.lecturer_id = ?'
);
$stmt->execute([(int) ($_GET['placement'] ?? 0), my_lecturer_id()]);
$pl = $stmt->fetch();
if (!$pl) {
    flash('That student is not assigned to you.', 'danger');
    redirect('/lecturer/students.php');
}

$entries    = logbook_entries((int) $pl['placement_id']);
$totalWeeks = count(placement_weeks($pl));
$confirmed  = count(array_filter($entries, fn($x) => $x['confirmed']));

$page_title = 'Logbook: ' . $pl['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<a href="students.php" class="small"><i class="bi bi-arrow-left me-1"></i>Back to my students</a>
<h1 class="h3 mt-2 mb-1">Logbook: <?= e($pl['full_name']) ?></h1>
<p class="text-muted"><?= e($pl['reg_no']) ?> · <?= e($pl['org_name']) ?> ·
    <?= count($entries) ?> of <?= $totalWeeks ?> weeks submitted, <?= $confirmed ?> confirmed by the industrial supervisor</p>
<?php if (!$entries): ?>
    <div class="alert alert-light border">No entries submitted yet.</div>
<?php endif; ?>
<div class="col-lg-9">
    <?php foreach ($entries as $en) { entry_card($en); } ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
