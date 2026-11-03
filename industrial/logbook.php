<?php
/** Industrial supervisor: confirm students' weekly logbook entries and add remarks (FR-15). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/logbook.php';
require_role('industrial');

$indSupId = (int) my_supervisor()['ind_sup_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entryId = (int) ($_POST['entry_id'] ?? 0);
    $comment = post_str('comment');
    $back    = '/industrial/logbook.php' . (isset($_GET['placement']) ? '?placement=' . (int) $_GET['placement'] : '');

    if (!csrf_valid()) {
        flash('The request could not be processed. Please try again.', 'danger');
    } elseif (mb_strlen($comment) > 1000) {
        flash('Remarks must be 1,000 characters or fewer.', 'danger');
    } else {
        // Only entries of this supervisor's own students, and only once.
        $stmt = db()->prepare(
            'UPDATE logbook_entries l JOIN placements p ON p.placement_id = l.placement_id
             SET l.confirmed = 1, l.confirmed_by = ?, l.confirmed_at = NOW(), l.supervisor_comment = ?
             WHERE l.entry_id = ? AND p.ind_sup_id = ? AND l.confirmed = 0'
        );
        $stmt->execute([$_SESSION['user_id'], $comment === '' ? null : $comment, $entryId, $indSupId]);
        flash($stmt->rowCount() ? 'Entry confirmed.' : 'That entry could not be confirmed.', $stmt->rowCount() ? 'success' : 'warning');
    }
    redirect($back);
}

// Students supervised, with how many entries are waiting.
$stmt = db()->prepare(
    "SELECT p.placement_id, u.full_name, s.reg_no,
            (SELECT COUNT(*) FROM logbook_entries l WHERE l.placement_id = p.placement_id) AS total,
            (SELECT COUNT(*) FROM logbook_entries l WHERE l.placement_id = p.placement_id AND l.confirmed = 0) AS waiting
     FROM placements p JOIN students s ON s.student_id = p.student_id JOIN users u ON u.user_id = s.user_id
     WHERE p.ind_sup_id = ? AND p.status IN ('approved','ongoing','completed')
     ORDER BY waiting DESC, u.full_name"
);
$stmt->execute([$indSupId]);
$students = $stmt->fetchAll();

$selected = (int) ($_GET['placement'] ?? ($students[0]['placement_id'] ?? 0));
$current  = null;
foreach ($students as $st) {
    if ((int) $st['placement_id'] === $selected) { $current = $st; }
}
$entries = $current ? logbook_entries($selected) : [];

$page_title = 'Logbooks';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Student logbooks</h1>
<?php show_flash(); ?>
<?php if (!$students): ?>
    <div class="alert alert-light border">You do not have any students on attachment yet.</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="list-group shadow-sm">
            <?php foreach ($students as $st): ?>
                <a href="?placement=<?= (int) $st['placement_id'] ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center<?= (int) $st['placement_id'] === $selected ? ' active' : '' ?>">
                    <span><?= e($st['full_name']) ?><br><small><?= e($st['reg_no']) ?></small></span>
                    <?php if ($st['waiting']): ?>
                        <span class="badge bg-warning text-dark"><?= (int) $st['waiting'] ?> to confirm</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-8">
        <?php if (!$current): ?>
            <div class="alert alert-light border">Choose a student.</div>
        <?php elseif (!$entries): ?>
            <div class="alert alert-light border"><?= e($current['full_name']) ?> has not submitted any entries yet.</div>
        <?php endif; ?>
        <?php foreach ($entries as $en): ?>
            <?php entry_card($en); ?>
            <?php if (!$en['confirmed']): ?>
                <form method="post" class="card card-body shadow-sm mb-4 mt-n2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="entry_id" value="<?= (int) $en['entry_id'] ?>">
                    <label class="form-label small" for="comment<?= (int) $en['entry_id'] ?>">Remarks (optional)</label>
                    <textarea class="form-control form-control-sm mb-2" id="comment<?= (int) $en['entry_id'] ?>" name="comment" rows="2" maxlength="1000"></textarea>
                    <button class="btn btn-sm btn-success align-self-start"><i class="bi bi-check2 me-1"></i>Confirm week <?= (int) $en['week_no'] ?></button>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
