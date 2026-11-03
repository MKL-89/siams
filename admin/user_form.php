<?php
/**
 * Administrator: create a user account or edit one, including changing its role (FR-26).
 * Each role has its own profile details (registration number, staff number, organization ...).
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$isSelf = $id === (int) $_SESSION['user_id'];

$u = [
    'full_name' => '', 'email' => '', 'phone' => '', 'role' => 'student',
    'reg_no' => '', 'programme' => 'Diploma in Information Technology', 'year_of_study' => '1',
    'staff_no' => '', 'school' => 'School of Science, Engineering & Technology', 'department' => 'Computer Science',
    'org_id' => '', 'position' => '',
];

/** Loads the profile details of a user for the given role into $u. */
function load_profile(array &$u, int $userId, string $role): void
{
    $queries = [
        'student'     => 'SELECT reg_no, programme, year_of_study FROM students WHERE user_id = ?',
        'coordinator' => 'SELECT staff_no, school FROM coordinators WHERE user_id = ?',
        'lecturer'    => 'SELECT staff_no, department FROM lecturers WHERE user_id = ?',
        'industrial'  => 'SELECT org_id, position FROM ind_supervisors WHERE user_id = ?',
    ];
    if (!isset($queries[$role])) {
        return;
    }
    $stmt = db()->prepare($queries[$role]);
    $stmt->execute([$userId]);
    foreach ($stmt->fetch() ?: [] as $k => $v) {
        $u[$k] = (string) $v;
    }
}

if ($id) {
    $stmt = db()->prepare('SELECT full_name, email, phone, role FROM users WHERE user_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('Account not found.', 'danger');
        redirect('/admin/users.php');
    }
    $u = array_merge($u, array_map('strval', $row));
    load_profile($u, $id, $row['role']);
    $oldRole = $row['role'];
}

