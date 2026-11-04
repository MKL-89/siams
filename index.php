<?php
/**
 * Public landing page: explains what SIAMS does and shows the latest attachment
 * opportunities. Visitors can browse freely; they log in or create an account only
 * when they want to apply. Logged-in users go straight to their dashboard.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/opportunities.php';
start_secure_session();

if (!empty($_SESSION['user_id'])) {
    redirect(ROLE_HOME[current_role()]);
}

$stats  = opportunity_stats();
$latest = array_slice(search_open_opportunities(['sort' => 'newest'])['rows'], 0, 6);

$page_title = 'Industrial attachment made simple';
$full_width = true;
require __DIR__ . '/includes/header.php';
?>
<!-- Hero -->
<section class="landing-hero text-white">
    <div class="container py-5">
        <div class="row align-items-center g-4 py-lg-4">
            <div class="col-lg-7">
                <p class="text-uppercase small fw-semibold landing-eyebrow mb-2"><?= e(INSTITUTION) ?></p>
                <h1 class="display-5 fw-bold mb-3">Find, manage and complete your industrial attachment in one place</h1>
                <p class="lead mb-4 landing-lead">
                    <?= APP_NAME ?> connects students of the School of Science, Engineering &amp; Technology with host
                    organizations, and lets the attachment office, supervisors and students follow every placement
                    from application to final mark.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light btn-lg text-siams fw-semibold" href="<?= BASE_URL ?>/opportunities.php">
                        <i class="bi bi-search me-1"></i>Browse opportunities
                    </a>
                    <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/register.php">
                        <i class="bi bi-person-plus me-1"></i>Create a student account
                    </a>
                </div>
                <p class="small mt-3 mb-0 landing-lead">
                    Already have an account? <a class="link-light fw-semibold" href="<?= BASE_URL ?>/login.php">Log in</a>
                </p>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <?php foreach ([
                        ['bi-briefcase', $stats['opportunities'], 'Open opportunities'],
                        ['bi-people', $stats['slots'], 'Attachment slots'],
                        ['bi-building', $stats['organizations'], 'Host organizations'],
                        ['bi-geo-alt', $stats['counties'], 'Counties'],
                    ] as [$icon, $value, $label]): ?>
                        <div class="col-6">
                            <div class="landing-stat rounded-3 p-3 h-100">
                                <i class="bi <?= $icon ?> fs-4"></i>
                                <div class="fs-2 fw-bold lh-1 mt-2"><?= $value ?></div>
                                <div class="small landing-lead"><?= $label ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="container py-5">
    <div class="text-center mb-4">
        <h2 class="h3 fw-bold">How it works</h2>
        <p class="text-muted mb-0">From finding a placement to receiving your final attachment mark.</p>
    </div>
    <div class="row g-4">
        <?php foreach ([
            ['bi-search', 'Find an opportunity', 'Browse open attachment opportunities by keyword, county or sector — no account needed.'],
            ['bi-person-check', 'Create an account and apply', 'Create a student account with your registration number — it is ready at once — and send your application online.'],
            ['bi-building-check', 'Get placed', 'The host organization accepts you, then the attachment office approves the placement and assigns your university supervisor.'],
            ['bi-journal-check', 'Logbook and assessment', 'Fill in your weekly logbook online. Your supervisors assess you and the system calculates your final mark.'],
        ] as $i => [$icon, $title, $text]): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="landing-step-icon mb-3"><i class="bi <?= $icon ?>"></i></div>
                        <p class="small text-muted fw-semibold mb-1">Step <?= $i + 1 ?></p>
                        <h3 class="h6 fw-bold"><?= $title ?></h3>
                        <p class="small text-muted mb-0"><?= $text ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Latest opportunities -->
<section class="bg-white border-top border-bottom">
    <div class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h2 class="h3 fw-bold mb-1">Latest opportunities</h2>
                <p class="text-muted mb-0">Recently posted by host organizations.</p>
            </div>
            <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/opportunities.php">
                View all <?= $stats['opportunities'] ?> opportunities<i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <?php if (!$latest): ?>
            <div class="alert alert-light border">There are no open opportunities at the moment. Please check again soon.</div>
        <?php endif; ?>
        <div class="row g-3">
            <?php foreach ($latest as $op): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100 opportunity-card">
                        <div class="card-body">
                            <span class="badge text-bg-light border mb-2"><?= e($op['sector']) ?></span>
                            <h3 class="h6 fw-bold card-title mb-1"><?= e($op['title']) ?></h3>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-building me-1"></i><?= e($op['org_name']) ?><br>
                                <i class="bi bi-geo-alt me-1"></i><?= e($op['county']) ?>
                            </p>
                            <p class="small mb-0"><?= e(mb_strimwidth($op['description'], 0, 120, '…')) ?></p>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center small">
                            <span class="text-danger"><i class="bi bi-calendar-event me-1"></i><?= nice_date($op['deadline']) ?></span>
                            <a class="btn btn-sm btn-siams" href="<?= BASE_URL ?>/opportunity.php?id=<?= (int) $op['opportunity_id'] ?>">View and apply</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Who it is for -->
<section class="container py-5">
    <div class="text-center mb-4">
        <h2 class="h3 fw-bold">One system for everyone involved</h2>
        <p class="text-muted mb-0">Each user sees only the pages and records that belong to their role.</p>
    </div>
    <div class="row g-4">
        <?php foreach ([
            ['bi-mortarboard', 'Students', 'Apply online, follow your applications, keep a logbook that cannot be lost, upload documents and see your results.'],
            ['bi-building', 'Host organizations', 'Post vacancies, receive organised applications, confirm logbook entries and assess students online.'],
            ['bi-person-workspace', 'University supervisors', 'See your assigned students, record supervision visits, read logbooks and submit assessments.'],
            ['bi-clipboard-data', 'Attachment office', 'Approve placements, assign supervisors and produce placement and results reports in minutes.'],
        ] as [$icon, $title, $text]): ?>
            <div class="col-md-6 col-lg-3">
                <div class="text-center px-2">
                    <div class="landing-role-icon mx-auto mb-3"><i class="bi <?= $icon ?>"></i></div>
                    <h3 class="h6 fw-bold"><?= $title ?></h3>
                    <p class="small text-muted"><?= $text ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Host organization call to action -->
<section class="container pb-5">
    <div class="landing-cta rounded-4 p-4 p-md-5 text-white">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <h2 class="h4 fw-bold mb-2">Is your organization looking for attachment students?</h2>
                <p class="mb-0 landing-lead">Register your organization to post opportunities and receive applications from
                    Kabarak University students. Your account is ready as soon as you register.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a class="btn btn-light btn-lg text-siams fw-semibold" href="<?= BASE_URL ?>/register_host.php">
                    <i class="bi bi-building-add me-1"></i>Register your organization
                </a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
