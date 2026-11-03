<?php
/** University supervisor: record a supervision visit or call for one student (FR-18). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_role('lecturer');

const VISIT_TYPES = ['physical' => 'Physical visit', 'phone' => 'Phone call', 'virtual' => 'Video call'];

$lecturerId  = my_lecturer_id();
$placementId = (int) ($_GET['placement'] ?? 0);

// The placement must be assigned to this supervisor.
$stmt = db()->prepare(
    "SELECT p.*, u.full_name, s.reg_no, o.name AS org_name
     FROM placements p
     JOIN students s ON s.student_id = p.student_id
     JOIN users u ON u.user_id = s.user_id
     JOIN organizations o ON o.org_id = p.org_id
     WHERE p.placement_id = ? AND p.lecturer_id = ?"
);
$stmt->execute([$placementId, $lecturerId]);
$pl = $stmt->fetch();
if (!$pl) {
    flash('That student is not assigned to you.', 'danger');
    redirect('/lecturer/students.php');
}

$v = ['visit_date' => date('Y-m-d'), 'visit_type' => 'physical', 'notes' => ''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) { $v[$k] = post_str($k); }
    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    $d = DateTime::createFromFormat('Y-m-d', $v['visit_date']);
    if (!$d || $d->format('Y-m-d') !== $v['visit_date']) {
        $errors[] = 'Please enter a valid date.';
    } elseif ($v['visit_date'] > date('Y-m-d')) {
        $errors[] = 'A visit cannot be recorded for a future date.';
    }
    if (!array_key_exists($v['visit_type'], VISIT_TYPES)) {
        $errors[] = 'Please choose the type of contact.';
    }
    if ($v['notes'] === '' || mb_strlen($v['notes']) > 2000) {
        $errors[] = 'Please write short notes on the visit (maximum 2,000 characters).';
    }
    if (!$errors) {
        db()->prepare('INSERT INTO supervision_visits (placement_id, lecturer_id, visit_date, visit_type, notes) VALUES (?, ?, ?, ?, ?)')
            ->execute([$placementId, $lecturerId, $v['visit_date'], $v['visit_type'], $v['notes']]);
        flash('Visit recorded.');
        redirect('/lecturer/visits.php?placement=' . $placementId);
    }
}

$stmt = db()->prepare('SELECT * FROM supervision_visits WHERE placement_id = ? ORDER BY visit_date DESC, visit_id DESC');
$stmt->execute([$placementId]);
$visits = $stmt->fetchAll();

$page_title = 'Visits: ' . $pl['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<a href="students.php" class="small"><i class="bi bi-arrow-left me-1"></i>Back to my students</a>
<h1 class="h3 mt-2 mb-1"><?= e($pl['full_name']) ?></h1>
<p class="text-muted"><?= e($pl['reg_no']) ?> · <?= e($pl['org_name']) ?></p>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-5">
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body">
                <h2 class="h5 mb-3">Record a visit or call</h2>
                <?php show_errors($errors); ?>
                <div class="mb-3">
                    <label class="form-label" for="visit_date">Date</label>
                    <input type="date" class="form-control" id="visit_date" name="visit_date" max="<?= date('Y-m-d') ?>" value="<?= e($v['visit_date']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="visit_type">Type of contact</label>
                    <select class="form-select" id="visit_type" name="visit_type">
                        <?php foreach (VISIT_TYPES as $key => $label): ?>
                            <option value="<?= $key ?>"<?= $v['visit_type'] === $key ? ' selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="5" maxlength="2000"
                              placeholder="Work observed, progress, issues raised" required><?= e($v['notes']) ?></textarea>
                </div>
                <button class="btn btn-siams w-100">Save visit</button>
            </div>
        </form>
    </div>
    <div class="col-lg-7">
        <h2 class="h5">Visit history</h2>
        <?php if (!$visits): ?>
            <p class="text-muted">No visits recorded yet.</p>
        <?php endif; ?>
        <?php foreach ($visits as $vi): ?>
            <div class="card shadow-sm mb-2">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between small text-muted">
                        <span><?= nice_date($vi['visit_date']) ?></span>
                        <span><?= e(VISIT_TYPES[$vi['visit_type']]) ?></span>
                    </div>
                    <p class="mb-0" style="white-space: pre-line"><?= e($vi['notes']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
