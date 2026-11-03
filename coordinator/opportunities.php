<?php
/** Coordinator: all attachment opportunities across host organizations, with close/reopen. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_role('coordinator');

handle_status_toggle(null, '/coordinator/opportunities.php');

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$sql = 'SELECT op.*, o.name AS org_name, o.county,
               (SELECT COUNT(*) FROM applications a WHERE a.opportunity_id = op.opportunity_id) AS applicants
        FROM opportunities op JOIN organizations o ON o.org_id = op.org_id WHERE 1 = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (op.title LIKE ? OR o.name LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
if (in_array($status, ['open', 'closed'], true)) {
    $sql .= ' AND op.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY op.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$opps = $stmt->fetchAll();

$page_title = 'Opportunities';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Attachment opportunities</h1>
<?php show_flash(); ?>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-6"><input class="form-control" name="q" placeholder="Search by title or organization" value="<?= e($q) ?>"></div>
    <div class="col-md-4">
        <select class="form-select" name="status">
            <option value="">All statuses</option>
            <option value="open"<?= $status === 'open' ? ' selected' : '' ?>>Open</option>
            <option value="closed"<?= $status === 'closed' ? ' selected' : '' ?>>Closed</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Title</th><th>Organization</th><th>County</th><th>Slots</th><th>Deadline</th><th>Applicants</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (!$opps): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No opportunities found.</td></tr>
            <?php endif; ?>
            <?php foreach ($opps as $op): ?>
                <tr>
                    <td class="fw-semibold"><?= e($op['title']) ?></td>
                    <td><?= e($op['org_name']) ?></td>
                    <td><?= e($op['county']) ?></td>
                    <td><?= (int) $op['slots'] ?></td>
                    <td><?= nice_date($op['deadline']) ?></td>
                    <td><?= (int) $op['applicants'] ?></td>
                    <td><?= status_badge($op['status']) ?></td>
                    <td class="text-end">
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
