<?php
/** Shared rules for applications and placements (FR-07, FR-14, FR-20, FR-22). */

/** The attachment period currently in use, or null if the coordinator has not set one. */
function active_period(): ?array
{
    $row = db()->query('SELECT * FROM attachment_periods WHERE is_active = 1 LIMIT 1')->fetch();
    return $row ?: null;
}

/** The student_id of the logged-in student. */
function my_student_id(): int
{
    $stmt = db()->prepare('SELECT student_id FROM students WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

/** The ind_sup_id and org_id of the logged-in industrial supervisor. */
function my_supervisor(): array
{
    $stmt = db()->prepare('SELECT ind_sup_id, org_id FROM ind_supervisors WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: ['ind_sup_id' => 0, 'org_id' => 0];
}

/** The lecturer_id of the logged-in university supervisor. */
function my_lecturer_id(): int
{
    $stmt = db()->prepare('SELECT lecturer_id FROM lecturers WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

/** True when the student already holds an approved (or later) placement in the period. */
function has_confirmed_placement(int $studentId, ?int $periodId): bool
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM placements
         WHERE student_id = ? AND period_id <=> ? AND status IN ('approved','ongoing','completed')"
    );
    $stmt->execute([$studentId, $periodId]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Checks whether a student may apply for an opportunity. Returns null when allowed,
 * otherwise the reason to show.
 */
function cannot_apply_reason(int $studentId, array $op): ?string
{
    if (!is_accepting($op)) {
        return 'This opportunity is no longer accepting applications.';
    }
    $stmt = db()->prepare('SELECT status FROM applications WHERE student_id = ? AND opportunity_id = ?');
    $stmt->execute([$studentId, $op['opportunity_id']]);
    if ($stmt->fetchColumn() !== false) {
        return 'You have already applied for this opportunity.';
    }
    $period = active_period();
    if (has_confirmed_placement($studentId, $period['period_id'] ?? null)) {
        return 'You already have an approved placement for this attachment period.';
    }
    return null;
}

/** Number of students the host has already accepted (or the coordinator approved) for an opportunity. */
function filled_slots(int $opportunityId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM applications WHERE opportunity_id = ? AND status IN ('accepted','approved')");
    $stmt->execute([$opportunityId]);
    return (int) $stmt->fetchColumn();
}
