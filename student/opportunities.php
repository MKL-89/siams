<?php
/** Student: view and search open attachment opportunities (FR-06). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/opportunities.php';
require_role('student');

$q      = trim($_GET['q'] ?? '');
$county = $_GET['county'] ?? '';
$sector = $_GET['sector'] ?? '';

// Only open opportunities, from approved organizations, whose deadline has not passed.
$sql = "SELECT op.*, o.name AS org_name, o.county, o.sector
        FROM opportunities op JOIN organizations o ON o.org_id = op.org_id
        WHERE op.status = 'open' AND o.status = 'approved'
          AND (op.deadline IS NULL OR op.deadline >= CURDATE())";
$params = [];
if ($q !== '') {
    $sql .= ' AND (op.title LIKE ? OR op.description LIKE ? OR o.name LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if (in_array($county, COUNTIES, true)) {
    $sql .= ' AND o.county = ?';
    $params[] = $county;
}
if (in_array($sector, SECTORS, true)) {
    $sql .= ' AND o.sector = ?';
    $params[] = $sector;
}
// Sort order and page (12 opportunities per page).
$sorts = [
    'deadline' => 'op.deadline ASC, op.title',
    'newest'   => 'op.created_at DESC, op.opportunity_id DESC',
    'title'    => 'op.title ASC',
];
$sort  = array_key_exists($_GET['sort'] ?? '', $sorts) ? $_GET['sort'] : 'deadline';
$count = db()->prepare('SELECT COUNT(*) FROM (' . $sql . ') AS found');
$count->execute($params);
$total   = (int) $count->fetchColumn();
$perPage = 12;
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$sql .= ' ORDER BY ' . $sorts[$sort] . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);
$stmt = db()->prepare($sql);
$stmt->execute($params);
$opps = $stmt->fetchAll();

/** Link to another page of results, keeping the current search. */
function page_url(int $p): string
{
    return '?' . http_build_query(array_merge($_GET, ['page' => $p]));
}

$page_title = 'Find opportunities';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Attachment opportunities</h1>
<form class="row g-2 mb-4" method="get">
    <div class="col-md-4"><input class="form-control" name="q" placeholder="Search by keyword or organization" value="<?= e($q) ?>"></div>
    <div class="col-md-2"><select class="form-select" name="county"><?= options(COUNTIES, $county, 'All counties') ?></select></div>
    <div class="col-md-2"><select class="form-select" name="sector"><?= options(SECTORS, $sector, 'All sectors') ?></select></div>
    <div class="col-md-2">
        <select class="form-select" name="sort" aria-label="Sort by">
            <?php foreach (['deadline' => 'Closing soonest', 'newest' => 'Newest first', 'title' => 'Title A–Z'] as $k => $label): ?>
                <option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-siams w-100"><i class="bi bi-search me-1"></i>Search</button></div>
</form>

<p class="text-muted small"><?= $total ?> open opportunit<?= $total === 1 ? 'y' : 'ies' ?> found<?= $pages > 1 ? ' · page ' . $page . ' of ' . $pages : '' ?>.</p>
<div class="row g-3">
    <?php if (!$opps): ?>
        <div class="col-12"><div class="alert alert-light border">No open opportunities match your search.</div></div>
    <?php endif; ?>
    <?php foreach ($opps as $op): ?>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
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
                    <a class="btn btn-sm btn-outline-primary" href="opportunity.php?id=<?= (int) $op['opportunity_id'] ?>">View details</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($pages > 1): ?>
    <nav class="mt-4" aria-label="Opportunity pages">
        <ul class="pagination justify-content-center flex-wrap">
            <li class="page-item<?= $page === 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(page_url($page - 1)) ?>">Previous</a></li>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <li class="page-item<?= $p === $page ? ' active' : '' ?>"><a class="page-link" href="<?= e(page_url($p)) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <li class="page-item<?= $page === $pages ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(page_url($page + 1)) ?>">Next</a></li>
        </ul>
    </nav>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
