<?php
/**
 * Search form, opportunity cards and page links, shared by the public
 * opportunities page and the student opportunities page.
 * Expects $result (from search_open_opportunities) and $detailUrl (link before the id).
 */
?>
<form class="row g-2 mb-4" method="get">
    <div class="col-md-4"><input class="form-control" name="q" placeholder="Search by keyword or organization" value="<?= e($result['q']) ?>" aria-label="Search"></div>
    <div class="col-md-2"><select class="form-select" name="county" aria-label="County"><?= options(COUNTIES, $result['county'], 'All counties') ?></select></div>
    <div class="col-md-2"><select class="form-select" name="sector" aria-label="Sector"><?= options(SECTORS, $result['sector'], 'All sectors') ?></select></div>
    <div class="col-md-2">
        <select class="form-select" name="sort" aria-label="Sort by">
            <?php foreach (OPPORTUNITY_SORTS as $k => [$label]): ?>
                <option value="<?= $k ?>"<?= $result['sort'] === $k ? ' selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-siams w-100"><i class="bi bi-search me-1"></i>Search</button></div>
</form>

<p class="text-muted small"><?= $result['total'] ?> open opportunit<?= $result['total'] === 1 ? 'y' : 'ies' ?> found<?= $result['pages'] > 1 ? ' · page ' . $result['page'] . ' of ' . $result['pages'] : '' ?>.</p>
<div class="row g-3">
    <?php if (!$result['rows']): ?>
        <div class="col-12"><div class="alert alert-light border">No open opportunities match your search.</div></div>
    <?php endif; ?>
    <?php foreach ($result['rows'] as $op): ?>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 opportunity-card">
                <div class="card-body">
                    <h2 class="h5 card-title mb-1"><?= e($op['title']) ?></h2>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-building me-1"></i><?= e($op['org_name']) ?> ·
                        <i class="bi bi-geo-alt me-1"></i><?= e($op['county']) ?> · <?= e($op['sector']) ?>
                    </p>
                    <p class="card-text"><?= e(mb_strimwidth($op['description'], 0, 180, '…')) ?></p>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center small">
                    <span><i class="bi bi-people me-1"></i><?= (int) $op['slots'] ?> slot(s)</span>
                    <span class="text-danger"><i class="bi bi-calendar-event me-1"></i>Apply by <?= nice_date($op['deadline']) ?></span>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e($detailUrl) . (int) $op['opportunity_id'] ?>">View details</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($result['pages'] > 1): ?>
    <nav class="mt-4" aria-label="Opportunity pages">
        <ul class="pagination justify-content-center flex-wrap">
            <li class="page-item<?= $result['page'] === 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(opportunity_page_url($result['page'] - 1)) ?>">Previous</a></li>
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <li class="page-item<?= $p === $result['page'] ? ' active' : '' ?>"><a class="page-link" href="<?= e(opportunity_page_url($p)) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <li class="page-item<?= $result['page'] === $result['pages'] ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(opportunity_page_url($result['page'] + 1)) ?>">Next</a></li>
        </ul>
    </nav>
<?php endif; ?>
