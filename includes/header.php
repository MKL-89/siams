<?php
/**
 * Page header and navigation bar. Set $page_title before including this file.
 * The menu shows only the links allowed for the logged-in user's role.
 */
$page_title = $page_title ?? APP_NAME;
$role = current_role();

// Menu items per role: label => path. New modules add their pages here.
$menus = [
    'admin'       => [
        'Dashboard'    => '/admin/index.php',
        'Users'        => '/admin/users.php',
        'Activity log' => '/admin/audit.php',
        'Backup'       => '/admin/backup.php',
    ],
    'coordinator' => [
        'Dashboard'     => '/coordinator/index.php',
        'Organizations' => '/coordinator/organizations.php',
        'Opportunities' => '/coordinator/opportunities.php',
        'Placements'    => '/coordinator/placements.php',
        'Supervisors'   => '/coordinator/assign.php',
        'Results'       => '/coordinator/results.php',
        'Documents'     => '/coordinator/documents.php',
        'Reports'       => '/coordinator/reports.php',
        'Periods'       => '/coordinator/periods.php',
    ],
    'lecturer'    => [
        'Dashboard'   => '/lecturer/index.php',
        'My students' => '/lecturer/students.php',
    ],
    'industrial'  => [
        'Dashboard'     => '/industrial/index.php',
        'Opportunities' => '/industrial/opportunities.php',
        'Applicants'    => '/industrial/applicants.php',
        'My students'   => '/industrial/students.php',
        'Logbooks'      => '/industrial/logbook.php',
        'Assessments'   => '/industrial/assess.php',
    ],
    'student'     => [
        'Dashboard'       => '/student/index.php',
        'Opportunities'   => '/student/opportunities.php',
        'My applications' => '/student/applications.php',
        'My logbook'      => '/student/logbook.php',
        'My documents'    => '/student/documents.php',
        'My results'      => '/student/results.php',
        'My profile'      => '/student/profile.php',
    ],
];
$current_path = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(BASE_URL));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php if ($role): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-siams">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="<?= BASE_URL . ROLE_HOME[$role] ?>">
            <i class="bi bi-briefcase-fill me-1"></i><?= APP_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($menus[$role] as $label => $path): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $current_path === $path ? ' active' : '' ?>"
                           href="<?= BASE_URL . $path ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle text-nowrap" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="<?= e(ROLE_LABEL[$role]) ?>">
                    <i class="bi bi-person-circle me-1"></i><?= e($_SESSION['full_name']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= e(ROLE_LABEL[$role]) ?></span></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/account.php"><i class="bi bi-key me-2"></i>My account (change password)</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/login.php"><i class="bi bi-person-plus me-2"></i>Use another account</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<?php else: ?>
<?php
// Visitors who are not logged in: links to the public pages.
$publicMenu = ['Home' => '/index.php', 'Opportunities' => '/opportunities.php', 'For organizations' => '/register_host.php'];
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-siams">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-briefcase-fill me-1"></i><?= APP_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($publicMenu as $label => $path): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $current_path === $path || ($path === '/index.php' && $current_path === '/') || ($path === '/opportunities.php' && $current_path === '/opportunity.php') ? ' active' : '' ?>"
                           href="<?= BASE_URL . $path ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-outline-light btn-sm me-2 text-nowrap" href="<?= BASE_URL ?>/login.php">
                <i class="bi bi-box-arrow-in-right me-1"></i>Log in
            </a>
            <a class="btn btn-light btn-sm text-nowrap text-siams fw-semibold" href="<?= BASE_URL ?>/register.php">
                <i class="bi bi-person-plus me-1"></i>Create account
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>
<main class="<?= empty($full_width) ? 'container py-4' : '' ?>">
