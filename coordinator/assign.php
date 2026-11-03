<?php
/** Coordinator: assign a university supervisor to each approved placement (FR-23). */
require_once __DIR__ . '/../includes/auth.php';
require_role('coordinator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placementId = (int) ($_POST['placement_id'] ?? 0);
    $lecturerId  = (int) ($_POST['lecturer_id'] ?? 0);
    $back = '/coordinator/assign.php' . (isset($_GET['show']) ? '?show=' . urlencode($_GET['show']) : '');

    $valid = db()->prepare("SELECT COUNT(*) FROM lecturers l JOIN users u ON u.user_id = l.user_id WHERE l.lecturer_id = ? AND u.status = 'active'");
    $valid->execute([$lecturerId]);

    if (!csrf_valid() || !$valid->fetchColumn()) {
        flash('Please choose a university supervisor from the list.', 'danger');
    } else {
        // Only approved or ongoing placements can be given a supervisor.
        $stmt = db()->prepare("UPDATE placements SET lecturer_id = ?, assigned_at = NOW()
                               WHERE placement_id = ? AND status IN ('approved','ongoing')");
        $stmt->execute([$lecturerId, $placementId]);
        flash($stmt->rowCount() ? 'University supervisor assigned.' : 'No change was made.', $stmt->rowCount() ? 'success' : 'warning');
    }
    redirect($back);
}

// Supervisors with their current number of students (workload).
$lecturers = db()->query(
    "SELECT l.lecturer_id, u.full_name, l.department,
            (SELECT COUNT(*) FROM placements p WHERE p.lecturer_id = l.lecturer_id AND p.status IN ('approved','ongoing')) AS load_count
     FROM lecturers l JOIN users u ON u.user_id = l.user_id
     WHERE u.status = 'active' ORDER BY u.full_name"
)->fetchAll();

$show = ($_GET['show'] ?? 'unassigned') === 'all' ? 'all' : 'unassigned';
$sql = "SELECT p.placement_id, p.lecturer_id, p.start_date, p.end_date, u.full_name, s.reg_no,
               o.name AS org_name, o.county, lu.full_name AS lecturer_name
        FROM placements p
        JOIN students s ON s.student_id = p.student_id
        JOIN users u ON u.user_id = s.user_id
        JOIN organizations o ON o.org_id = p.org_id
        LEFT JOIN lecturers l ON l.lecturer_id = p.lecturer_id
        LEFT JOIN users lu ON lu.user_id = l.user_id
        WHERE p.status IN ('approved','ongoing')" . ($show === 'unassigned' ? ' AND p.lecturer_id IS NULL' : '') . '
        ORDER BY o.county, o.name, u.full_name';
$rows = db()->query($sql)->fetchAll();

$page_title = 'Assign supervisors';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Assign university supervisors</h1>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-8">
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link<?= $show === 'unassigned' ? ' active' : '' ?>" href="?show=unassigned">Without a supervisor</a></li>
            <li class="nav-item"><a class="nav-link<?= $show === 'all' ? ' active' : '' ?>" href="?show=all">All approved placements</a></li>
        </ul>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Student</th><th>Organization</th><th>University supervisor</th></tr></thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">
                            <?= $show === 'unassigned' ? 'Every approved placement has a supervisor.' : 'No approved placements yet.' ?>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><div class="fw-semibold"><?= e($r['full_name']) ?></div><div class="small text-muted"><?= e($r['reg_no']) ?></div></td>
                            <td><?= e($r['org_name']) ?><div class="small text-muted"><?= e($r['county']) ?></div></td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="placement_id" value="<?= (int) $r['placement_id'] ?>">
                                    <select name="lecturer_id" class="form-select form-select-sm" aria-label="University supervisor" required>
                                        <option value="">Choose...</option>
                                        <?php foreach ($lecturers as $l): ?>
                                            <option value="<?= (int) $l['lecturer_id'] ?>"<?= (int) $r['lecturer_id'] === (int) $l['lecturer_id'] ? ' selected' : '' ?>>
                                                <?= e($l['full_name']) ?> (<?= (int) $l['load_count'] ?> students)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-siams text-nowrap"><?= $r['lecturer_id'] ? 'Change' : 'Assign' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">Supervisor workload</h2>
                <?php if (!$lecturers): ?>
                    <p class="text-muted small mb-0">No university supervisor accounts yet.</p>
                <?php endif; ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($lecturers as $l): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><?= e($l['full_name']) ?><br><small class="text-muted"><?= e($l['department']) ?></small></span>
                            <span class="badge bg-secondary align-self-center"><?= (int) $l['load_count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
