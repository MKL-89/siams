<?php
/**
 * Host organization registration (FR-12). Creates the organization and its
 * industrial supervisor account; both stay "pending" until a coordinator approves them (FR-21).
 */
require_once __DIR__ . '/includes/auth.php';
start_secure_session();

if (!empty($_SESSION['user_id'])) {
    redirect(ROLE_HOME[current_role()]);
}

$o = ['name' => '', 'sector' => '', 'county' => '', 'address' => '', 'contact_email' => '', 'contact_phone' => ''];
$s = ['full_name' => '', 'position' => '', 'email' => '', 'phone' => ''];
$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($o as $k => $_) { $o[$k] = post_str($k); }
    foreach ($s as $k => $_) { $s[$k] = post_str('sup_' . $k); }
    $s['email'] = strtolower($s['email']);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    $errors = array_merge($errors, validate_organization($o));
    if ($s['full_name'] === '' || mb_strlen($s['full_name']) > 100) {
        $errors[] = "Supervisor's full name is required.";
    }
    if (!filter_var($s['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email for the supervisor's login.";
    }
    if ($problem = password_problem($password, $confirm)) {
        $errors[] = $problem;
    }
    if (!$errors) {
        $exists = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $exists->execute([$s['email']]);
        if ($exists->fetchColumn()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO organizations (name, sector, county, address, contact_email, contact_phone) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$o['name'], $o['sector'], $o['county'], $o['address'], $o['contact_email'], $o['contact_phone']]);
        $orgId = (int) $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, 'industrial', 'pending')")
            ->execute([$s['full_name'], $s['email'], $s['phone'], password_hash($password, PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();

        $pdo->prepare('INSERT INTO ind_supervisors (user_id, org_id, position) VALUES (?, ?, ?)')
            ->execute([$userId, $orgId, $s['position']]);
        $pdo->commit();
        log_action('host_registered', $o['name'] . ' (' . $s['email'] . ')', $userId);
        $done = true;
    }
}

$page_title = 'Register your organization';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-1">Register your organization</h1>
        <p class="text-muted">Host organizations can register to post attachment opportunities for students.
            The attachment office will review your registration before you can log in.</p>

        <?php if ($done): ?>
            <div class="alert alert-success">
                <h2 class="h5">Registration received</h2>
                Thank you. Your registration is awaiting approval by the attachment office.
                You will be able to log in once it has been approved.
            </div>
            <a class="btn btn-siams" href="<?= BASE_URL ?>/login.php">Back to login</a>
        <?php else: ?>
            <?php show_errors($errors); ?>
            <form method="post" class="card shadow-sm" novalidate>
                <?= csrf_field() ?>
                <div class="card-body">
                    <h2 class="h5 mb-3">Organization details</h2>
                    <?php require __DIR__ . '/includes/organization_fields.php'; ?>

                    <h2 class="h5 mt-4 mb-3">Industrial supervisor (login account)</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sup_full_name">Full name</label>
                            <input class="form-control" id="sup_full_name" name="sup_full_name" maxlength="100" value="<?= e($s['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sup_position">Position</label>
                            <input class="form-control" id="sup_position" name="sup_position" maxlength="80" value="<?= e($s['position']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sup_email">Email (used to log in)</label>
                            <input type="email" class="form-control" id="sup_email" name="sup_email" maxlength="120" value="<?= e($s['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sup_phone">Phone</label>
                            <input class="form-control" id="sup_phone" name="sup_phone" maxlength="20" value="<?= e($s['phone']) ?>">
                        </div>
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
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-link">Back to login</a>
                    <button type="submit" class="btn btn-siams">Submit registration</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
