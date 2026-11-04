<?php
/**
 * Student account creation. The account is active at once: the student is logged in
 * straight away and taken to the opportunity they wanted to apply for, or to their dashboard.
 * The coordinator and administrator can still deactivate an account later.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/opportunities.php';
start_secure_session();

// Someone already logged in in this tab can still create another account; it opens in its own session.
$loggedInAs = !empty($_SESSION['user_id']) ? $_SESSION['full_name'] . ' (' . ROLE_LABEL[current_role()] . ')' : null;

// Came from "Create a student account" on an opportunity: remember it for after login.
if (isset($_GET['apply']) && open_opportunity_title((int) $_GET['apply']) !== null) {
    $_SESSION['apply_after_login'] = (int) $_GET['apply'];
}
$applyId    = (int) ($_SESSION['apply_after_login'] ?? 0);
$applyTitle = $applyId ? open_opportunity_title($applyId) : null;

$u = ['full_name' => '', 'email' => '', 'phone' => '', 'reg_no' => '',
      'programme' => 'Diploma in Information Technology', 'year_of_study' => '1'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($u as $k => $_) { $u[$k] = post_str($k); }
    $u['email']  = strtolower($u['email']);
    $u['reg_no'] = strtoupper($u['reg_no']);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    if ($u['full_name'] === '' || mb_strlen($u['full_name']) > 100) {
        $errors[] = 'Full name is required (up to 100 characters).';
    }
    if (!filter_var($u['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($u['email']) > 120) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($u['phone'] !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $u['phone'])) {
        $errors[] = 'Please enter a valid phone number, for example 0712 345678.';
    }
    if (!preg_match('#^[A-Z0-9][A-Z0-9/\-]{3,29}$#', $u['reg_no'])) {
        $errors[] = 'Please enter your registration number as it appears on your student ID, for example DIT/N/1234/09/25.';
    }
    if ($u['programme'] === '' || mb_strlen($u['programme']) > 100) {
        $errors[] = 'Programme is required.';
    }
    if (!in_array($u['year_of_study'], ['1', '2', '3', '4'], true)) {
        $errors[] = 'Please choose your year of study.';
    }
    if ($problem = password_problem($password, $confirm)) {
        $errors[] = $problem;
    }
    if (!isset($_POST['agree'])) {
        $errors[] = 'Please confirm that the details you have given are correct.';
    }

    if (!$errors) {
        $check = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $check->execute([$u['email']]);
        if ($check->fetchColumn()) {
            $errors[] = 'An account with this email already exists. Please log in instead.';
        }
        $check = db()->prepare('SELECT 1 FROM students WHERE reg_no = ?');
        $check->execute([$u['reg_no']]);
        if ($check->fetchColumn()) {
            $errors[] = 'This registration number already has an account. Contact the attachment office if this is a mistake.';
        }
    }

    // Another account is logged in in this tab: open a new session for the new student.
    if (!$errors && $loggedInAs) {
        unset($_SESSION['apply_after_login']);
        if (!move_to_new_slot()) {
            $errors[] = 'Too many accounts are logged in on this browser. Please log out of one of them first.';
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, 'student', 'active')")
            ->execute([$u['full_name'], $u['email'], $u['phone'], password_hash($password, PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO students (user_id, reg_no, programme, year_of_study) VALUES (?, ?, ?, ?)')
            ->execute([$userId, $u['reg_no'], $u['programme'], (int) $u['year_of_study']]);
        $pdo->commit();
        log_action('student_registered', $u['reg_no'] . ' (' . $u['email'] . ')', $userId);

        // Log the new student in and continue where they were going.
        start_user_session($userId, $u['full_name'], 'student');
        unset($_SESSION['apply_after_login']);
        if ($applyTitle) {
            flash('Welcome, ' . $u['full_name'] . '! Your account is ready. You can now apply for this opportunity.');
            redirect('/student/opportunity.php?id=' . $applyId);
        }
        flash('Welcome, ' . $u['full_name'] . '! Your account is ready. Browse the opportunities and apply.');
        redirect('/student/index.php');
    }
}

$page_title = 'Create an account';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-1">Create a student account</h1>
        <p class="text-muted">Students of the School of Science, Engineering &amp; Technology can create an account to
            find attachment opportunities, apply online and keep their logbook. Your account is ready to use as soon
            as you create it.</p>

            <?php if ($applyTitle): ?>
                <div class="alert alert-primary small">
                    <i class="bi bi-send me-1"></i>Create your account to apply for <strong><?= e($applyTitle) ?></strong>.
                    You will be taken straight back to it to send your application.
                </div>
            <?php endif; ?>
            <div class="alert alert-light border small">
                <i class="bi bi-building me-1"></i>Registering a host organization instead?
                <a href="<?= BASE_URL ?>/register_host.php">Register your organization</a>
            </div>
            <?php show_errors($errors); ?>
            <form method="post" class="card shadow-sm" novalidate>
                <?= csrf_field() ?>
                <div class="card-body">
                    <h2 class="h5 mb-3">Personal details</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="full_name">Full name</label>
                            <input class="form-control" id="full_name" name="full_name" maxlength="100" value="<?= e($u['full_name']) ?>" required autocomplete="name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone <span class="text-muted">(optional)</span></label>
                            <input class="form-control" id="phone" name="phone" maxlength="20" value="<?= e($u['phone']) ?>" autocomplete="tel">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="email">Email (used to log in)</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="120" value="<?= e($u['email']) ?>" required autocomplete="email">
                        </div>
                    </div>

                    <h2 class="h5 mt-4 mb-3">Academic details</h2>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="reg_no">Registration number</label>
                            <input class="form-control" id="reg_no" name="reg_no" maxlength="30" value="<?= e($u['reg_no']) ?>" placeholder="e.g. DIT/N/1234/09/25" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="programme">Programme</label>
                            <input class="form-control" id="programme" name="programme" maxlength="100" value="<?= e($u['programme']) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="year_of_study">Year</label>
                            <select class="form-select" id="year_of_study" name="year_of_study">
                                <?php foreach (['1', '2', '3', '4'] as $y): ?>
                                    <option value="<?= $y ?>" <?= $u['year_of_study'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h2 class="h5 mt-4 mb-3">Password</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                            <div class="form-text">At least 8 characters, with letters and numbers.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirm">Confirm password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="agree" name="agree" value="1">
                        <label class="form-check-label small" for="agree">
                            I confirm that these details are correct, and I agree that the university may use them to
                            manage my industrial attachment.
                        </label>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-link">Already have an account? Log in</a>
                    <button type="submit" class="btn btn-siams"><i class="bi bi-person-plus me-1"></i>Create account</button>
                </div>
            </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
