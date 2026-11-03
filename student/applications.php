<?php
/** Student: status of each application and placement (FR-09), with withdraw for pending applications. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_role('student');

$studentId = my_student_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_valid()) {
        // Only the student's own applications that the host has not yet decided on.
        $stmt = db()->prepare("UPDATE applications SET status = 'withdrawn', decided_at = NOW()
                               WHERE application_id = ? AND student_id = ? AND status = 'pending'");
        $stmt->execute([(int) ($_POST['withdraw'] ?? 0), $studentId]);
        flash($stmt->rowCount() ? 'Application withdrawn.' : 'That application can no longer be withdrawn.', $stmt->rowCount() ? 'success' : 'warning');
    }
    redirect('/student/applications.php');
}

$stmt = db()->prepare(
    'SELECT a.*, op.title, o.name AS org_name, o.county
     FROM applications a
     JOIN opportunities op ON op.opportunity_id = a.opportunity_id
     JOIN organizations o ON o.org_id = op.org_id
     WHERE a.student_id = ? ORDER BY a.applied_at DESC'
);
$stmt->execute([$studentId]);
$apps = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT pl.*, o.name AS org_name, ap.name AS period_name,
            lu.full_name AS lecturer_name, lu.phone AS lecturer_phone,
            iu.full_name AS ind_name, iu.phone AS ind_phone
     FROM placements pl
     JOIN organizations o ON o.org_id = pl.org_id
     LEFT JOIN attachment_periods ap ON ap.period_id = pl.period_id
     LEFT JOIN lecturers l ON l.lecturer_id = pl.lecturer_id
     LEFT JOIN users lu ON lu.user_id = l.user_id
     LEFT JOIN ind_supervisors i ON i.ind_sup_id = pl.ind_sup_id
     LEFT JOIN users iu ON iu.user_id = i.user_id
     WHERE pl.student_id = ? ORDER BY pl.created_at DESC'
);
$stmt->execute([$studentId]);
$placements = $stmt->fetchAll();

// Plain-language explanation of each application status.
$meaning = [
    'pending'   => 'Waiting for the organization to respond.',
    'accepted'  => 'Accepted by the organization. Waiting for the attachment office to approve.',
    'approved'  => 'Approved. This is your placement.',
    'rejected'  => 'Not successful.',
    'withdrawn' => 'Withdrawn.',
];

$page_title = 'My applications';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My applications</h1>
<?php show_flash(); ?>

<?php foreach ($placements as $pl): ?>
    <?php if (in_array($pl['status'], ['approved', 'ongoing', 'completed'], true)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            Your placement at <strong><?= e($pl['org_name']) ?></strong> is <?= e($pl['status']) ?>
            (<?= e($pl['period_name'] ?? '') ?>, <?= nice_date($pl['start_date']) ?> to <?= nice_date($pl['end_date']) ?>).
            <div class="small mt-2">
                <strong>Industrial supervisor:</strong> <?= e($pl['ind_name'] ?? 'Not set') ?> <?= e($pl['ind_phone'] ?? '') ?><br>
                <strong>University supervisor:</strong>
                <?= $pl['lecturer_name'] ? e($pl['lecturer_name'] . ' ' . $pl['lecturer_phone']) : 'Not yet assigned by the attachment office' ?>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Opportunity</th><th>Organization</th><th>Applied</th><th>Status</th><th>What it means</th><th></th></tr></thead>
            <tbody>
            <?php if (!$apps): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    You have not applied yet. <a href="opportunities.php">Find opportunities</a>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($apps as $a): ?>
                <tr>
                    <td class="fw-semibold"><?= e($a['title']) ?></td>
                    <td><?= e($a['org_name']) ?>, <?= e($a['county']) ?></td>
                    <td><?= nice_date($a['applied_at']) ?></td>
                    <td><?= status_badge($a['status']) ?></td>
                    <td class="small text-muted"><?= e($meaning[$a['status']]) ?></td>
                    <td class="text-end">
                        <?php if ($a['status'] === 'pending'): ?>
                            <form method="post" onsubmit="return confirm('Withdraw this application?');">
                                <?= csrf_field() ?>
                                <button name="withdraw" value="<?= (int) $a['application_id'] ?>" class="btn btn-sm btn-outline-secondary">Withdraw</button>
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
