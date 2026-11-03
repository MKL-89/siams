<?php
/** Administrator: view the activity log of important actions (FR-27). */
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$action = $_GET['action'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

$sql = 'SELECT a.*, u.full_name, u.email FROM audit_log a LEFT JOIN users u ON u.user_id = a.user_id WHERE 1 = 1';
$params = [];
$actions = db()->query('SELECT DISTINCT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
if (in_array($action, $actions, true)) {
    $sql .= ' AND a.action = ?';
    $params[] = $action;
}
if (DateTime::createFromFormat('Y-m-d', $from)) {
    $sql .= ' AND a.created_at >= ?';
    $params[] = $from . ' 00:00:00';
}
if (DateTime::createFromFormat('Y-m-d', $to)) {
    $sql .= ' AND a.created_at <= ?';
    $params[] = $to . ' 23:59:59';
}
$sql .= ' ORDER BY a.log_id DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$page_title = 'Activity log';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-1">Activity log</h1>
<p class="text-muted">Logins, approvals, account changes and backups. The latest 300 matching entries are shown.</p>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-4">
        <select class="form-select" name="action" aria-label="Action">
            <option value="">All actions</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?= e($a) ?>"<?= $action === $a ? ' selected' : '' ?>><?= e(str_replace('_', ' ', $a)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= e($from) ?>" aria-label="From date"></div>
    <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= e($to) ?>" aria-label="To date"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP address</th></tr></thead>
            <tbody>
            <?php if (!$logs): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No activity found.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('j M Y, H:i', strtotime($l['created_at']))) ?></td>
                    <td class="small"><?= e($l['full_name'] ?? '—') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e(str_replace('_', ' ', $l['action'])) ?></span></td>
                    <td class="small"><?= e($l['details']) ?></td>
                    <td class="small text-muted"><?= e($l['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
