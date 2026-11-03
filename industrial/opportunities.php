<?php
/** Industrial supervisor: the organization's attachment opportunities (FR-13). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_role('industrial');

$stmt = db()->prepare('SELECT org_id FROM ind_supervisors WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$orgId = (int) $stmt->fetchColumn();

handle_status_toggle($orgId, '/industrial/opportunities.php');

$stmt = db()->prepare(
    'SELECT op.*, (SELECT COUNT(*) FROM applications a WHERE a.opportunity_id = op.opportunity_id) AS applicants
     FROM opportunities op WHERE op.org_id = ? ORDER BY op.created_at DESC'
);
$stmt->execute([$orgId]);
$opps = $stmt->fetchAll();

$page_title = 'Our opportunities';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Attachment opportunities</h1>
    <a class="btn btn-siams" href="opportunity_form.php"><i class="bi bi-plus-lg me-1"></i>Post opportunity</a>
</div>
<?php show_flash(); ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Title</th><th>Slots</th><th>Deadline</th><th>Applicants</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!$opps): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">You have not posted any opportunities yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($opps as $op): ?>
                <tr>
                    <td class="fw-semibold"><?= e($op['title']) ?></td>
                    <td><?= (int) $op['slots'] ?></td>
                    <td><?= nice_date($op['deadline']) ?></td>
                    <td><?= (int) $op['applicants'] ?></td>
                    <td><?= status_badge($op['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="opportunity_form.php?id=<?= (int) $op['opportunity_id'] ?>">Edit</a>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="opportunity_id" value="<?= (int) $op['opportunity_id'] ?>">
                            <?php if ($op['status'] === 'open'): ?>
                                <button name="status" value="closed" class="btn btn-sm btn-outline-danger">Close</button>
                            <?php else: ?>
                                <button name="status" value="open" class="btn btn-sm btn-outline-success">Reopen</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
