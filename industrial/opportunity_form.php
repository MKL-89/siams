<?php
/** Industrial supervisor: post a new opportunity or edit one of the organization's own (FR-13). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_role('industrial');

$stmt = db()->prepare('SELECT org_id FROM ind_supervisors WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$orgId = (int) $stmt->fetchColumn();

$id = (int) ($_GET['id'] ?? 0);
$op = ['title' => '', 'description' => '', 'slots' => '1', 'deadline' => ''];

if ($id) {
    // Only the organization's own opportunities can be edited.
    $stmt = db()->prepare('SELECT * FROM opportunities WHERE opportunity_id = ? AND org_id = ?');
    $stmt->execute([$id, $orgId]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('Opportunity not found.', 'danger');
        redirect('/industrial/opportunities.php');
    }
    $op = array_map('strval', array_intersect_key($row, $op));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($op as $k => $_) { $op[$k] = post_str($k); }
    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    $errors = array_merge($errors, validate_opportunity($op, $id === 0));

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE opportunities SET title = ?, description = ?, slots = ?, deadline = ? WHERE opportunity_id = ? AND org_id = ?')
                ->execute([$op['title'], $op['description'], (int) $op['slots'], $op['deadline'], $id, $orgId]);
            flash('Opportunity updated.');
        } else {
            db()->prepare('INSERT INTO opportunities (org_id, title, description, slots, deadline) VALUES (?, ?, ?, ?, ?)')
                ->execute([$orgId, $op['title'], $op['description'], (int) $op['slots'], $op['deadline']]);
            flash('Opportunity posted. Students can now see it.');
        }
        redirect('/industrial/opportunities.php');
    }
}

$page_title = $id ? 'Edit opportunity' : 'Post opportunity';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3"><?= e($page_title) ?></h1>
        <?php show_errors($errors); ?>
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label" for="title">Title</label>
                    <input class="form-control" id="title" name="title" maxlength="150" value="<?= e($op['title']) ?>"
                           placeholder="e.g. ICT Support Attachment" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="6" maxlength="2000"
                              placeholder="Duties, skills needed and any requirements" required><?= e($op['description']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="slots">Number of students (slots)</label>
                    <input type="number" class="form-control" id="slots" name="slots" min="1" max="50" value="<?= e($op['slots']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="deadline">Application deadline</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" value="<?= e($op['deadline']) ?>"
                           <?= $id ? '' : 'min="' . date('Y-m-d') . '"' ?> required>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="opportunities.php" class="btn btn-link">Cancel</a>
                <button type="submit" class="btn btn-siams"><?= $id ? 'Save changes' : 'Post opportunity' ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
