<?php
/** University supervisor dashboard: students assigned to this lecturer. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('lecturer');

$me = 'SELECT lecturer_id FROM lecturers WHERE user_id = ?';
$uid = [$_SESSION['user_id']];

$assigned = count_of("SELECT COUNT(*) FROM placements WHERE lecturer_id = ($me)", $uid);
$visits   = count_of("SELECT COUNT(*) FROM supervision_visits WHERE lecturer_id = ($me)", $uid);
$to_mark  = count_of(
    "SELECT COUNT(*) FROM placements p
     WHERE p.lecturer_id = ($me)
       AND NOT EXISTS (SELECT 1 FROM evaluations e WHERE e.placement_id = p.placement_id AND e.component = 'university')",
    $uid
);

$page_title = 'Supervisor dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading('University supervisor');
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Students assigned to me', $assigned, 'bi-people');
    stat_card('Supervision visits recorded', $visits, 'bi-geo-alt');
    stat_card('Students not yet assessed', $to_mark, 'bi-clipboard-check');
    ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
