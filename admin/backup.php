<?php
/**
 * Administrator: download a full backup of the database as an SQL file (FR-28).
 * Written in PHP so it also works on shared hosting where the mysqldump program is not available.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('The request could not be processed. Please try again.', 'danger');
        redirect('/admin/backup.php');
    }

    $pdo = db();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $filename = 'siams_backup_' . date('Y-m-d_His') . '.sql';
    log_action('database_backup', $filename . ', ' . count($tables) . ' tables');

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "-- SIAMS database backup\n-- Created " . date('Y-m-d H:i:s') . "\n\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n";
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
        echo "DROP TABLE IF EXISTS `$table`;\n$create;\n\n";

        $rows = $pdo->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row);
            echo "INSERT INTO `$table` (`" . implode('`, `', array_keys($row)) . '`) VALUES (' . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    exit;
}

$counts = [];
foreach (db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $counts[$t] = (int) db()->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}
$last = db()->query("SELECT created_at FROM audit_log WHERE action = 'database_backup' ORDER BY log_id DESC LIMIT 1")->fetchColumn();

$page_title = 'Database backup';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-1">Database backup</h1>
<p class="text-muted">Last backup: <?= $last ? e(date('j M Y, H:i', strtotime($last))) : 'never' ?>.
    Uploaded PDF files are stored separately in the <code>uploads</code> folder and should be copied as well.</p>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-5">
        <form method="post" class="card shadow-sm">
            <?= csrf_field() ?>
            <div class="card-body">
                <h2 class="h5">Download a backup</h2>
                <p class="small">Creates an SQL file with every table and record. Keep it somewhere safe, away from the server.
                    It can be restored with phpMyAdmin (Import) or the <code>mysql</code> command.</p>
                <button class="btn btn-siams w-100"><i class="bi bi-download me-1"></i>Download backup</button>
            </div>
        </form>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Table</th><th class="text-end">Records</th></tr></thead>
                    <tbody>
                    <?php foreach ($counts as $t => $n): ?>
                        <tr><td><?= e($t) ?></td><td class="text-end"><?= $n ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
