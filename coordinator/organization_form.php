<?php
/** Coordinator: add a new host organization or edit an existing one. */
require_once __DIR__ . '/../includes/auth.php';
require_role('coordinator');

$id = (int) ($_GET['id'] ?? 0);
$o = ['name' => '', 'sector' => '', 'county' => '', 'address' => '', 'contact_email' => '', 'contact_phone' => ''];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM organizations WHERE org_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('Organization not found.', 'danger');
        redirect('/coordinator/organizations.php');
    }
    $o = array_intersect_key($row, $o);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($o as $k => $_) { $o[$k] = post_str($k); }
    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    $errors = array_merge($errors, validate_organization($o));

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE organizations SET name = ?, sector = ?, county = ?, address = ?, contact_email = ?, contact_phone = ? WHERE org_id = ?')
                ->execute([$o['name'], $o['sector'], $o['county'], $o['address'], $o['contact_email'], $o['contact_phone'], $id]);
            flash('Organization updated.');
        } else {
            // Organizations added by the coordinator are approved straight away.
            db()->prepare("INSERT INTO organizations (name, sector, county, address, contact_email, contact_phone, status, reviewed_by, reviewed_at)
                           VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, NOW())")
                ->execute([$o['name'], $o['sector'], $o['county'], $o['address'], $o['contact_email'], $o['contact_phone'], $_SESSION['user_id']]);
            flash('Organization added.');
        }
        redirect('/coordinator/organizations.php');
    }
}

$page_title = $id ? 'Edit organization' : 'Add organization';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3"><?= e($page_title) ?></h1>
        <?php show_errors($errors); ?>
        <form method="post" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body">
                <?php require __DIR__ . '/../includes/organization_fields.php'; ?>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="organizations.php" class="btn btn-link">Cancel</a>
                <button type="submit" class="btn btn-siams">Save</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
