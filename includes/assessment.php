<?php
/**
 * Assessments and the final attachment mark (FR-16, FR-19, FR-24).
 * Each placement gets three scores out of 100: industrial supervisor, university
 * supervisor and logbook. The final mark uses the weights in the settings table.
 */

const COMPONENTS = [
    'industrial' => 'Industrial supervisor assessment',
    'university' => 'University supervisor assessment',
    'logbook'    => 'Logbook assessment',
];

/** Weights (percent of the final mark) from the settings table, e.g. ['industrial' => 40, ...]. */
function mark_weights(): array
{
    $rows = db()->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'weight\\_%'")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
    $weights = [];
    foreach (array_keys(COMPONENTS) as $c) {
        $weights[$c] = (float) ($rows['weight_' . $c] ?? 0);
    }
    return $weights;
}

/** The submitted evaluations of a placement, keyed by component. */
function placement_scores(int $placementId): array
{
    $stmt = db()->prepare(
        'SELECT e.*, u.full_name AS evaluator_name FROM evaluations e
         JOIN users u ON u.user_id = e.evaluator_id WHERE e.placement_id = ?'
    );
    $stmt->execute([$placementId]);
    return array_column($stmt->fetchAll(), null, 'component');
}

/** Final mark out of 100, or null until all three components have been submitted. */
function final_mark(array $scores, array $weights): ?float
{
    $total = 0.0;
    foreach (array_keys(COMPONENTS) as $c) {
        if (!isset($scores[$c])) {
            return null;
        }
        $total += (float) $scores[$c]['score'] * $weights[$c] / 100;
    }
    return round($total, 2);
}

/** Checks a submitted score and comment. Returns a list of errors. */
function validate_score(string $score, string $comments): array
{
    $errors = [];
    if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $score) || (float) $score > 100) {
        $errors[] = 'The score must be a number from 0 to 100 (up to two decimal places).';
    }
    if ($comments === '' || mb_strlen($comments) > 2000) {
        $errors[] = 'Please add comments on the student\'s performance (maximum 2,000 characters).';
    }
    return $errors;
}

/** Saves (or updates) one component's score for a placement. */
function save_score(int $placementId, string $component, string $score, string $comments): void
{
    db()->prepare(
        'INSERT INTO evaluations (placement_id, component, evaluator_id, score, comments)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE evaluator_id = VALUES(evaluator_id), score = VALUES(score),
                                 comments = VALUES(comments), submitted_at = NOW()'
    )->execute([$placementId, $component, $_SESSION['user_id'], $score, $comments]);
}

/** Prints the score form for one component. $existing is the saved evaluation, if any. */
function score_form(string $component, ?array $existing, array $values, array $errors, string $hint): void
{
    $weight = mark_weights()[$component];
    ?>
    <form method="post" class="card shadow-sm mb-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="component" value="<?= e($component) ?>">
        <div class="card-body">
            <h2 class="h5 mb-1"><?= e(COMPONENTS[$component]) ?></h2>
            <p class="text-muted small"><?= e($hint) ?> Counts for <?= e((string) $weight) ?>% of the final mark.</p>
            <?php show_errors($errors); ?>
            <?php if ($existing): ?>
                <div class="alert alert-success py-2 small">Submitted on <?= nice_date($existing['submitted_at']) ?>. You can update it below.</div>
            <?php endif; ?>
            <div class="mb-3 col-sm-4">
                <label class="form-label" for="score_<?= e($component) ?>">Score (out of 100)</label>
                <input type="number" class="form-control" id="score_<?= e($component) ?>" name="score" min="0" max="100" step="0.01"
                       value="<?= e($values['score']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="comments_<?= e($component) ?>">Comments</label>
                <textarea class="form-control" id="comments_<?= e($component) ?>" name="comments" rows="4" maxlength="2000" required><?= e($values['comments']) ?></textarea>
            </div>
            <button class="btn btn-siams"><?= $existing ? 'Update assessment' : 'Submit assessment' ?></button>
        </div>
    </form>
    <?php
}
