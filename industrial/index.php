<?php
/** Industrial supervisor dashboard: students on attachment at this organization. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('industrial');

$stmt = db()->prepare(
    'SELECT i.ind_sup_id, o.name AS org_name
     FROM ind_supervisors i JOIN organizations o ON o.org_id = i.org_id
     WHERE i.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch() ?: ['ind_sup_id' => 0, 'org_name' => 'Organization not set'];

$students    = count_of('SELECT COUNT(*) FROM placements WHERE ind_sup_id = ?', [$profile['ind_sup_id']]);
$to_confirm  = count_of(
    'SELECT COUNT(*) FROM logbook_entries l JOIN placements p ON p.placement_id = l.placement_id
     WHERE p.ind_sup_id = ? AND l.confirmed = 0',
    [$profile['ind_sup_id']]
);

$page_title = 'Industrial supervisor dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading($profile['org_name']);
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Students I supervise', $students, 'bi-people');
    stat_card('Logbook entries to confirm', $to_confirm, 'bi-journal-check');
    ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
