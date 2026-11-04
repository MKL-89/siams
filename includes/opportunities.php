<?php
/** Shared code for attachment opportunities (FR-06, FR-13). */

/** Validates an opportunity form. $isNew checks that the deadline is not in the past. */
function validate_opportunity(array $op, bool $isNew): array
{
    $errors = [];
    if ($op['title'] === '' || mb_strlen($op['title']) > 150) {
        $errors[] = 'Title is required (maximum 150 characters).';
    }
    if ($op['description'] === '' || mb_strlen($op['description']) > 2000) {
        $errors[] = 'Description is required (maximum 2,000 characters).';
    }
    if (!ctype_digit($op['slots']) || (int) $op['slots'] < 1 || (int) $op['slots'] > 50) {
        $errors[] = 'Number of slots must be a whole number from 1 to 50.';
    }
    $d = DateTime::createFromFormat('Y-m-d', $op['deadline']);
    if (!$d || $d->format('Y-m-d') !== $op['deadline']) {
        $errors[] = 'Please enter a valid application deadline.';
    } elseif ($isNew && $op['deadline'] < date('Y-m-d')) {
        $errors[] = 'The application deadline cannot be in the past.';
    }
    return $errors;
}

/** Opens or closes an opportunity. $orgId limits the change to one organization (null = any). */
function set_opportunity_status(int $oppId, string $status, ?int $orgId): bool
{
    $sql = 'UPDATE opportunities SET status = ? WHERE opportunity_id = ?';
    $params = [$status, $oppId];
    if ($orgId !== null) {
        $sql .= ' AND org_id = ?';
        $params[] = $orgId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() === 1;
}

/** Handles the open/close buttons on the opportunity lists. */
function handle_status_toggle(?int $orgId, string $back): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $status = $_POST['status'] ?? '';
    if (!csrf_valid() || !in_array($status, ['open', 'closed'], true)) {
        flash('The request could not be processed. Please try again.', 'danger');
    } elseif (set_opportunity_status((int) ($_POST['opportunity_id'] ?? 0), $status, $orgId)) {
        flash($status === 'open' ? 'Opportunity reopened.' : 'Opportunity closed.');
    } else {
        flash('No change was made.', 'warning');
    }
    redirect($back);
}

/** True when students can still apply: open and the deadline has not passed. */
function is_accepting(array $op): bool
{
    return $op['status'] === 'open' && ($op['deadline'] === null || $op['deadline'] >= date('Y-m-d'));
}

/* ---------- Searching open opportunities (public page and student page) ---------- */

const OPPORTUNITY_SORTS = [
    'deadline' => ['Closing soonest', 'op.deadline ASC, op.title'],
    'newest'   => ['Newest first', 'op.created_at DESC, op.opportunity_id DESC'],
    'title'    => ['Title A–Z', 'op.title ASC'],
];
const OPPORTUNITIES_PER_PAGE = 12;

/**
 * Open opportunities from approved organizations whose deadline has not passed,
 * filtered by the search form in $f (q, county, sector, sort, page).
 * Returns the rows for one page plus the total, page and page count.
 */
function search_open_opportunities(array $f): array
{
    $q      = trim((string) ($f['q'] ?? ''));
    $county = (string) ($f['county'] ?? '');
    $sector = (string) ($f['sector'] ?? '');
    $sort   = array_key_exists($f['sort'] ?? '', OPPORTUNITY_SORTS) ? $f['sort'] : 'deadline';

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

    $count = db()->prepare('SELECT COUNT(*) FROM (' . $sql . ') AS found');
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / OPPORTUNITIES_PER_PAGE));
    $page  = min($pages, max(1, (int) ($f['page'] ?? 1)));

    $sql .= ' ORDER BY ' . OPPORTUNITY_SORTS[$sort][1]
          . ' LIMIT ' . OPPORTUNITIES_PER_PAGE . ' OFFSET ' . (($page - 1) * OPPORTUNITIES_PER_PAGE);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages,
            'q' => $q, 'county' => $county, 'sector' => $sector, 'sort' => $sort];
}

/** Link to another page of results, keeping the current search. */
function opportunity_page_url(int $p): string
{
    return '?' . http_build_query(array_merge($_GET, ['page' => $p]));
}

/** Title of an opportunity that is open to applications, or null. Used by "Log in to apply". */
function open_opportunity_title(int $oppId): ?string
{
    $stmt = db()->prepare("SELECT op.* FROM opportunities op JOIN organizations o ON o.org_id = op.org_id
                           WHERE op.opportunity_id = ? AND o.status = 'approved'");
    $stmt->execute([$oppId]);
    $op = $stmt->fetch();
    return ($op && is_accepting($op)) ? $op['title'] : null;
}

/** Counts used on the landing page. */
function opportunity_stats(): array
{
    $open = "op.status = 'open' AND o.status = 'approved' AND (op.deadline IS NULL OR op.deadline >= CURDATE())";
    $row = db()->query("SELECT COUNT(*) AS opportunities, COALESCE(SUM(op.slots), 0) AS slots,
                               COUNT(DISTINCT o.org_id) AS organizations, COUNT(DISTINCT o.county) AS counties
                        FROM opportunities op JOIN organizations o ON o.org_id = op.org_id WHERE $open")->fetch();
    return array_map('intval', $row);
}
