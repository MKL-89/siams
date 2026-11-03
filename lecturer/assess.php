<?php
/** University supervisor: assess an assigned student and mark the logbook (FR-19). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/logbook.php';
require_once __DIR__ . '/../includes/assessment.php';
require_once __DIR__ . '/../includes/documents.php';
require_role('lecturer');

$stmt = db()->prepare(
    'SELECT p.*, u.full_name, s.reg_no, o.name AS org_name
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     JOIN organizations o ON o.org_id = p.org_id
     WHERE p.placement_id = ? AND p.lecturer_id = ? AND p.status IN (\'approved\',\'ongoing\',\'completed\')'
);
$stmt->execute([(int) ($_GET['placement'] ?? 0), my_lecturer_id()]);
$pl = $stmt->fetch();
if (!$pl) {
    flash('That student is not assigned to you.', 'danger');
    redirect('/lecturer/students.php');
}
$placementId = (int) $pl['placement_id'];

$scores = placement_scores($placementId);
$values = [];
$errors = ['university' => [], 'logbook' => []];
foreach (['university', 'logbook'] as $c) {
    $values[$c] = ['score' => (string) ($scores[$c]['score'] ?? ''), 'comments' => $scores[$c]['comments'] ?? ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $component = $_POST['component'] ?? '';
    if (!in_array($component, ['university', 'logbook'], true)) {
        flash('The request could not be processed. Please try again.', 'danger');
        redirect('/lecturer/assess.php?placement=' . $placementId);
    }
    $values[$component] = ['score' => post_str('score'), 'comments' => post_str('comments')];
    $errors[$component] = csrf_valid()
        ? validate_score($values[$component]['score'], $values[$component]['comments'])
        : ['Your form expired. Please try again.'];
    if (!$errors[$component]) {
        save_score($placementId, $component, $values[$component]['score'], $values[$component]['comments']);
        flash(COMPONENTS[$component] . ' saved.');
        redirect('/lecturer/assess.php?placement=' . $placementId);
    }
}

$entries   = logbook_entries($placementId);
$confirmed = count(array_filter($entries, fn($x) => $x['confirmed']));
$weeks     = count(placement_weeks($pl));
$stmt      = db()->prepare('SELECT COUNT(*) FROM supervision_visits WHERE placement_id = ?');
$stmt->execute([$placementId]);
$visits    = (int) $stmt->fetchColumn();
$industrial = $scores['industrial'] ?? null;

$page_title = 'Assess: ' . $pl['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<a href="students.php" class="small"><i class="bi bi-arrow-left me-1"></i>Back to my students</a>
<h1 class="h3 mt-2 mb-1">Assess: <?= e($pl['full_name']) ?></h1>
<p class="text-muted"><?= e($pl['reg_no']) ?> · <?= e($pl['org_name']) ?></p>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-8">
        <?php score_form('university', $scores['university'] ?? null, $values['university'], $errors['university'],
            'Your assessment of the student based on supervision visits and calls.'); ?>
        <?php score_form('logbook', $scores['logbook'] ?? null, $values['logbook'], $errors['logbook'],
            'Mark the completeness and quality of the student\'s weekly logbook.'); ?>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body small">
                <h2 class="h6">Evidence</h2>
                <p class="mb-1">Logbook: <?= count($entries) ?> of <?= $weeks ?> weeks submitted, <?= $confirmed ?> confirmed
                    · <a href="logbook.php?placement=<?= $placementId ?>">read logbook</a></p>
                <p class="mb-1">Supervision visits: <?= $visits ?> · <a href="visits.php?placement=<?= $placementId ?>">view visits</a></p>
                <p class="mb-0">Industrial supervisor's score:
                    <?= $industrial ? e($industrial['score']) . ' / 100' : 'not yet submitted' ?></p>
                <h2 class="h6 mt-3">Documents</h2>
                <?php document_links(placement_documents($placementId)); ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
