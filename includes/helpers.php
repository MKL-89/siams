<?php
/** General helpers used by forms and list pages. */

/** Kenya's 47 counties, used in organization forms and filters. */
const COUNTIES = [
    'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Garissa', 'Homa Bay',
    'Isiolo', 'Kajiado', 'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii',
    'Kisumu', 'Kitui', 'Kwale', 'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera',
    'Marsabit', 'Meru', 'Migori', 'Mombasa', "Murang'a", 'Nairobi', 'Nakuru', 'Nandi',
    'Narok', 'Nyamira', 'Nyandarua', 'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River',
    'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu', 'Vihiga', 'Wajir', 'West Pokot',
];

const SECTORS = [
    'ICT', 'Banking and Finance', 'Government', 'Education', 'Health', 'Manufacturing',
    'Telecommunications', 'Agriculture', 'Non-governmental Organization', 'Other',
];

/** Stores a one-time message to show on the next page. */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash_msg'] = ['text' => $message, 'type' => $type];
}

/** Prints and clears the one-time message, if any. */
function show_flash(): void
{
    if (!empty($_SESSION['flash_msg'])) {
        $m = $_SESSION['flash_msg'];
        echo '<div class="alert alert-' . e($m['type']) . ' alert-dismissible fade show" role="alert">'
            . e($m['text'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['flash_msg']);
    }
}

/** Trimmed text from the submitted form. */
function post_str(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

/** Prints a list of validation errors. */
function show_errors(array $errors): void
{
    if ($errors) {
        echo '<div class="alert alert-danger"><ul class="mb-0">';
        foreach ($errors as $err) {
            echo '<li>' . e($err) . '</li>';
        }
        echo '</ul></div>';
    }
}

/** Prints <option> tags for a list, marking the selected value. */
function options(array $items, ?string $selected, string $placeholder = 'Select...'): string
{
    $html = '<option value="">' . e($placeholder) . '</option>';
    foreach ($items as $item) {
        $sel = ($item === $selected) ? ' selected' : '';
        $html .= '<option value="' . e($item) . '"' . $sel . '>' . e($item) . '</option>';
    }
    return $html;
}

/** Bootstrap badge for a status word. */
function status_badge(string $status): string
{
    $colours = [
        'pending' => 'warning text-dark', 'accepted' => 'info text-dark', 'approved' => 'success', 'rejected' => 'danger',
        'open' => 'success', 'closed' => 'secondary', 'active' => 'success', 'inactive' => 'secondary',
        'ongoing' => 'primary', 'completed' => 'dark', 'withdrawn' => 'secondary',
    ];
    return '<span class="badge bg-' . ($colours[$status] ?? 'secondary') . '">' . e(ucfirst($status)) . '</span>';
}

/** Formats a date as e.g. 3 Nov 2026. */
function nice_date(?string $date): string
{
    return $date ? date('j M Y', strtotime($date)) : '—';
}

/** Validates the fields shared by the organization forms. Returns a list of errors. */
function validate_organization(array $o): array
{
    $errors = [];
    if ($o['name'] === '' || mb_strlen($o['name']) > 150) {
        $errors[] = 'Organization name is required (maximum 150 characters).';
    }
    if (!in_array($o['sector'], SECTORS, true)) {
        $errors[] = 'Please choose a sector.';
    }
    if (!in_array($o['county'], COUNTIES, true)) {
        $errors[] = 'Please choose a county.';
    }
    if (mb_strlen($o['address']) > 200) {
        $errors[] = 'Address must be 200 characters or fewer.';
    }
    if (!filter_var($o['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid contact email.';
    }
    if (!preg_match('/^\+?[0-9 ]{9,15}$/', $o['contact_phone'])) {
        $errors[] = 'Please enter a valid phone number (digits only, e.g. 0712345678).';
    }
    return $errors;
}
