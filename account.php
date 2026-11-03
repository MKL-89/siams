<?php
/** Any logged-in user: change their own password. */
require_once __DIR__ . '/includes/auth.php';
require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    } elseif (!password_verify($_POST['current_password'] ?? '', $hash)) {
        $errors[] = 'Your current password is not correct.';
    } elseif ($problem = password_problem($_POST['password'] ?? '', $_POST['password_confirm'] ?? '')) {
        $errors[] = $problem;
    } else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
            ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
        session_regenerate_id(true);
        log_action('password_changed');
        flash('Your password has been changed.');
        redirect('/account.php');
    }
}

$page_title = 'My account';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <h1 class="h3 mb-1">My account</h1>
        <p class="text-muted"><?= e($_SESSION['full_name']) ?> · <?= e(ROLE_LABEL[current_role()]) ?></p>
        <?php show_flash(); show_errors($errors); ?>
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body">
                <h2 class="h5 mb-3">Change password</h2>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                    <div class="form-text">At least 8 characters, with letters and numbers.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Confirm new password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                </div>
                <button class="btn btn-siams w-100">Change password</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
