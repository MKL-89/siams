<?php
/**
 * Login page (FR-01). Checks the email and password, then sends each user
 * to the dashboard for their role. If someone is already logged in in this tab,
 * the new account opens in its own session and the first one stays logged in.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/opportunities.php';
start_secure_session();

// "Log in to apply": remember the opportunity so the student returns to it after logging in.
// Only an opportunity id is stored (never a URL), so this cannot redirect anywhere else.
if (isset($_GET['apply'])) {
    $applyId = (int) $_GET['apply'];
    if (open_opportunity_title($applyId) !== null) {
        $_SESSION['apply_after_login'] = $applyId;
    } else {
        unset($_SESSION['apply_after_login']);
    }
}
$applyTitle = isset($_SESSION['apply_after_login']) ? open_opportunity_title((int) $_SESSION['apply_after_login']) : null;

$error = null;
$email = '';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Your form expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email address and your password.';
    } else {
        $applyId = (int) ($_SESSION['apply_after_login'] ?? 0);
        unset($_SESSION['apply_after_login']);
        $error = attempt_login($email, $password);
        if ($error !== null && $applyId) {
            $_SESSION['apply_after_login'] = $applyId;   // keep it for the next try
        }
        if ($error === null) {
            if ($applyId && current_role() === 'student') {
                redirect('/student/opportunity.php?id=' . $applyId);
            }
            redirect(ROLE_HOME[current_role()]);
        }
    }
}

$page_title = 'Log in';
$full_width = true;
require __DIR__ . '/includes/header.php';
?>
<div class="login-wrapper auth-backdrop">
    <div class="card shadow-sm login-card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="login-logo"><i class="bi bi-briefcase-fill"></i></div>
                <h1 class="h4 mb-1"><?= APP_NAME ?></h1>
                <p class="text-muted small mb-0"><?= e(APP_FULL_NAME) ?></p>
                <p class="text-muted small"><?= e(INSTITUTION) ?></p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-info py-2 small"><?= e($flash) ?></div>
            <?php endif; ?>
            <?php if ($applyTitle): ?>
                <div class="alert alert-primary py-2 small">
                    <i class="bi bi-send me-1"></i>Log in to apply for <strong><?= e($applyTitle) ?></strong>.
                    You will be taken back to it after logging in.
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($email) ?>" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                           required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-siams w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Log in
                </button>
            </form>
            <hr class="my-4">
            <p class="text-center small text-muted mb-2">Don't have an account?</p>
            <a class="btn btn-outline-primary w-100 mb-2" href="<?= BASE_URL ?>/register.php<?= $applyTitle ? '?apply=' . (int) $_SESSION['apply_after_login'] : '' ?>">
                <i class="bi bi-person-plus me-1"></i>Create a student account
            </a>
            <p class="text-center small text-muted mb-0">
                Host organization? <a href="<?= BASE_URL ?>/register_host.php">Register to post opportunities</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
