<?php
/**
 * Layout pieces for one opportunity, shared by the public details page and the
 * student details page. Each page adds its own apply panel beside the details.
 */
require_once __DIR__ . '/opportunity_list_helpers.php';

/** Days from today to the deadline (negative when passed), or null without a deadline. */
function days_left(?string $deadline): ?int
{
    if (!$deadline) {
        return null;
    }
    return (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($deadline))->format('%r%a');
}

/** Short text such as "12 days left" or "Closes today". */
function days_left_text(?int $days): string
{
    if ($days === null) { return 'No deadline'; }
    if ($days < 0)      { return 'Closed'; }
    if ($days === 0)    { return 'Closes today'; }
    return $days === 1 ? '1 day left' : $days . ' days left';
}

/** Banner with the back link, organization badge, title and labels. */
function opportunity_hero(array $op, string $backUrl, string $backLabel = 'Back to opportunities'): void
{
    $open = is_accepting($op);
    ?>
    <section class="opp-hero rounded-4 text-white mb-4">
        <a href="<?= e($backUrl) ?>" class="link-light small position-relative"><i class="bi bi-arrow-left me-1"></i><?= e($backLabel) ?></a>
        <div class="d-flex flex-wrap align-items-center gap-3 mt-3 position-relative">
            <div class="org-badge org-badge-lg <?= org_badge_class($op['org_name']) ?>" aria-hidden="true"><?= e(org_initials($op['org_name'])) ?></div>
            <div class="flex-grow-1 min-w-0">
                <h1 class="h2 fw-bold mb-1"><?= e($op['title']) ?></h1>
                <p class="mb-2 landing-lead"><i class="bi bi-building me-1"></i><?= e($op['org_name']) ?></p>
                <div class="d-flex flex-wrap gap-1">
                    <span class="opp-chip opp-chip-hero"><i class="bi bi-geo-alt"></i><?= e($op['county']) ?></span>
                    <span class="opp-chip opp-chip-hero"><i class="bi bi-tag"></i><?= e($op['sector']) ?></span>
                    <span class="opp-chip opp-chip-hero"><i class="bi bi-people"></i><?= (int) $op['slots'] ?> slot<?= (int) $op['slots'] === 1 ? '' : 's' ?></span>
                    <?= $open ? '<span class="badge bg-success align-self-center">Open</span>' : '<span class="badge bg-secondary align-self-center">Closed</span>' ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

/** Description and key facts. $showContact adds the organization's email and phone (logged-in users only). */
function opportunity_details(array $op, bool $showContact = false): void
{
    $days = days_left($op['deadline']);
    ?>
    <div class="card mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">About this attachment</h2>
            <p class="mb-0" style="white-space: pre-line"><?= e($op['description']) ?></p>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
                <div class="stat-icon-wrap"><i class="bi bi-geo-alt"></i></div>
                <div><div class="small text-muted">Location</div><div class="fw-semibold"><?= e(trim(($op['address'] ?? '') . ', ' . $op['county'], ', ')) ?></div></div>
            </div></div>
        </div>
        <div class="col-sm-6">
            <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
                <div class="stat-icon-wrap"><i class="bi bi-calendar-event"></i></div>
                <div><div class="small text-muted">Apply by</div>
                    <div class="fw-semibold"><?= nice_date($op['deadline']) ?>
                        <span class="small <?= $days !== null && $days <= 7 ? 'text-danger' : 'text-muted' ?>">· <?= days_left_text($days) ?></span></div></div>
            </div></div>
        </div>
        <?php if ($showContact): ?>
            <div class="col-12">
                <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
                    <div class="stat-icon-wrap"><i class="bi bi-envelope"></i></div>
                    <div><div class="small text-muted">Contact</div><div class="fw-semibold"><?= e($op['contact_email']) ?> · <?= e($op['contact_phone']) ?></div></div>
                </div></div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
