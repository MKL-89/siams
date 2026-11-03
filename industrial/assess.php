<?php
/** Industrial supervisor: submit an online assessment of each student (FR-16). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/assessment.php';
require_role('industrial');

$indSupId = (int) my_supervisor()['ind_sup_id'];

$stmt = db()->prepare(
    "SELECT p.placement_id, u.full_name, s.reg_no, e.score, e.submitted_at,
            (SELECT COUNT(*) FROM logbook_entries l WHERE l.placement_id = p.placement_id AND l.confirmed = 1) AS confirmed_weeks
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     LEFT JOIN evaluations e ON e.placement_id = p.placement_id AND e.component = 'industrial'
     WHERE p.ind_sup_id = ? AND p.status IN ('approved','ongoing','completed')
     ORDER BY e.score IS NOT NULL, u.full_name"
);
$stmt->execute([$indSupId]);
$students = $stmt->fetchAll();

$placementId = (int) ($_GET['placement'] ?? 0);
$current = null;
foreach ($students as $st) {
    if ((int) $st['placement_id'] === $placementId) { $current = $st; }
}

$errors = [];
$existing = $current ? (placement_scores($placementId)['industrial'] ?? null) : null;
$values = ['score' => (string) ($existing['score'] ?? ''), 'comments' => $existing['comments'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$current) {
        flash('That student is not supervised by you.', 'danger');
        redirect('/industrial/assess.php');
    }
    $values = ['score' => post_str('score'), 'comments' => post_str('comments')];
    $errors = csrf_valid() ? validate_score($values['score'], $values['comments']) : ['Your form expired. Please try again.'];
    if (!$errors) {
        save_score($placementId, 'industrial', $values['score'], $values['comments']);
        flash('Assessment saved for ' . $current['full_name'] . '.');
        redirect('/industrial/assess.php');
    }
}

$page_title = 'Assessments';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Student assessments</h1>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="list-group shadow-sm">
            <?php if (!$students): ?>
                <div class="list-group-item text-muted">You do not have any students on attachment yet.</div>
            <?php endif; ?>
            <?php foreach ($students as $st): ?>
                <a href="?placement=<?= (int) $st['placement_id'] ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center<?= (int) $st['placement_id'] === $placementId ? ' active' : '' ?>">
                    <span><?= e($st['full_name']) ?><br><small><?= e($st['reg_no']) ?> · <?= (int) $st['confirmed_weeks'] ?> weeks confirmed</small></span>
                    <?= $st['score'] !== null ? '<span class="badge bg-success">' . e($st['score']) . '</span>' : '<span class="badge bg-warning text-dark">Not assessed</span>' ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-7">
        <?php if ($current): ?>
            <h2 class="h4 mb-3"><?= e($current['full_name']) ?></h2>
            <?php score_form('industrial', $existing, $values, $errors,
                'Rate the student\'s overall performance at your organization: skills, attitude, punctuality and quality of work.'); ?>
        <?php elseif ($students): ?>
            <div class="alert alert-light border">Choose a student to assess.</div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
