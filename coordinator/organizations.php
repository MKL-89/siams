<?php
/**
 * Coordinator: list host organizations, search them, and approve or reject
 * new registrations (FR-21).
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('coordinator');

// Approve or reject a pending registration.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgId  = (int) ($_POST['org_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!csrf_valid() || !in_array($action, ['approve', 'reject'], true)) {
        flash('The request could not be processed. Please try again.', 'danger');
    } else {
        $orgStatus  = $action === 'approve' ? 'approved' : 'rejected';
        $userStatus = $action === 'approve' ? 'active' : 'inactive';
        $pdo = db();
        $pdo->beginTransaction();
        $upd = $pdo->prepare("UPDATE organizations SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE org_id = ? AND status = 'pending'");
        $upd->execute([$orgStatus, $_SESSION['user_id'], $orgId]);
        if ($upd->rowCount() === 1) {
            // The organization's supervisor accounts follow the decision.
            $pdo->prepare("UPDATE users u JOIN ind_supervisors i ON i.user_id = u.user_id
                           SET u.status = ? WHERE i.org_id = ? AND u.status = 'pending'")
                ->execute([$userStatus, $orgId]);
            $pdo->commit();
            flash('Organization ' . $orgStatus . '.');
        } else {
            $pdo->rollBack();
            flash('That registration has already been reviewed.', 'warning');
        }
    }
    redirect('/coordinator/organizations.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

// Filters
$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$county = $_GET['county'] ?? '';

$sql = 'SELECT o.*, (SELECT COUNT(*) FROM opportunities op WHERE op.org_id = o.org_id) AS opp_count
        FROM organizations o WHERE 1 = 1';
$params = [];
if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $sql .= ' AND o.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $sql .= ' AND o.name LIKE ?';
    $params[] = '%' . $q . '%';
}
if (in_array($county, COUNTIES, true)) {
    $sql .= ' AND o.county = ?';
    $params[] = $county;
}
$sql .= " ORDER BY FIELD(o.status, 'pending', 'approved', 'rejected'), o.name";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orgs = $stmt->fetchAll();

$pending = (int) db()->query("SELECT COUNT(*) FROM organizations WHERE status = 'pending'")->fetchColumn();

$page_title = 'Host organizations';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Host organizations</h1>
    <a class="btn btn-siams" href="organization_form.php"><i class="bi bi-plus-lg me-1"></i>Add organization</a>
</div>
<?php show_flash(); ?>
<?php if ($pending): ?>
    <div class="alert alert-warning py-2">
        <i class="bi bi-hourglass-split me-1"></i><?= $pending ?> registration(s) awaiting your approval.
        <a href="?status=pending">Show them</a>
    </div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-4"><input class="form-control" name="q" placeholder="Search by name" value="<?= e($q) ?>"></div>
    <div class="col-md-3"><select class="form-select" name="county"><?= options(COUNTIES, $county, 'All counties') ?></select></div>
    <div class="col-md-3">
        <select class="form-select" name="status">
            <option value="">All statuses</option>
            <?php foreach (['pending', 'approved', 'rejected'] as $st): ?>
                <option value="<?= $st ?>"<?= $status === $st ? ' selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Sector</th><th>County</th><th>Contact</th><th>Opportunities</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!$orgs): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No organizations found.</td></tr>
            <?php endif; ?>
            <?php foreach ($orgs as $org): ?>
                <tr>
                    <td class="fw-semibold"><?= e($org['name']) ?></td>
                    <td><?= e($org['sector']) ?></td>
                    <td><?= e($org['county']) ?></td>
                    <td class="small"><?= e($org['contact_email']) ?><br><?= e($org['contact_phone']) ?></td>
                    <td><?= (int) $org['opp_count'] ?></td>
                    <td><?= status_badge($org['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($org['status'] === 'pending'): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="org_id" value="<?= (int) $org['org_id'] ?>">
                                <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button name="action" value="reject" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Reject this registration?');">Reject</button>
                            </form>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" href="organization_form.php?id=<?= (int) $org['org_id'] ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
