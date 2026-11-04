<?php
/**
 * Public details of one open opportunity. To apply, a visitor logs in or creates an
 * account; after logging in, a student is taken straight back to this opportunity.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/opportunities.php';
require_once __DIR__ . '/includes/opportunity_detail.php';
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
<?php opportunity_hero($op, BASE_URL . '/opportunities.php'); ?>
<div class="row g-4">
    <div class="col-lg-8">
        <?php opportunity_details($op, false); ?>
    </div>
    <div class="col-lg-4">
        <div class="card apply-card">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold">Apply for this opportunity</h2>
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
