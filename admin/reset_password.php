<?php
/**
 * Administrator: set a new password for a user who has forgotten theirs (FR-02).
 * The administrator gives the new password to the user, who can then change it under My account.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$stmt = db()->prepare('SELECT user_id, full_name, email FROM users WHERE user_id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$user = $stmt->fetch();
if (!$user) {
    flash('Account not found.', 'danger');
    redirect('/admin/users.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    } elseif ($problem = password_problem($_POST['password'] ?? '', $_POST['password_confirm'] ?? '')) {
        $errors[] = $problem;
    } else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
            ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $user['user_id']]);
        // Clear failed attempts so a locked-out user can log in with the new password.
        db()->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$user['email']]);
        log_action('password_reset', 'User #' . $user['user_id']);
        flash('Password reset for ' . $user['full_name'] . '. Give them the new password and ask them to change it after logging in.');
        redirect('/admin/users.php');
    }
}

$page_title = 'Reset password';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <h1 class="h3 mb-1">Reset password</h1>
        <p class="text-muted"><?= e($user['full_name']) ?> · <?= e($user['email']) ?></p>
        <?php show_errors($errors); ?>
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                    <div class="form-text">At least 8 characters, with letters and numbers.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Confirm new password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                </div>
                <button class="btn btn-siams w-100">Reset password</button>
            </div>
        </form>
        <a href="users.php" class="btn btn-link mt-2">Back to accounts</a>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
