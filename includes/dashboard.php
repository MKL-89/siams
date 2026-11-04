<?php
/** Small helpers shared by the role dashboards. */

/** Runs a COUNT query with parameters and returns the number. */
function count_of(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/** Prints one summary card. With $href the whole card links to that page. */
function stat_card(string $label, int|string $value, string $icon, ?string $href = null): void
{
    $tag = $href ? 'a' : 'div';
    ?>
    <div class="col-sm-6 col-lg-3">
        <<?= $tag ?> class="card stat-card h-100<?= $href ? ' stat-card-link' : '' ?>"<?= $href ? ' href="' . e($href) . '"' : '' ?>>
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon-wrap"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value"><?= e((string) $value) ?></div>
                    <div class="text-muted small"><?= e($label) ?></div>
                </div>
            </div>
        </<?= $tag ?>>
    </div>
    <?php
}

/** Greeting for the time of day (Africa/Nairobi, set in the configuration). */
function greeting(): string
{
    $h = (int) date('G');
    return $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
}

/** Blue welcome banner at the top of each dashboard, in the style of the home page. */
function dashboard_heading(string $subtitle, string $icon = 'bi-briefcase'): void
{
    $role = current_role();
    ?>
    <section class="dash-hero rounded-4 text-white mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
            <div>
                <p class="text-uppercase small fw-semibold landing-eyebrow mb-1">
                    <?= e(ROLE_LABEL[$role] ?? '') ?> · <?= date('l, j F Y') ?>
                </p>
                <h1 class="h2 fw-bold mb-1"><?= greeting() ?>, <?= e($_SESSION['full_name']) ?></h1>
                <p class="mb-0 landing-lead"><?= e($subtitle) ?></p>
            </div>
            <div class="dash-hero-icon d-none d-sm-flex"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></div>
        </div>
    </section>
    <?php
    show_flash();   // e.g. the welcome message after creating an account
}

/**
 * A row of large links to the main tasks of a role.
 * $items: list of [icon, title, short description, link].
 */
function quick_actions(array $items, string $title = 'Quick actions'): void
{
    ?>
    <h2 class="h5 fw-bold mb-3"><?= e($title) ?></h2>
    <div class="row g-3 mb-4">
        <?php foreach ($items as [$icon, $label, $text, $href]): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card action-card h-100">
                    <div class="card-body">
                        <div class="landing-step-icon mb-3"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></div>
                        <h3 class="h6 fw-bold mb-1">
                            <a class="stretched-link text-reset text-decoration-none" href="<?= e($href) ?>"><?= e($label) ?></a>
                        </h3>
                        <p class="small text-muted mb-0"><?= e($text) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/** A highlighted "what to do next" panel. $tone: info, success, warning. */
function next_step(string $icon, string $title, string $text, ?string $button = null, ?string $href = null, string $tone = 'info'): void
{
    ?>
    <div class="card next-step next-step-<?= e($tone) ?> mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div class="next-step-icon"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></div>
            <div class="flex-grow-1">
                <h2 class="h6 fw-bold mb-1"><?= e($title) ?></h2>
                <p class="small mb-0"><?= e($text) ?></p>
            </div>
            <?php if ($button && $href): ?>
                <a class="btn btn-siams" href="<?= e($href) ?>"><?= e($button) ?><i class="bi bi-arrow-right ms-1"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
