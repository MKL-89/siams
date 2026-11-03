<?php
/** Student: supervisors' remarks, scores and the final attachment result (FR-11). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/assessment.php';
require_role('student');

$stmt = db()->prepare(
    "SELECT p.*, o.name AS org_name FROM placements p JOIN organizations o ON o.org_id = p.org_id
     WHERE p.student_id = ? AND p.status IN ('approved','ongoing','completed')
     ORDER BY p.start_date DESC LIMIT 1"
);
$stmt->execute([my_student_id()]);
$pl = $stmt->fetch();

$weights = mark_weights();
$scores  = $pl ? placement_scores((int) $pl['placement_id']) : [];
$final   = final_mark($scores, $weights);

$page_title = 'My results';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My results</h1>
<?php if (!$pl): ?>
    <div class="alert alert-light border">Your results will appear here once you have an approved placement and have been assessed.</div>
<?php else: ?>
    <p class="text-muted"><?= e($pl['org_name']) ?> · <?= nice_date($pl['start_date']) ?> to <?= nice_date($pl['end_date']) ?></p>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted small">Final attachment mark</div>
                <?php if ($final !== null): ?>
                    <div class="display-6 fw-semibold text-primary"><?= number_format($final, 2) ?> <span class="fs-5 text-muted">/ 100</span></div>
                <?php else: ?>
                    <div class="fs-5">Not yet available</div>
                    <div class="small text-muted"><?= count($scores) ?> of 3 assessments submitted. Your final mark appears once all three are in.</div>
                <?php endif; ?>
            </div>
            <i class="bi bi-award fs-1 text-primary opacity-50"></i>
        </div>
    </div>
    <div class="row g-3">
        <?php foreach (COMPONENTS as $c => $label): ?>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1"><?= e($label) ?></h2>
                        <div class="small text-muted mb-2">Weight: <?= e((string) $weights[$c]) ?>%</div>
                        <?php if (isset($scores[$c])): ?>
                            <div class="fs-4 fw-semibold"><?= e($scores[$c]['score']) ?> <span class="fs-6 text-muted">/ 100</span></div>
                            <p class="small mb-1" style="white-space: pre-line"><?= e($scores[$c]['comments']) ?></p>
                            <div class="small text-muted"><?= e($scores[$c]['evaluator_name']) ?>, <?= nice_date($scores[$c]['submitted_at']) ?></div>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Not yet submitted</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
