<?php
/** University supervisor: list of assigned students (FR-17). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_role('lecturer');

$stmt = db()->prepare(
    "SELECT p.placement_id, p.start_date, p.end_date, p.status,
            u.full_name, u.phone, u.email, s.reg_no, s.programme,
            o.name AS org_name, o.county, o.address,
            iu.full_name AS ind_name, iu.phone AS ind_phone,
            (SELECT COUNT(*) FROM supervision_visits v WHERE v.placement_id = p.placement_id) AS visits,
            (SELECT MAX(v.visit_date) FROM supervision_visits v WHERE v.placement_id = p.placement_id) AS last_visit
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     JOIN organizations o ON o.org_id = p.org_id
     LEFT JOIN ind_supervisors i ON i.ind_sup_id = p.ind_sup_id
     LEFT JOIN users iu ON iu.user_id = i.user_id
     WHERE p.lecturer_id = ? AND p.status IN ('approved','ongoing','completed')
     ORDER BY o.county, u.full_name"
);
$stmt->execute([my_lecturer_id()]);
$students = $stmt->fetchAll();

$page_title = 'My students';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My students</h1>
<?php show_flash(); ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Placement</th><th>Industrial supervisor</th><th>Period</th><th>Visits</th><th></th></tr></thead>
            <tbody>
            <?php if (!$students): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No students have been assigned to you yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($students as $st): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($st['full_name']) ?></div>
                        <div class="small text-muted"><?= e($st['reg_no']) ?> · <?= e($st['phone']) ?></div>
                    </td>
                    <td><?= e($st['org_name']) ?><div class="small text-muted"><?= e($st['address']) ?>, <?= e($st['county']) ?></div></td>
                    <td class="small"><?= e($st['ind_name'] ?? '—') ?><br><?= e($st['ind_phone'] ?? '') ?></td>
                    <td class="small"><?= nice_date($st['start_date']) ?> –<br><?= nice_date($st['end_date']) ?></td>
                    <td class="small"><?= (int) $st['visits'] ?><?= $st['last_visit'] ? '<br>last ' . nice_date($st['last_visit']) : '' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="logbook.php?placement=<?= (int) $st['placement_id'] ?>">Logbook</a>
                        <a class="btn btn-sm btn-outline-secondary" href="visits.php?placement=<?= (int) $st['placement_id'] ?>">Visits</a>
                        <a class="btn btn-sm btn-siams" href="assess.php?placement=<?= (int) $st['placement_id'] ?>">Assess</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