$orgs = db()->query("SELECT org_id, name FROM organizations WHERE status = 'approved' ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($u as $k => $_) { $u[$k] = post_str($k); }
    $u['email'] = strtolower($u['email']);
    if ($isSelf) {
        $u['role'] = $oldRole;   // administrators cannot change their own role
    }

    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    if ($u['full_name'] === '' || mb_strlen($u['full_name']) > 100) {
        $errors[] = 'Full name is required (maximum 100 characters).';
    }
    if (!filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $dup = db()->prepare('SELECT 1 FROM users WHERE email = ? AND user_id <> ?');
        $dup->execute([$u['email'], $id]);
        if ($dup->fetchColumn()) {
            $errors[] = 'Another account already uses this email address.';
        }
    }
    if ($u['phone'] !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $u['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!array_key_exists($u['role'], ROLE_LABEL)) {
        $errors[] = 'Please choose a role.';
    }
    if (!$id && ($problem = password_problem($_POST['password'] ?? '', $_POST['password_confirm'] ?? ''))) {
        $errors[] = $problem;
    }

    // Profile details required for the chosen role.
    $uniqueCheck = null;
    switch ($u['role']) {
        case 'student':
            if ($u['reg_no'] === '' || mb_strlen($u['reg_no']) > 30) { $errors[] = 'Registration number is required.'; }
            if ($u['programme'] === '') { $errors[] = 'Programme is required.'; }
            if (!in_array($u['year_of_study'], ['1', '2', '3', '4'], true)) { $errors[] = 'Year of study must be 1 to 4.'; }
            $uniqueCheck = ['SELECT 1 FROM students WHERE reg_no = ? AND user_id <> ?', $u['reg_no'], 'registration number'];
            break;
        case 'coordinator':
        case 'lecturer':
            if ($u['staff_no'] === '' || mb_strlen($u['staff_no']) > 30) { $errors[] = 'Staff number is required.'; }
            $table = $u['role'] === 'lecturer' ? 'lecturers' : 'coordinators';
            $uniqueCheck = ["SELECT 1 FROM $table WHERE staff_no = ? AND user_id <> ?", $u['staff_no'], 'staff number'];
            break;
        case 'industrial':
            if (!array_key_exists((int) $u['org_id'], $orgs)) { $errors[] = 'Please choose the host organization.'; }
            break;
    }
    if (!$errors && $uniqueCheck) {
        $dup = db()->prepare($uniqueCheck[0]);
        $dup->execute([$uniqueCheck[1], $id]);
        if ($dup->fetchColumn()) {
            $errors[] = 'Another account already uses this ' . $uniqueCheck[2] . '.';
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        if ($id) {
            $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE user_id = ?')
                ->execute([$u['full_name'], $u['email'], $u['phone'], $u['role'], $id]);
            $userId = $id;
        } else {
            $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)")
                ->execute([$u['full_name'], $u['email'], $u['phone'], password_hash($_POST['password'], PASSWORD_DEFAULT), $u['role']]);
            $userId = (int) $pdo->lastInsertId();
        }

        // Create or update the profile row for the role.
        $profile = [
            'student'     => ['INSERT INTO students (user_id, reg_no, programme, year_of_study) VALUES (?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE reg_no = VALUES(reg_no), programme = VALUES(programme), year_of_study = VALUES(year_of_study)',
                              [$userId, $u['reg_no'], $u['programme'], (int) $u['year_of_study']]],
            'coordinator' => ['INSERT INTO coordinators (user_id, staff_no, school) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE staff_no = VALUES(staff_no), school = VALUES(school)',
                              [$userId, $u['staff_no'], $u['school']]],
            'lecturer'    => ['INSERT INTO lecturers (user_id, staff_no, department) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE staff_no = VALUES(staff_no), department = VALUES(department)',
                              [$userId, $u['staff_no'], $u['department']]],
            'industrial'  => ['INSERT INTO ind_supervisors (user_id, org_id, position) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE org_id = VALUES(org_id), position = VALUES(position)',
                              [$userId, (int) $u['org_id'], $u['position']]],
        ];
        if (isset($profile[$u['role']])) {
            $pdo->prepare($profile[$u['role']][0])->execute($profile[$u['role']][1]);
        }
        $pdo->commit();

        if ($id) {
            $detail = 'User #' . $id . ($oldRole !== $u['role'] ? " role changed from $oldRole to {$u['role']}" : '');
            log_action($oldRole !== $u['role'] ? 'user_role_changed' : 'user_updated', $detail);
            flash('Account updated.');
        } else {
            log_action('user_created', 'User #' . $userId . ' (' . $u['role'] . ')');
            flash('Account created. Give the user their email address and password.');
        }
        redirect('/admin/users.php');
    }
}

$page_title = $id ? 'Edit account' : 'New account';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3"><?= e($page_title) ?></h1>
        <?php show_errors($errors); ?>
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="full_name">Full name</label>
                    <input class="form-control" id="full_name" name="full_name" maxlength="100" value="<?= e($u['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="role">Role</label>
                    <select class="form-select" id="role" name="role" <?= $isSelf ? 'disabled' : '' ?>>
                        <?php foreach (ROLE_LABEL as $key => $label): ?>
                            <option value="<?= $key ?>"<?= $u['role'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isSelf): ?><div class="form-text">You cannot change your own role.</div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email (used to log in)</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="120" value="<?= e($u['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone</label>
                    <input class="form-control" id="phone" name="phone" maxlength="20" value="<?= e($u['phone']) ?>">
                </div>

                <?php if (!$id): ?>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                        <div class="form-text">At least 8 characters, with letters and numbers.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Confirm password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                    </div>
                <?php endif; ?>

                <div class="col-12 role-fields" data-role="student">
                    <h2 class="h6 mt-2">Student details</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="reg_no">Registration number</label>
                            <input class="form-control" id="reg_no" name="reg_no" maxlength="30" value="<?= e($u['reg_no']) ?>"></div>
                        <div class="col-md-5"><label class="form-label" for="programme">Programme</label>
                            <input class="form-control" id="programme" name="programme" maxlength="100" value="<?= e($u['programme']) ?>"></div>
                        <div class="col-md-3"><label class="form-label" for="year_of_study">Year of study</label>
                            <select class="form-select" id="year_of_study" name="year_of_study">
                                <?php foreach (['1', '2', '3', '4'] as $y): ?>
                                    <option<?= $u['year_of_study'] === $y ? ' selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select></div>
                    </div>
                </div>
                <div class="col-12 role-fields" data-role="coordinator lecturer">
                    <h2 class="h6 mt-2">Staff details</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="staff_no">Staff number</label>
                            <input class="form-control" id="staff_no" name="staff_no" maxlength="30" value="<?= e($u['staff_no']) ?>"></div>
                        <div class="col-md-8 role-fields" data-role="coordinator"><label class="form-label" for="school">School</label>
                            <input class="form-control" id="school" name="school" maxlength="120" value="<?= e($u['school']) ?>"></div>
                        <div class="col-md-8 role-fields" data-role="lecturer"><label class="form-label" for="department">Department</label>
                            <input class="form-control" id="department" name="department" maxlength="120" value="<?= e($u['department']) ?>"></div>
                    </div>
                </div>
                <div class="col-12 role-fields" data-role="industrial">
                    <h2 class="h6 mt-2">Industrial supervisor details</h2>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label" for="org_id">Host organization</label>
                            <select class="form-select" id="org_id" name="org_id">
                                <option value="">Select...</option>
                                <?php foreach ($orgs as $oid => $oname): ?>
                                    <option value="<?= (int) $oid ?>"<?= (string) $oid === $u['org_id'] ? ' selected' : '' ?>><?= e($oname) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-5"><label class="form-label" for="position">Position</label>
                            <input class="form-control" id="position" name="position" maxlength="80" value="<?= e($u['position']) ?>"></div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="users.php" class="btn btn-link">Cancel</a>
                <button type="submit" class="btn btn-siams"><?= $id ? 'Save changes' : 'Create account' ?></button>
            </div>
        </form>
    </div>
</div>
<script>
    // Show only the detail fields that belong to the chosen role.
    (function () {
        const role = document.getElementById('role');
        function update() {
            document.querySelectorAll('.role-fields').forEach(function (box) {
                box.style.display = box.dataset.role.split(' ').includes(role.value) ? '' : 'none';
            });
        }
        role.addEventListener('change', update);
        update();
    })();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
