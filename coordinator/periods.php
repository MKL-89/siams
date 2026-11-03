<?php
/** Coordinator: create attachment periods and choose the active one (FR-20). */
require_once __DIR__ . '/../includes/auth.php';
require_role('coordinator');

$p = ['name' => '', 'start_date' => '', 'end_date' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('The request could not be processed. Please try again.', 'danger');
        redirect('/coordinator/periods.php');
    }

    if (isset($_POST['activate'])) {
        // Only one period is active at a time.
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->exec('UPDATE attachment_periods SET is_active = 0');
        $pdo->prepare('UPDATE attachment_periods SET is_active = 1 WHERE period_id = ?')->execute([(int) $_POST['activate']]);
        $pdo->commit();
        flash('Active attachment period changed.');
        redirect('/coordinator/periods.php');
    }

    foreach ($p as $k => $_) { $p[$k] = post_str($k); }
    if ($p['name'] === '' || mb_strlen($p['name']) > 80) {
        $errors[] = 'Period name is required (maximum 80 characters).';
    }
    $start = DateTime::createFromFormat('Y-m-d', $p['start_date']);
    $end   = DateTime::createFromFormat('Y-m-d', $p['end_date']);
    if (!$start || !$end) {
        $errors[] = 'Please enter valid start and end dates.';
    } elseif ($p['end_date'] <= $p['start_date']) {
        $errors[] = 'The end date must be after the start date.';
    }
    if (!$errors) {
        $makeActive = active_count() === 0 ? 1 : 0;   // the first period becomes active automatically
        db()->prepare('INSERT INTO attachment_periods (name, start_date, end_date, is_active, created_by) VALUES (?, ?, ?, ?, ?)')
            ->execute([$p['name'], $p['start_date'], $p['end_date'], $makeActive, $_SESSION['user_id']]);
        flash('Attachment period created.');
        redirect('/coordinator/periods.php');
    }
}

function active_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM attachment_periods WHERE is_active = 1')->fetchColumn();
}

$periods = db()->query(
    "SELECT ap.*, (SELECT COUNT(*) FROM placements pl WHERE pl.period_id = ap.period_id AND pl.status IN ('approved','ongoing','completed')) AS placed
     FROM attachment_periods ap ORDER BY ap.start_date DESC"
)->fetchAll();

$page_title = 'Attachment periods';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Attachment periods</h1>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Period</th><th>Start</th><th>End</th><th>Students placed</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$periods): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No periods yet. Create one to start placing students.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($periods as $per): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($per['name']) ?></td>
                            <td><?= nice_date($per['start_date']) ?></td>
                            <td><?= nice_date($per['end_date']) ?></td>
                            <td><?= (int) $per['placed'] ?></td>
                            <td class="text-end">
                                <?php if ($per['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button name="activate" value="<?= (int) $per['period_id'] ?>" class="btn btn-sm btn-outline-primary">Make active</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body">
                <h2 class="h5 mb-3">New period</h2>
                <?php show_errors($errors); ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control" id="name" name="name" maxlength="80" placeholder="e.g. May - August 2027" value="<?= e($p['name']) ?>" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col"><label class="form-label" for="start_date">Start date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($p['start_date']) ?>" required></div>
                    <div class="col"><label class="form-label" for="end_date">End date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($p['end_date']) ?>" required></div>
                </div>
                <button class="btn btn-siams w-100">Create period</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
