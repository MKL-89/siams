<?php
/**
 * Public details of one open opportunity. To apply, a visitor logs in or creates an
 * account; after logging in, a student is taken straight back to this opportunity.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/opportunities.php';
start_secure_session();

$oppId = (int) ($_GET['id'] ?? 0);

// Students already logged in use the student page, which has the Apply button.
if (current_role() === 'student') {
    redirect('/student/opportunity.php?id=' . $oppId);
}

$stmt = db()->prepare(
    "SELECT op.*, o.name AS org_name, o.county, o.sector, o.address
     FROM opportunities op JOIN organizations o ON o.org_id = op.org_id
     WHERE op.opportunity_id = ? AND o.status = 'approved'"
);
$stmt->execute([$oppId]);
$op = $stmt->fetch();

// Only open opportunities are shown to the public.
if (!$op || !is_accepting($op)) {
    flash('That opportunity is no longer open for applications.', 'warning');
    redirect('/opportunities.php');
}

$page_title = $op['title'];
require __DIR__ . '/includes/header.php';
?>
<a href="<?= BASE_URL ?>/opportunities.php" class="small"><i class="bi bi-arrow-left me-1"></i>Back to opportunities</a>
<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h1 class="h3 mb-1"><?= e($op['title']) ?></h1>
                        <p class="text-muted mb-3"><i class="bi bi-building me-1"></i><?= e($op['org_name']) ?> · <?= e($op['sector']) ?></p>
                    </div>
                    <?= status_badge('open') ?>
                </div>
                <div class="row mb-3 small g-2">
                    <div class="col-sm-4"><i class="bi bi-geo-alt me-1"></i><strong>Location:</strong> <?= e($op['address']) ?>, <?= e($op['county']) ?></div>
                    <div class="col-sm-4"><i class="bi bi-people me-1"></i><strong>Slots:</strong> <?= (int) $op['slots'] ?></div>
                    <div class="col-sm-4 text-danger"><i class="bi bi-calendar-event me-1"></i><strong>Apply by:</strong> <?= nice_date($op['deadline']) ?></div>
                </div>
                <h2 class="h6">Description</h2>
                <p class="mb-0" style="white-space: pre-line"><?= e($op['description']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 apply-card">
            <div class="card-body">
                <h2 class="h5">Apply for this opportunity</h2>
                <?php if (!current_role()): ?>
                    <p class="small text-muted">You need a student account to apply. Log in, or create an account if you do not have one yet.</p>
                    <a class="btn btn-siams w-100 mb-2" href="<?= BASE_URL ?>/login.php?apply=<?= $oppId ?>">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Log in to apply
                    </a>
                    <a class="btn btn-outline-primary w-100" href="<?= BASE_URL ?>/register.php?apply=<?= $oppId ?>">
                        <i class="bi bi-person-plus me-1"></i>Create a student account
                    </a>
                    <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Creating an account takes a
                        minute, and you are brought straight back here to apply.</p>
                <?php else: ?>
                    <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Only students can apply for attachment
                        opportunities. You are logged in as <?= e(ROLE_LABEL[current_role()]) ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
