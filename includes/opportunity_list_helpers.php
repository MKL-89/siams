<?php
/** Small helpers for showing organizations on opportunity cards and pages. */

/** Colour for an organization's initial badge, always the same for the same name. */
function org_badge_class(string $name): string
{
    return 'org-badge-' . (abs(crc32($name)) % 6);
}

/** Initials of an organization, e.g. "Demo Software Studio" -> "DS". */
function org_initials(string $name): string
{
    $words = array_values(array_filter(
        preg_split('/[^A-Za-z0-9]+/', $name),
        fn($w) => $w !== '' && !in_array(strtolower($w), ['and', 'of', 'the', 'ltd'], true)
    ));
    $letters = mb_substr($words[0] ?? '?', 0, 1) . mb_substr($words[1] ?? '', 0, 1);
    return mb_strtoupper($letters);
}
