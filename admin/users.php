<?php
/** Administrator: list user accounts, activate or deactivate them (FR-26). */
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!csrf_valid() || !in_array($status, ['active', 'inactive'], true)) {
        flash('The request could not be processed. Please try again.', 'danger');
    } elseif ($userId === (int) $_SESSION['user_id']) {
        flash('You cannot deactivate your own account.', 'warning');
    } else {
        $stmt = db()->prepare('UPDATE users SET status = ? WHERE user_id = ?');
        $stmt->execute([$status, $userId]);
        if ($stmt->rowCount()) {
            log_action('user_' . ($status === 'active' ? 'activated' : 'deactivated'), 'User #' . $userId);
            flash('Account ' . ($status === 'active' ? 'activated.' : 'deactivated.'));
        }
    }
    redirect('/admin/users.php?' . http_build_query(array_intersect_key($_GET, ['q' => 1, 'role' => 1, 'status' => 1])));
}

$q      = trim($_GET['q'] ?? '');
$role   = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$sql = 'SELECT user_id, full_name, email, phone, role, status, last_login FROM users WHERE 1 = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (full_name LIKE ? OR email LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
if (array_key_exists($role, ROLE_LABEL)) {
    $sql .= ' AND role = ?';
    $params[] = $role;
}
if (in_array($status, ['active', 'inactive', 'pending'], true)) {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY role, full_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'User accounts';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">User accounts</h1>
    <a class="btn btn-siams" href="user_form.php"><i class="bi bi-person-plus me-1"></i>New account</a>
</div>
<?php show_flash(); ?>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-5"><input class="form-control" name="q" placeholder="Search by name or email" value="<?= e($q) ?>"></div>
    <div class="col-md-3">
        <select class="form-select" name="role">
            <option value="">All roles</option>
            <?php foreach (ROLE_LABEL as $key => $label): ?>
                <option value="<?= $key ?>"<?= $role === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="status">
            <option value="">All statuses</option>
            <?php foreach (['active', 'inactive', 'pending'] as $st): ?>
                <option value="<?= $st ?>"<?= $status === $st ? ' selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Role</th><th>Last login</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No accounts found.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($u['full_name']) ?></div><div class="small text-muted"><?= e($u['email']) ?> · <?= e($u['phone']) ?></div></td>
                    <td class="small"><?= e(ROLE_LABEL[$u['role']]) ?></td>
                    <td class="small"><?= $u['last_login'] ? nice_date($u['last_login']) : 'Never' ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="user_form.php?id=<?= (int) $u['user_id'] ?>">Edit</a>
                        <a class="btn btn-sm btn-outline-secondary" href="reset_password.php?id=<?= (int) $u['user_id'] ?>">Reset password</a>
                        <?php if ((int) $u['user_id'] !== (int) $_SESSION['user_id']): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                                <?php if ($u['status'] === 'active'): ?>
                                    <button name="status" value="inactive" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Deactivate this account?');">Deactivate</button>
                                <?php else: ?>
                                    <button name="status" value="active" class="btn btn-sm btn-outline-success">Activate</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
