<?php
/**
 * Page header and navigation bar. Set $page_title before including this file.
 * The menu shows only the links allowed for the logged-in user's role.
 */
$page_title = $page_title ?? APP_NAME;
$role = current_role();

// Menu items per role: label => path. New modules add their pages here.
$menus = [
    'admin'       => ['Dashboard' => '/admin/index.php'],
    'coordinator' => [
        'Dashboard'     => '/coordinator/index.php',
        'Organizations' => '/coordinator/organizations.php',
        'Opportunities' => '/coordinator/opportunities.php',
        'Placements'    => '/coordinator/placements.php',
        'Supervisors'   => '/coordinator/assign.php',
        'Results'       => '/coordinator/results.php',
        'Documents'     => '/coordinator/documents.php',
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
            <span class="navbar-text text-white-50 me-3 small">
                <i class="bi bi-person-circle me-1"></i><?= e($_SESSION['full_name']) ?>
                · <?= e(ROLE_LABEL[$role]) ?>
            </span>
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>/logout.php">
                <i class="bi bi-box-arrow-right me-1"></i>Log out
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>
<main class="container py-4">
