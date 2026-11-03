<?php
/** Coordinator: assessment scores and final marks for every placed student (FR-24). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/assessment.php';
require_role('coordinator');

$weights = mark_weights();

$rows = db()->query(
    "SELECT p.placement_id, u.full_name, s.reg_no, o.name AS org_name, lu.full_name AS lecturer_name,
            MAX(CASE WHEN e.component = 'industrial' THEN e.score END) AS industrial,
            MAX(CASE WHEN e.component = 'university' THEN e.score END) AS university,
            MAX(CASE WHEN e.component = 'logbook'    THEN e.score END) AS logbook
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     JOIN organizations o ON o.org_id = p.org_id
     LEFT JOIN lecturers l ON l.lecturer_id = p.lecturer_id
     LEFT JOIN users lu ON lu.user_id = l.user_id
     LEFT JOIN evaluations e ON e.placement_id = p.placement_id
     WHERE p.status IN ('approved','ongoing','completed')
     GROUP BY p.placement_id
     ORDER BY u.full_name"
)->fetchAll();

$complete = 0;
foreach ($rows as &$r) {
    $scores = [];
    foreach (array_keys(COMPONENTS) as $c) {
        if ($r[$c] !== null) { $scores[$c] = ['score' => $r[$c]]; }
    }
    $r['final'] = final_mark($scores, $weights);
    if ($r['final'] !== null) { $complete++; }
}
unset($r);

$page_title = 'Results';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-1">Assessment results</h1>
<p class="text-muted">Final mark = industrial supervisor <?= e((string) $weights['industrial']) ?>%
    + university supervisor <?= e((string) $weights['university']) ?>%
    + logbook <?= e((string) $weights['logbook']) ?>%.
    <?= $complete ?> of <?= count($rows) ?> students fully assessed.</p>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Student</th><th>Organization</th><th>University supervisor</th>
                    <th class="text-end">Industrial</th><th class="text-end">University</th><th class="text-end">Logbook</th><th class="text-end">Final</th></tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No placed students yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($r['full_name']) ?></div><div class="small text-muted"><?= e($r['reg_no']) ?></div></td>
                    <td class="small"><?= e($r['org_name']) ?></td>
                    <td class="small"><?= e($r['lecturer_name'] ?? 'Not assigned') ?></td>
                    <?php foreach (array_keys(COMPONENTS) as $c): ?>
                        <td class="text-end"><?= $r[$c] !== null ? e($r[$c]) : '<span class="text-muted">—</span>' ?></td>
                    <?php endforeach; ?>
                    <td class="text-end fw-semibold"><?= $r['final'] !== null ? number_format($r['final'], 2) : '<span class="badge bg-warning text-dark">Incomplete</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
