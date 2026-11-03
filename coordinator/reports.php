<?php
/**
 * Coordinator: reports on placement, organizations, supervisor workload and results (FR-25).
 * Each report can be printed or exported as a CSV file that opens in Excel.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/assessment.php';
require_role('coordinator');

const REPORTS = [
    'placed'        => 'Students placed and not placed',
    'organizations' => 'Placements by organization and county',
    'workload'      => 'University supervisor workload',
    'results'       => 'Assessment results',
];

$type = array_key_exists($_GET['type'] ?? '', REPORTS) ? $_GET['type'] : 'placed';

$periods = db()->query('SELECT period_id, name, is_active FROM attachment_periods ORDER BY start_date DESC')->fetchAll();
$periodId = (int) ($_GET['period'] ?? 0);
if (!$periodId) {
    foreach ($periods as $p) { if ($p['is_active']) { $periodId = (int) $p['period_id']; } }
}
$periodName = '';
foreach ($periods as $p) { if ((int) $p['period_id'] === $periodId) { $periodName = $p['name']; } }

$confirmed = "('approved','ongoing','completed')";

/** Builds the selected report: [column headings, rows]. */
function build_report(string $type, int $periodId, string $confirmed): array
{
    $pdo = db();
    switch ($type) {
        case 'placed':
            $stmt = $pdo->prepare(
                "SELECT u.full_name, s.reg_no, s.programme, s.year_of_study,
                        IFNULL(o.name, '') AS org, IFNULL(o.county, '') AS county,
                        IF(p.placement_id IS NULL, 'Not placed', 'Placed') AS placed
                 FROM students s JOIN users u ON u.user_id = s.user_id
                 LEFT JOIN placements p ON p.student_id = s.student_id AND p.period_id = ? AND p.status IN $confirmed
                 LEFT JOIN organizations o ON o.org_id = p.org_id
                 WHERE u.status = 'active'
                 ORDER BY placed DESC, u.full_name"
            );
            $stmt->execute([$periodId]);
            return [['Student', 'Reg. number', 'Programme', 'Year', 'Organization', 'County', 'Status'], $stmt->fetchAll(PDO::FETCH_NUM)];

        case 'organizations':
            $stmt = $pdo->prepare(
                "SELECT o.name, o.county, o.sector, COUNT(p.placement_id) AS students
                 FROM organizations o
                 JOIN placements p ON p.org_id = o.org_id AND p.period_id = ? AND p.status IN $confirmed
                 GROUP BY o.org_id ORDER BY o.county, students DESC, o.name"
            );
            $stmt->execute([$periodId]);
            return [['Organization', 'County', 'Sector', 'Students placed'], $stmt->fetchAll(PDO::FETCH_NUM)];

        case 'workload':
            $stmt = $pdo->prepare(
                "SELECT u.full_name, l.department,
                        COUNT(DISTINCT p.placement_id) AS students,
                        COUNT(DISTINCT v.visit_id) AS visits,
                        COUNT(DISTINCT e.evaluation_id) AS assessed
                 FROM lecturers l JOIN users u ON u.user_id = l.user_id
                 LEFT JOIN placements p ON p.lecturer_id = l.lecturer_id AND p.period_id = ? AND p.status IN $confirmed
                 LEFT JOIN supervision_visits v ON v.placement_id = p.placement_id
                 LEFT JOIN evaluations e ON e.placement_id = p.placement_id AND e.component = 'university'
                 WHERE u.status = 'active'
                 GROUP BY l.lecturer_id ORDER BY students DESC, u.full_name"
            );
            $stmt->execute([$periodId]);
            return [['University supervisor', 'Department', 'Students', 'Visits recorded', 'Students assessed'], $stmt->fetchAll(PDO::FETCH_NUM)];

        case 'results':
            $weights = mark_weights();
            $stmt = $pdo->prepare(
                "SELECT p.placement_id, u.full_name, s.reg_no, o.name AS org
                 FROM placements p
                 JOIN students s ON s.student_id = p.student_id JOIN users u ON u.user_id = s.user_id
                 JOIN organizations o ON o.org_id = p.org_id
                 WHERE p.period_id = ? AND p.status IN $confirmed ORDER BY u.full_name"
            );
            $stmt->execute([$periodId]);
            $rows = [];
            foreach ($stmt->fetchAll() as $r) {
                $scores = placement_scores((int) $r['placement_id']);
                $final = final_mark($scores, $weights);
                $rows[] = [$r['full_name'], $r['reg_no'], $r['org'],
                    $scores['industrial']['score'] ?? '', $scores['university']['score'] ?? '', $scores['logbook']['score'] ?? '',
                    $final === null ? 'Incomplete' : number_format($final, 2)];
            }
            return [['Student', 'Reg. number', 'Organization',
                     "Industrial ({$weights['industrial']}%)", "University ({$weights['university']}%)", "Logbook ({$weights['logbook']}%)", 'Final mark'], $rows];
    }
    return [[], []];
}

[$headings, $rows] = $periodId ? build_report($type, $periodId, $confirmed) : [[], []];

// CSV export
if (($_GET['format'] ?? '') === 'csv' && $periodId) {
    log_action('report_exported', REPORTS[$type] . ' – ' . $periodName);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="siams_' . $type . '_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // lets Excel read the file as UTF-8
    fputcsv($out, [REPORTS[$type] . ' – ' . $periodName]);
    fputcsv($out, $headings);
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out);
    exit;
}

// Summary line for the placement report.
$summary = '';
if ($type === 'placed' && $rows) {
    $placed = count(array_filter($rows, fn($r) => $r[6] === 'Placed'));
    $summary = $placed . ' of ' . count($rows) . ' students placed, ' . (count($rows) - $placed) . ' not placed.';
}

$page_title = 'Reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <h1 class="h3 mb-0">Reports</h1>
    <?php if ($periodId): ?>
        <div>
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            <a class="btn btn-siams" href="?type=<?= $type ?>&period=<?= $periodId ?>&format=csv"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
        </div>
    <?php endif; ?>
</div>
<form class="row g-2 mb-3 d-print-none" method="get">
    <div class="col-md-6">
        <select class="form-select" name="type" aria-label="Report">
            <?php foreach (REPORTS as $key => $label): ?>
                <option value="<?= $key ?>"<?= $type === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <select class="form-select" name="period" aria-label="Attachment period">
            <?php foreach ($periods as $p): ?>
                <option value="<?= (int) $p['period_id'] ?>"<?= (int) $p['period_id'] === $periodId ? ' selected' : '' ?>>
                    <?= e($p['name']) ?><?= $p['is_active'] ? ' (active)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Show</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-0"><?= e(REPORTS[$type]) ?></h2>
        <p class="text-muted small"><?= e(INSTITUTION) ?> · <?= e($periodName ?: 'No attachment period') ?> · printed <?= date('j M Y') ?>
            <?= $summary ? '<br>' . e($summary) : '' ?></p>
        <?php if (!$periodId): ?>
            <div class="alert alert-light border mb-0">Create an attachment period first.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light"><tr><?php foreach ($headings as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="<?= count($headings) ?>" class="text-center text-muted py-3">No records for this period.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr><?php foreach ($r as $cell): ?><td><?= e((string) $cell) ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
