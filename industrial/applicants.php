<?php
/**
 * Industrial supervisor: applicants for the organization's opportunities (FR-14).
 * Accepting creates a pending placement that the coordinator must approve (FR-22).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_role('industrial');

$me = my_supervisor();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId  = (int) ($_POST['application_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $back   = '/industrial/applicants.php';

    if (!csrf_valid() || !in_array($action, ['accept', 'decline'], true)) {
        flash('The request could not be processed. Please try again.', 'danger');
        redirect($back);
    }

    // The application must be pending and belong to one of this organization's opportunities.
    $stmt = db()->prepare(
        "SELECT a.application_id, a.student_id, a.opportunity_id, op.slots
         FROM applications a JOIN opportunities op ON op.opportunity_id = a.opportunity_id
         WHERE a.application_id = ? AND op.org_id = ? AND a.status = 'pending'"
    );
    $stmt->execute([$appId, $me['org_id']]);
    $app = $stmt->fetch();
    if (!$app) {
        flash('That application is no longer waiting for a decision.', 'warning');
        redirect($back);
    }

    if ($action === 'decline') {
        db()->prepare("UPDATE applications SET status = 'rejected', decided_at = NOW() WHERE application_id = ?")->execute([$appId]);
        flash('Application declined.');
        redirect($back);
    }

    // Accept: check slots, the attachment period and the student's other placements.
    $period = active_period();
    if (!$period) {
        flash('The attachment office has not opened an attachment period yet, so students cannot be accepted. Please contact the office.', 'warning');
    } elseif (filled_slots((int) $app['opportunity_id']) >= (int) $app['slots']) {
        flash('All slots for this opportunity have been filled.', 'warning');
    } elseif (has_confirmed_placement((int) $app['student_id'], (int) $period['period_id'])) {
        flash('This student already has an approved placement elsewhere for this period.', 'warning');
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE applications SET status = 'accepted', decided_at = NOW() WHERE application_id = ?")->execute([$appId]);
        $pdo->prepare('INSERT INTO placements (student_id, org_id, period_id, application_id, ind_sup_id, start_date, end_date)
                       VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$app['student_id'], $me['org_id'], $period['period_id'], $appId, $me['ind_sup_id'], $period['start_date'], $period['end_date']]);
        $pdo->commit();
        flash('Student accepted. The attachment office will now approve the placement.');
    }
    redirect($back);
}

$stmt = db()->prepare(
    'SELECT a.*, op.title, op.slots, u.full_name, u.email, u.phone, s.reg_no, s.programme, s.year_of_study
     FROM applications a
     JOIN opportunities op ON op.opportunity_id = a.opportunity_id
     JOIN students s ON s.student_id = a.student_id
     JOIN users u ON u.user_id = s.user_id
     WHERE op.org_id = ? AND a.status <> \'withdrawn\'
     ORDER BY FIELD(a.status, \'pending\', \'accepted\', \'approved\', \'rejected\'), a.applied_at'
);
$stmt->execute([$me['org_id']]);
$apps = $stmt->fetchAll();

$page_title = 'Applicants';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Applicants</h1>
<?php show_flash(); ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Programme</th><th>Opportunity</th><th>Applied</th><th>Status</th><th class="text-end">Decision</th></tr></thead>
            <tbody>
            <?php if (!$apps): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No applications yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($apps as $a): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($a['full_name']) ?></div>
                        <div class="small text-muted"><?= e($a['reg_no']) ?> · <?= e($a['email']) ?> · <?= e($a['phone']) ?></div>
                    </td>
                    <td class="small"><?= e($a['programme']) ?>, year <?= (int) $a['year_of_study'] ?></td>
                    <td><?= e($a['title']) ?></td>
                    <td><?= nice_date($a['applied_at']) ?></td>
                    <td><?= status_badge($a['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($a['status'] === 'pending'): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= (int) $a['application_id'] ?>">
                                <button name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                                <button name="action" value="decline" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Decline this applicant?');">Decline</button>
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
