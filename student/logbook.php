<?php
/**
 * Student: weekly logbook (FR-10). One entry per week of the placement.
 * An entry can be edited until the industrial supervisor confirms it.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/logbook.php';
require_role('student');

// The student's current approved placement.
$stmt = db()->prepare(
    "SELECT p.*, o.name AS org_name FROM placements p JOIN organizations o ON o.org_id = p.org_id
     WHERE p.student_id = ? AND p.status IN ('approved','ongoing')
     ORDER BY p.start_date DESC LIMIT 1"
);
$stmt->execute([my_student_id()]);
$placement = $stmt->fetch();

$page_title = 'My logbook';

if (!$placement) {
    require __DIR__ . '/../includes/header.php';
    echo '<h1 class="h3 mb-3">My logbook</h1><div class="alert alert-light border">Your logbook opens once you have an approved placement.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$placementId = (int) $placement['placement_id'];
$openWeeks   = open_weeks($placement);
$entries     = logbook_entries($placementId);
$byWeek      = array_column($entries, null, 'week_no');

// Which week is being written or edited.
$week = (int) ($_GET['week'] ?? 0);
if (!$week) {
    foreach ($openWeeks as $n => $_) {
        if (!isset($byWeek[$n])) { $week = $n; break; }   // first week without an entry
    }
}
$current = $byWeek[$week] ?? null;
$en = [
    'week_no'        => (string) $week,
    'activities'     => $current['activities'] ?? '',
    'skills_learned' => $current['skills_learned'] ?? '',
    'challenges'     => $current['challenges'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($en as $k => $_) { $en[$k] = post_str($k); }
    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    $errors = array_merge($errors, validate_entry($en, $openWeeks));
    $existing = $byWeek[(int) $en['week_no']] ?? null;
    if (!$errors && $existing && $existing['confirmed']) {
        $errors[] = 'This week has already been confirmed by your supervisor and can no longer be changed.';
    }
    if (!$errors) {
        $w = (int) $en['week_no'];
        if ($existing) {
            db()->prepare('UPDATE logbook_entries SET activities = ?, skills_learned = ?, challenges = ?, submitted_at = NOW()
                           WHERE entry_id = ? AND confirmed = 0')
                ->execute([$en['activities'], $en['skills_learned'], $en['challenges'], $existing['entry_id']]);
            flash("Week $w entry updated.");
        } else {
            db()->prepare('INSERT INTO logbook_entries (placement_id, week_no, week_start, activities, skills_learned, challenges) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$placementId, $w, $openWeeks[$w], $en['activities'], $en['skills_learned'], $en['challenges']]);
            flash("Week $w entry submitted. Your industrial supervisor will confirm it.");
        }
        redirect('/student/logbook.php');
    }
    $current = $existing;
}

$totalWeeks = count(placement_weeks($placement));
$confirmed  = count(array_filter($entries, fn($x) => $x['confirmed']));
$canEdit    = $week && isset($openWeeks[$week]) && !($current['confirmed'] ?? false);

require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-1">My logbook</h1>
<p class="text-muted"><?= e($placement['org_name']) ?> · <?= nice_date($placement['start_date']) ?> to <?= nice_date($placement['end_date']) ?>
    · <?= count($entries) ?> of <?= $totalWeeks ?> weeks submitted, <?= $confirmed ?> confirmed</p>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-5">
        <?php if (!$openWeeks): ?>
            <div class="alert alert-light border">Your placement starts on <?= nice_date($placement['start_date']) ?>.
                You can write your first entry from then.</div>
        <?php elseif (!$canEdit && !$errors): ?>
            <div class="alert alert-success">All weeks so far have entries. Choose a week below to edit an entry that is not yet confirmed.</div>
        <?php else: ?>
            <form method="post" class="card shadow-sm" novalidate>
                <?= csrf_field() ?>
                <div class="card-body">
                    <h2 class="h5 mb-3"><?= $current ? 'Edit' : 'New' ?> entry</h2>
                    <?php show_errors($errors); ?>
                    <div class="mb-3">
                        <label class="form-label" for="week_no">Week</label>
                        <select class="form-select" id="week_no" name="week_no">
                            <?php foreach ($openWeeks as $n => $start): ?>
                                <?php if (!empty($byWeek[$n]['confirmed'])) { continue; } ?>
                                <option value="<?= $n ?>"<?= (int) $en['week_no'] === $n ? ' selected' : '' ?>>
                                    Week <?= $n ?> (from <?= nice_date($start) ?>)<?= isset($byWeek[$n]) ? ' – edit' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="activities">Activities this week</label>
                        <textarea class="form-control" id="activities" name="activities" rows="6" maxlength="3000" required><?= e($en['activities']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="skills_learned">Skills learned</label>
                        <textarea class="form-control" id="skills_learned" name="skills_learned" rows="3" maxlength="2000"><?= e($en['skills_learned']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="challenges">Challenges</label>
                        <textarea class="form-control" id="challenges" name="challenges" rows="3" maxlength="2000"><?= e($en['challenges']) ?></textarea>
                    </div>
                    <button class="btn btn-siams w-100"><?= $current ? 'Save changes' : 'Submit entry' ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <div class="col-lg-7">
        <h2 class="h5">Entries</h2>
        <?php if (!$entries): ?>
            <p class="text-muted">No entries yet.</p>
        <?php endif; ?>
        <?php foreach ($entries as $entry): ?>
            <?php entry_card($entry); ?>
            <?php if (!$entry['confirmed']): ?>
                <a class="small d-block mb-3 mt-n2" href="?week=<?= (int) $entry['week_no'] ?>">Edit week <?= (int) $entry['week_no'] ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
