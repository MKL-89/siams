<?php
/**
 * Search banner, opportunity cards and page links, shared by the public
 * opportunities page and the student opportunities page.
 * Expects $result (from search_open_opportunities) and $detailUrl (link before the id).
 * Optional: $listTitle, $listIntro and $listAction (HTML of a button shown in the banner).
 */
$listTitle  = $listTitle ?? 'Attachment opportunities';
$listIntro  = $listIntro ?? 'Find an attachment that matches your skills and apply online.';
$listAction = $listAction ?? '';

require_once __DIR__ . '/opportunity_list_helpers.php';

$today = new DateTimeImmutable('today');
?>
<section class="opp-hero rounded-4 text-white mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 position-relative">
        <div>
            <h1 class="h2 fw-bold mb-1"><?= e($listTitle) ?></h1>
            <p class="mb-3 landing-lead"><?= e($listIntro) ?></p>
        </div>
        <?= $listAction ?>
    </div>
    <form class="opp-search row g-2 position-relative" method="get">
        <div class="col-lg-12 col-xl-3">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input class="form-control" name="q" placeholder="Keyword, skill or organization" value="<?= e($result['q']) ?>" aria-label="Search">
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 col-xl-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                <select class="form-select" name="county" aria-label="County"><?= options(COUNTIES, $result['county'], 'All counties') ?></select>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 col-xl-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-tag"></i></span>
                <select class="form-select" name="sector" aria-label="Sector"><?= options(SECTORS, $result['sector'], 'All sectors') ?></select>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 col-xl-3">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-sort-down"></i></span>
                <select class="form-select" name="sort" aria-label="Sort by">
                    <?php foreach (OPPORTUNITY_SORTS as $k => [$label]): ?>
                        <option value="<?= $k ?>"<?= $result['sort'] === $k ? ' selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 col-xl-2"><button class="btn btn-light text-siams fw-semibold w-100"><i class="bi bi-search me-1"></i>Search</button></div>
    </form>
</section>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted small mb-0"><?= $result['total'] ?> open opportunit<?= $result['total'] === 1 ? 'y' : 'ies' ?> found<?= $result['pages'] > 1 ? ' · page ' . $result['page'] . ' of ' . $result['pages'] : '' ?>.</p>
    <?php if ($result['q'] !== '' || $result['county'] !== '' || $result['sector'] !== ''): ?>
        <a class="small" href="?"><i class="bi bi-x-circle me-1"></i>Clear search</a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php if (!$result['rows']): ?>
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <div class="landing-step-icon mx-auto mb-3"><i class="bi bi-search"></i></div>
                    <h2 class="h5 fw-bold">No open opportunities match your search</h2>
                    <p class="text-muted mb-3">Try another keyword, or choose all counties and sectors.</p>
                    <a class="btn btn-siams" href="?">Show all opportunities</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($result['rows'] as $op):
        $daysLeft = $op['deadline'] ? (int) $today->diff(new DateTimeImmutable($op['deadline']))->format('%r%a') : null;
        $isNew    = $op['created_at'] && strtotime($op['created_at']) >= strtotime('-7 days');
        $closing  = $daysLeft !== null && $daysLeft <= 7;
    ?>
        <div class="col-md-6 col-xl-4">
            <article class="card h-100 opportunity-card opp-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="org-badge <?= org_badge_class($op['org_name']) ?>" aria-hidden="true"><?= e(org_initials($op['org_name'])) ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <h2 class="h5 card-title mb-1"><?= e($op['title']) ?></h2>
                            <p class="text-muted small mb-0 text-truncate" title="<?= e($op['org_name']) ?>"><?= e($op['org_name']) ?></p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="opp-chip"><i class="bi bi-geo-alt"></i><?= e($op['county']) ?></span>
                        <span class="opp-chip"><i class="bi bi-tag"></i><?= e($op['sector']) ?></span>
                        <span class="opp-chip"><i class="bi bi-people"></i><?= (int) $op['slots'] ?> slot<?= (int) $op['slots'] === 1 ? '' : 's' ?></span>
                        <?php if ($isNew): ?><span class="opp-chip opp-chip-new">New</span><?php endif; ?>
                    </div>
                    <p class="card-text small text-secondary opp-desc mb-0"><?= e($op['description']) ?></p>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <span class="small <?= $closing ? 'text-danger fw-semibold' : 'text-muted' ?>">
                        <i class="bi bi-<?= $closing ? 'alarm' : 'calendar-event' ?> me-1"></i><?php
                        if ($daysLeft === null) { echo 'No deadline'; }
                        elseif ($daysLeft === 0) { echo 'Closes today'; }
                        elseif ($daysLeft === 1) { echo '1 day left'; }
                        else { echo $daysLeft . ' days left'; } ?>
                        <span class="d-block opp-date">Apply by <?= nice_date($op['deadline']) ?></span>
                    </span>
                    <a class="btn btn-sm btn-siams" href="<?= e($detailUrl) . (int) $op['opportunity_id'] ?>">View details</a>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($result['pages'] > 1): ?>
    <nav class="mt-4" aria-label="Opportunity pages">
        <ul class="pagination justify-content-center flex-wrap">
            <li class="page-item<?= $result['page'] === 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(opportunity_page_url($result['page'] - 1)) ?>"><i class="bi bi-chevron-left"></i> Previous</a></li>
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <li class="page-item<?= $p === $result['page'] ? ' active' : '' ?>"><a class="page-link" href="<?= e(opportunity_page_url($p)) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <li class="page-item<?= $result['page'] === $result['pages'] ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(opportunity_page_url($result['page'] + 1)) ?>">Next <i class="bi bi-chevron-right"></i></a></li>
        </ul>
    </nav>
<?php endif; ?>
