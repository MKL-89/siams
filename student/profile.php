<?php
/**
 * Student: update profile details (FR-05). The registration number and name are
 * set by the administrator and shown here read-only.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$stmt = db()->prepare(
    'SELECT u.full_name, u.email, u.phone, s.reg_no, s.programme, s.year_of_study
     FROM users u JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$p = array_map('strval', $stmt->fetch() ?: []);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['phone', 'programme', 'year_of_study'] as $k) { $p[$k] = post_str($k); }
    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    if (!preg_match('/^\+?[0-9 ]{9,15}$/', $p['phone'])) {
        $errors[] = 'Please enter a valid phone number (e.g. 0712345678).';
    }
    if ($p['programme'] === '' || mb_strlen($p['programme']) > 100) {
        $errors[] = 'Programme is required (maximum 100 characters).';
    }
    if (!in_array($p['year_of_study'], ['1', '2', '3', '4'], true)) {
        $errors[] = 'Year of study must be 1 to 4.';
    }
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET phone = ? WHERE user_id = ?')->execute([$p['phone'], $_SESSION['user_id']]);
        $pdo->prepare('UPDATE students SET programme = ?, year_of_study = ? WHERE user_id = ?')
            ->execute([$p['programme'], (int) $p['year_of_study'], $_SESSION['user_id']]);
        $pdo->commit();
        flash('Profile updated.');
        redirect('/student/profile.php');
    }
}

$page_title = 'My profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <h1 class="h3 mb-3">My profile</h1>
        <?php show_flash(); show_errors($errors); ?>
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="full_name">Full name</label>
                    <input class="form-control" id="full_name" value="<?= e($p['full_name']) ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="reg_no">Registration number</label>
                    <input class="form-control" id="reg_no" value="<?= e($p['reg_no']) ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" value="<?= e($p['email']) ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone</label>
                    <input class="form-control" id="phone" name="phone" maxlength="20" value="<?= e($p['phone']) ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="programme">Programme</label>
                    <input class="form-control" id="programme" name="programme" maxlength="100" value="<?= e($p['programme']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="year_of_study">Year of study</label>
                    <select class="form-select" id="year_of_study" name="year_of_study">
                        <?php foreach (['1', '2', '3', '4'] as $y): ?>
                            <option<?= $p['year_of_study'] === $y ? ' selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="small text-muted mb-0">To correct your name, email or registration number, contact the attachment office.</p>
            </div>
            <div class="card-footer bg-white text-end">
                <button class="btn btn-siams">Save profile</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
