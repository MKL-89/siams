<?php
/**
 * Coordinator: list student accounts and approve or reject accounts that
 * students created themselves (register.php).
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('coordinator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!csrf_valid() || !in_array($action, ['approve', 'reject'], true)) {
        flash('The request could not be processed. Please try again.', 'danger');
    } else {
        $newStatus = $action === 'approve' ? 'active' : 'inactive';
        $upd = db()->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'student' AND status = 'pending'");
        $upd->execute([$newStatus, $userId]);
        if ($upd->rowCount() === 1) {
            log_action($action === 'approve' ? 'student_approved' : 'student_rejected', 'User #' . $userId);
            flash($action === 'approve' ? 'Student account approved. The student can now log in.' : 'Student account rejected.');
        } else {
            flash('That account has already been reviewed.', 'warning');
        }
    }
    redirect('/coordinator/students.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');

$sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.status, u.created_at,
               s.reg_no, s.programme, s.year_of_study
        FROM users u JOIN students s ON s.user_id = u.user_id
        WHERE u.role = 'student'";
$params = [];
if (in_array($status, ['pending', 'active', 'inactive'], true)) {
    $sql .= ' AND u.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR s.reg_no LIKE ? OR u.email LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$sql .= " ORDER BY FIELD(u.status, 'pending', 'active', 'inactive'), u.full_name";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pending = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'pending'")->fetchColumn();

$labels = ['pending' => 'Awaiting approval', 'active' => 'Active', 'inactive' => 'Inactive'];

$page_title = 'Student accounts';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Student accounts</h1>
<?php show_flash(); ?>
<?php if ($pending): ?>
    <div class="alert alert-warning py-2">
        <i class="bi bi-hourglass-split me-1"></i><?= $pending ?> student account(s) awaiting your approval.
        Check each registration number against the school's records before approving.
        <a href="?status=pending">Show them</a>
    </div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-6"><input class="form-control" name="q" placeholder="Search by name, registration number or email" value="<?= e($q) ?>"></div>
    <div class="col-md-4">
        <select class="form-select" name="status">
            <option value="">All statuses</option>
            <?php foreach ($labels as $st => $label): ?>
                <option value="<?= $st ?>"<?= $status === $st ? ' selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Registration no.</th><th>Programme</th><th>Year</th><th>Contact</th><th>Created</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!$students): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No student accounts found.</td></tr>
            <?php endif; ?>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td class="fw-semibold"><?= e($s['full_name']) ?></td>
                    <td><?= e($s['reg_no']) ?></td>
                    <td class="small"><?= e($s['programme']) ?></td>
                    <td><?= (int) $s['year_of_study'] ?></td>
                    <td class="small"><?= e($s['email']) ?><br><?= e($s['phone']) ?></td>
                    <td class="small"><?= nice_date($s['created_at']) ?></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($s['status'] === 'pending'): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $s['user_id'] ?>">
                                <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button name="action" value="reject" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Reject this student account?');">Reject</button>
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
