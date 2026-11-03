<?php
/** Shared code for the electronic logbook (FR-10, FR-15, FR-17). */

/**
 * The weeks of a placement: [week_no => start date of that week].
 * Week 1 starts on the placement start date; each later week starts 7 days after.
 */
function placement_weeks(array $placement): array
{
    $weeks = [];
    if (empty($placement['start_date']) || empty($placement['end_date'])) {
        return $weeks;
    }
    $start = new DateTime($placement['start_date']);
    $end   = new DateTime($placement['end_date']);
    for ($n = 1; $start <= $end; $n++) {
        $weeks[$n] = $start->format('Y-m-d');
        $start->modify('+7 days');
    }
    return $weeks;
}

/** Weeks the student may write about now: weeks that have already started. */
function open_weeks(array $placement): array
{
    $today = date('Y-m-d');
    return array_filter(placement_weeks($placement), fn($weekStart) => $weekStart <= $today);
}

/** All logbook entries for a placement, newest week first. */
function logbook_entries(int $placementId): array
{
    $stmt = db()->prepare(
        'SELECT l.*, u.full_name AS confirmed_by_name
         FROM logbook_entries l LEFT JOIN users u ON u.user_id = l.confirmed_by
         WHERE l.placement_id = ? ORDER BY l.week_no DESC'
    );
    $stmt->execute([$placementId]);
    return $stmt->fetchAll();
}

/** Validates the student's entry form. */
function validate_entry(array $en, array $openWeeks): array
{
    $errors = [];
    if (!ctype_digit($en['week_no']) || !array_key_exists((int) $en['week_no'], $openWeeks)) {
        $errors[] = 'Please choose a week of your placement that has already started.';
    }
    if (mb_strlen($en['activities']) < 20 || mb_strlen($en['activities']) > 3000) {
        $errors[] = 'Describe the activities you did this week (20 to 3,000 characters).';
    }
    if (mb_strlen($en['skills_learned']) > 2000 || mb_strlen($en['challenges']) > 2000) {
        $errors[] = 'Skills learned and challenges must each be 2,000 characters or fewer.';
    }
    return $errors;
}

/** Prints one logbook entry as a card. */
function entry_card(array $en): void
{
    ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Week <?= (int) $en['week_no'] ?>
                <span class="text-muted small fw-normal">· from <?= nice_date($en['week_start']) ?></span></span>
            <?= $en['confirmed'] ? '<span class="badge bg-success">Confirmed</span>' : '<span class="badge bg-warning text-dark">Awaiting confirmation</span>' ?>
        </div>
        <div class="card-body small">
            <h3 class="h6 mb-1">Activities</h3>
            <p style="white-space: pre-line"><?= e($en['activities']) ?></p>
            <?php if ($en['skills_learned']): ?>
                <h3 class="h6 mb-1">Skills learned</h3>
                <p style="white-space: pre-line"><?= e($en['skills_learned']) ?></p>
            <?php endif; ?>
            <?php if ($en['challenges']): ?>
                <h3 class="h6 mb-1">Challenges</h3>
                <p style="white-space: pre-line"><?= e($en['challenges']) ?></p>
            <?php endif; ?>
            <?php if ($en['confirmed']): ?>
                <div class="border-start border-3 border-success ps-2 mt-2">
                    <div class="text-muted">Confirmed by <?= e($en['confirmed_by_name']) ?> on <?= nice_date($en['confirmed_at']) ?></div>
                    <?php if ($en['supervisor_comment']): ?>
                        <div style="white-space: pre-line"><?= e($en['supervisor_comment']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white text-muted small">Submitted <?= nice_date($en['submitted_at']) ?></div>
    </div>
    <?php
}
