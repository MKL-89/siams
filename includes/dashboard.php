<?php
/** Small helpers shared by the role dashboards. */

/** Runs a COUNT query with parameters and returns the number. */
function count_of(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/** Prints one summary card. */
function stat_card(string $label, int|string $value, string $icon): void
{
    ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= e((string) $value) ?></div>
                    <div class="text-muted small"><?= e($label) ?></div>
                </div>
                <i class="bi <?= e($icon) ?> stat-icon"></i>
            </div>
        </div>
    </div>
    <?php
}

/** Page heading with a greeting. */
function dashboard_heading(string $subtitle): void
{
    ?>
    <div class="mb-4">
        <h1 class="h3 mb-1">Welcome, <?= e($_SESSION['full_name']) ?></h1>
        <p class="text-muted mb-0"><?= e($subtitle) ?></p>
    </div>
    <?php
}
