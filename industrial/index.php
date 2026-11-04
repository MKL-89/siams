<?php
/** Industrial supervisor dashboard: students on attachment at this organization. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_role('industrial');

$stmt = db()->prepare(
    'SELECT i.ind_sup_id, i.org_id, o.name AS org_name
     FROM ind_supervisors i JOIN organizations o ON o.org_id = i.org_id
     WHERE i.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch() ?: ['ind_sup_id' => 0, 'org_id' => 0, 'org_name' => 'Organization not set'];

$students    = count_of('SELECT COUNT(*) FROM placements WHERE ind_sup_id = ?', [$profile['ind_sup_id']]);
$to_confirm  = count_of(
    'SELECT COUNT(*) FROM logbook_entries l JOIN placements p ON p.placement_id = l.placement_id
     WHERE p.ind_sup_id = ? AND l.confirmed = 0',
    [$profile['ind_sup_id']]
);
$open_opps   = count_of("SELECT COUNT(*) FROM opportunities WHERE org_id = ? AND status = 'open' AND (deadline IS NULL OR deadline >= CURDATE())", [$profile['org_id']]);
$applicants  = count_of(
    "SELECT COUNT(*) FROM applications a JOIN opportunities op ON op.opportunity_id = a.opportunity_id
     WHERE op.org_id = ? AND a.status = 'pending'",
    [$profile['org_id']]
);

$page_title = 'Industrial supervisor dashboard';
require __DIR__ . '/../includes/header.php';
dashboard_heading($profile['org_name'], 'bi-building');
?>
<div class="row g-3 mb-4">
    <?php
    stat_card('Open opportunities', $open_opps, 'bi-briefcase', 'opportunities.php');
    stat_card('Applicants waiting', $applicants, 'bi-person-lines-fill', 'applicants.php');
    stat_card('Students I supervise', $students, 'bi-people', 'students.php');
    stat_card('Logbook entries to confirm', $to_confirm, 'bi-journal-check', 'logbook.php');
    ?>
</div>

<?php
if ($applicants) {
    next_step('bi-person-lines-fill', $applicants . ' applicant' . ($applicants === 1 ? '' : 's') . ' waiting for your decision',
        'Review the students who applied for your opportunities and accept or decline them.', 'Review applicants', 'applicants.php', 'warning');
} elseif ($to_confirm) {
    next_step('bi-journal-check', 'Logbook entries to confirm',
        'Your students have submitted weekly logbook entries. Read them, add remarks and confirm them.', 'Open logbooks', 'logbook.php', 'warning');
} elseif (!$open_opps) {
    next_step('bi-plus-circle', 'Post your first opportunity',
        'Tell students what attachment places you offer. Your opportunity is shown to students straight away.', 'Post an opportunity', 'opportunity_form.php');
}

quick_actions([
    ['bi-plus-circle', 'Post an opportunity', 'Add a new attachment opportunity for students to apply for.', 'opportunity_form.php'],
    ['bi-person-lines-fill', 'Applicants', 'Accept or decline students who applied.', 'applicants.php'],
    ['bi-journal-check', 'Logbooks', 'Read and confirm your students\' weekly entries.', 'logbook.php'],
    ['bi-clipboard-check', 'Assessments', 'Score each student out of 100 at the end of the attachment.', 'assess.php'],
]);
?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
