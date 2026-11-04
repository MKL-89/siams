<?php
/** Student: full details of one opportunity, with the Apply button (FR-07). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/opportunity_detail.php';
require_role('student');

$oppId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT op.*, o.name AS org_name, o.county, o.sector, o.address, o.contact_email, o.contact_phone
     FROM opportunities op JOIN organizations o ON o.org_id = op.org_id
     WHERE op.opportunity_id = ? AND o.status = 'approved'"
);
$stmt->execute([$oppId]);
$op = $stmt->fetch();
if (!$op) {
    flash('Opportunity not found.', 'danger');
    redirect('/student/opportunities.php');
}

$studentId = my_student_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = csrf_valid() ? cannot_apply_reason($studentId, $op) : 'Your form expired. Please try again.';
    if ($reason === null) {
        db()->prepare('INSERT INTO applications (student_id, opportunity_id) VALUES (?, ?)')->execute([$studentId, $oppId]);
        flash('Application sent. You can follow its progress under My applications.');
        redirect('/student/applications.php');
    }
    flash($reason, 'warning');
    redirect('/student/opportunity.php?id=' . $oppId);
}

$reason = cannot_apply_reason($studentId, $op);

$page_title = $op['title'];
require __DIR__ . '/../includes/header.php';
?>
<?php opportunity_hero($op, 'opportunities.php'); ?>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-8">
        <?php opportunity_details($op, true); ?>
    </div>
    <div class="col-lg-4">
        <div class="card apply-card">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold">Apply for this opportunity</h2>
                <?php if ($reason === null): ?>
                    <p class="small text-muted">Your application goes straight to <?= e($op['org_name']) ?>. You can follow it under My applications.</p>
                    <form method="post" onsubmit="return confirm('Send your application for this opportunity?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-siams w-100"><i class="bi bi-send me-1"></i>Apply for this opportunity</button>
                    </form>
                <?php else: ?>
                    <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i><?= e($reason) ?></p>
                <?php endif; ?>
                <hr>
                <a class="small" href="applications.php"><i class="bi bi-send me-1"></i>My applications</a>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
