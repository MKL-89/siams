<?php
/** Administrator dashboard: overview of user accounts. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('admin');

$by_role = db()->query('SELECT role, COUNT(*) AS total FROM users GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
$failed_today = count_of('SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at >= CURDATE()');

$page_title = 'Administrator dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading('System administration');
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Total user accounts', array_sum($by_role), 'bi-people');
    stat_card('Students', $by_role['student'] ?? 0, 'bi-mortarboard');
    stat_card('Staff and supervisors', ($by_role['coordinator'] ?? 0) + ($by_role['lecturer'] ?? 0) + ($by_role['industrial'] ?? 0), 'bi-person-badge');
    stat_card('Failed logins today', $failed_today, 'bi-shield-exclamation');
    ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
