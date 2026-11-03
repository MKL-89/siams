<?php
/**
 * Coordinator: approve or reject placements accepted by host organizations (FR-22).
 * A student can hold only one approved placement per attachment period.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_role('coordinator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placementId = (int) ($_POST['placement_id'] ?? 0);
    $action      = $_POST['action'] ?? '';
    $back        = '/coordinator/placements.php';

    if (!csrf_valid() || !in_array($action, ['approve', 'reject'], true)) {
        flash('The request could not be processed. Please try again.', 'danger');
        redirect($back);
    }

    $pdo = db();
    $pdo->beginTransaction();
    // Lock the placement row so two coordinators cannot decide on it at the same time.
    $stmt = $pdo->prepare("SELECT * FROM placements WHERE placement_id = ? AND status = 'pending' FOR UPDATE");
    $stmt->execute([$placementId]);
    $pl = $stmt->fetch();

    if (!$pl) {
        $pdo->rollBack();
        flash('That placement has already been decided.', 'warning');
        redirect($back);
    }

    if ($action === 'reject') {
        $pdo->prepare("UPDATE placements SET status = 'rejected', approved_by = ?, decided_at = NOW() WHERE placement_id = ?")
            ->execute([$_SESSION['user_id'], $placementId]);
        $pdo->prepare("UPDATE applications SET status = 'rejected', decided_at = NOW() WHERE application_id = ?")
            ->execute([$pl['application_id']]);
        $pdo->commit();
        flash('Placement rejected.');
        redirect($back);
    }

    // Approve: refuse if the student already holds an approved placement in this period.
    $pdo->prepare('SELECT placement_id FROM placements WHERE student_id = ? FOR UPDATE')->execute([$pl['student_id']]);
    if (has_confirmed_placement((int) $pl['student_id'], $pl['period_id'] !== null ? (int) $pl['period_id'] : null)) {
        $pdo->rollBack();
        flash('This student already has an approved placement for this period. A second one cannot be approved.', 'danger');
        redirect($back);
    }

    $pdo->prepare("UPDATE placements SET status = 'approved', approved_by = ?, decided_at = NOW() WHERE placement_id = ?")
        ->execute([$_SESSION['user_id'], $placementId]);
    $pdo->prepare("UPDATE applications SET status = 'approved', decided_at = NOW() WHERE application_id = ?")
        ->execute([$pl['application_id']]);

    // The student's other open applications and pending placements in the period are closed.
    $pdo->prepare("UPDATE placements SET status = 'rejected', decided_at = NOW()
                   WHERE student_id = ? AND period_id <=> ? AND status = 'pending' AND placement_id <> ?")
        ->execute([$pl['student_id'], $pl['period_id'], $placementId]);
    $pdo->prepare("UPDATE applications SET status = 'withdrawn', decided_at = NOW()
                   WHERE student_id = ? AND status IN ('pending','accepted') AND application_id <> ?")
        ->execute([$pl['student_id'], $pl['application_id']]);
    $pdo->commit();
    flash('Placement approved.');
    redirect($back);
}

$status = $_GET['status'] ?? 'pending';
$sql = 'SELECT pl.*, u.full_name, s.reg_no, s.programme, o.name AS org_name, o.county,
               op.title, ap.name AS period_name
        FROM placements pl
        JOIN students s ON s.student_id = pl.student_id
        JOIN users u ON u.user_id = s.user_id
        JOIN organizations o ON o.org_id = pl.org_id
        LEFT JOIN applications a ON a.application_id = pl.application_id
        LEFT JOIN opportunities op ON op.opportunity_id = a.opportunity_id
        LEFT JOIN attachment_periods ap ON ap.period_id = pl.period_id';
$params = [];
if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $sql .= ' WHERE pl.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY pl.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$page_title = 'Placements';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Placements</h1>
<?php show_flash(); ?>
<ul class="nav nav-tabs mb-3">
    <?php foreach (['pending' => 'Awaiting approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
        <li class="nav-item"><a class="nav-link<?= $status === $key ? ' active' : '' ?>" href="?status=<?= $key ?>"><?= $label ?></a></li>
    <?php endforeach; ?>
</ul>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Organization</th><th>Opportunity</th><th>Period</th><th>Status</th><th class="text-end">Decision</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No placements in this list.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($r['full_name']) ?></div><div class="small text-muted"><?= e($r['reg_no']) ?></div></td>
                    <td><?= e($r['org_name']) ?>, <?= e($r['county']) ?></td>
                    <td><?= e($r['title'] ?? '—') ?></td>
                    <td class="small"><?= e($r['period_name'] ?? '—') ?><br><?= nice_date($r['start_date']) ?> – <?= nice_date($r['end_date']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($r['status'] === 'pending'): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="placement_id" value="<?= (int) $r['placement_id'] ?>">
                                <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button name="action" value="reject" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Reject this placement?');">Reject</button>
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
