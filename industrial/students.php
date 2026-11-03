<?php
/** Industrial supervisor: students on attachment under this supervisor, with their university supervisor. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/documents.php';
require_role('industrial');

$stmt = db()->prepare(
    "SELECT p.placement_id, p.start_date, p.end_date, p.status, u.full_name, u.phone, u.email, s.reg_no, s.programme,
            lu.full_name AS lecturer_name, lu.phone AS lecturer_phone, lu.email AS lecturer_email
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     LEFT JOIN lecturers l ON l.lecturer_id = p.lecturer_id
     LEFT JOIN users lu ON lu.user_id = l.user_id
     WHERE p.ind_sup_id = ? AND p.status IN ('approved','ongoing','completed')
     ORDER BY u.full_name"
);
$stmt->execute([my_supervisor()['ind_sup_id']]);
$students = $stmt->fetchAll();

$page_title = 'My students';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Students on attachment</h1>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Programme</th><th>Period</th><th>University supervisor</th><th>Documents</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$students): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No approved students yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($students as $st): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($st['full_name']) ?></div><div class="small text-muted"><?= e($st['reg_no']) ?> · <?= e($st['phone']) ?></div></td>
                    <td class="small"><?= e($st['programme']) ?></td>
                    <td class="small"><?= nice_date($st['start_date']) ?> – <?= nice_date($st['end_date']) ?></td>
                    <td class="small">
                        <?php if ($st['lecturer_name']): ?>
                            <?= e($st['lecturer_name']) ?><br><?= e($st['lecturer_phone']) ?> · <?= e($st['lecturer_email']) ?>
                        <?php else: ?>
                            <span class="text-muted">Not yet assigned</span>
                        <?php endif; ?>
                    </td>
                    <td><?php document_links(placement_documents((int) $st['placement_id'])); ?></td>
                    <td><?= status_badge($st['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
