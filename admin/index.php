<?php
/** Administrator dashboard: overview of user accounts. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('admin');

$by_role = db()->query('SELECT role, COUNT(*) AS total FROM users GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
$failed_today = count_of('SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at >= CURDATE()');

$page_title = 'Administrator dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading('System administration', 'bi-shield-lock');
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Total user accounts', array_sum($by_role), 'bi-people', 'users.php');
    stat_card('Students', $by_role['student'] ?? 0, 'bi-mortarboard', 'users.php?role=student');
    stat_card('Staff and supervisors', ($by_role['coordinator'] ?? 0) + ($by_role['lecturer'] ?? 0) + ($by_role['industrial'] ?? 0), 'bi-person-badge', 'users.php');
    stat_card('Failed logins today', $failed_today, 'bi-shield-exclamation', 'audit.php?action=login_failed');
    ?>
</div>

<?php
if ($failed_today >= 10) {
    next_step('bi-shield-exclamation', 'Many failed logins today',
        'Check the activity log for repeated failed logins on the same account.', 'Open the activity log', 'audit.php?action=login_failed', 'warning');
}

quick_actions([
    ['bi-person-plus', 'Add a user', 'Create an account for a coordinator, supervisor or student.', 'user_form.php'],
    ['bi-people', 'Users', 'Edit, deactivate or reactivate accounts and reset passwords.', 'users.php'],
    ['bi-list-check', 'Activity log', 'See logins, approvals and account changes.', 'audit.php'],
    ['bi-database-down', 'Backup', 'Download a copy of the whole database.', 'backup.php'],
]);
?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
