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
