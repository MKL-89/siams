<?php
/** Student: full details of one opportunity, with the Apply button (FR-07). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_once __DIR__ . '/../includes/placements.php';
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
<a href="opportunities.php" class="small"><i class="bi bi-arrow-left me-1"></i>Back to opportunities</a>
<?php show_flash(); ?>
<div class="card shadow-sm mt-2">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h3 mb-1"><?= e($op['title']) ?></h1>
                <p class="text-muted mb-3"><?= e($op['org_name']) ?> · <?= e($op['sector']) ?></p>
            </div>
            <?= is_accepting($op) ? status_badge('open') : status_badge('closed') ?>
        </div>
        <div class="row mb-3 small">
            <div class="col-sm-4"><strong>Location:</strong> <?= e($op['address']) ?>, <?= e($op['county']) ?></div>
            <div class="col-sm-4"><strong>Slots:</strong> <?= (int) $op['slots'] ?></div>
            <div class="col-sm-4"><strong>Apply by:</strong> <?= nice_date($op['deadline']) ?></div>
        </div>
        <h2 class="h6">Description</h2>
        <p style="white-space: pre-line"><?= e($op['description']) ?></p>
        <h2 class="h6">Contact</h2>
        <p class="small mb-0"><?= e($op['contact_email']) ?> · <?= e($op['contact_phone']) ?></p>
    </div>
    <div class="card-footer bg-white">
        <?php if ($reason === null): ?>
            <form method="post" onsubmit="return confirm('Send your application for this opportunity?');">
                <?= csrf_field() ?>
                <button class="btn btn-siams"><i class="bi bi-send me-1"></i>Apply for this opportunity</button>
            </form>
        <?php else: ?>
            <span class="text-muted small"><i class="bi bi-info-circle me-1"></i><?= e($reason) ?></span>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
