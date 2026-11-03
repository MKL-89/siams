<?php
/**
 * Document uploads (FR-08). Files are stored in /uploads under random names;
 * the folder is closed to the browser and files are only sent through download.php
 * after an access check.
 */

const UPLOAD_DIR     = __DIR__ . '/../uploads';
const MAX_UPLOAD_MB  = 5;

// Document types a student may upload.
const DOC_TYPES = [
    'acceptance_letter' => 'Acceptance letter',
    'report'            => 'Final attachment report',
    'insurance'         => 'Insurance cover',
    'other'             => 'Other supporting document',
];

/**
 * Checks an uploaded file from $_FILES. Returns null when it is a valid PDF,
 * otherwise the problem to show.
 */
function check_pdf_upload(?array $file): ?string
{
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return 'Please choose a PDF file to upload.';
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        return 'The file is too large. The maximum size is ' . MAX_UPLOAD_MB . ' MB.';
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return 'The upload failed. Please try again.';
    }
    // Check the real content, not just the file name: PDF files start with "%PDF-".
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $head = file_get_contents($file['tmp_name'], false, null, 0, 5);
    if ($mime !== 'application/pdf' || $head !== '%PDF-') {
        return 'Only PDF files can be uploaded.';
    }
    return null;
}

/** Moves a checked upload into storage and returns its stored file name. */
function store_upload(array $file): string
{
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0750, true);
    }
    $name = bin2hex(random_bytes(16)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return $name;
}

/** A safe version of the original file name, for display and download. */
function clean_filename(string $name): string
{
    $name = preg_replace('/[^A-Za-z0-9 ._()-]/', '_', basename($name));
    return mb_substr($name, 0, 150) ?: 'document.pdf';
}

/** True when the logged-in user may open the document. */
function can_view_document(array $doc): bool
{
    $role = current_role();
    $uid  = $_SESSION['user_id'];
    if ($role === 'coordinator' || $role === 'admin') {
        return true;
    }
    if ($role === 'student') {
        $stmt = db()->prepare('SELECT 1 FROM students WHERE student_id = ? AND user_id = ?');
        $stmt->execute([$doc['student_id'], $uid]);
        return (bool) $stmt->fetchColumn();
    }
    if (!in_array($role, ['lecturer', 'industrial'], true) || $doc['placement_id'] === null) {
        return false;
    }
    // Supervisors may open documents of the placements they supervise.
    $sql = $role === 'lecturer'
        ? 'SELECT 1 FROM placements p JOIN lecturers l ON l.lecturer_id = p.lecturer_id WHERE p.placement_id = ? AND l.user_id = ?'
        : 'SELECT 1 FROM placements p JOIN ind_supervisors i ON i.ind_sup_id = p.ind_sup_id WHERE p.placement_id = ? AND i.user_id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$doc['placement_id'], $uid]);
    return (bool) $stmt->fetchColumn();
}

/** Documents of a placement, newest first. */
function placement_documents(int $placementId): array
{
    $stmt = db()->prepare('SELECT * FROM documents WHERE placement_id = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$placementId]);
    return $stmt->fetchAll();
}

/** Prints a small list of document links. */
function document_links(array $docs): void
{
    if (!$docs) {
        echo '<span class="text-muted small">No documents yet</span>';
        return;
    }
    echo '<ul class="list-unstyled small mb-0">';
    foreach ($docs as $d) {
        echo '<li><i class="bi bi-file-earmark-pdf text-danger me-1"></i>'
            . '<a href="' . BASE_URL . '/download.php?id=' . (int) $d['document_id'] . '" target="_blank" rel="noopener">'
            . e(DOC_TYPES[$d['doc_type']] ?? ucfirst($d['doc_type'])) . '</a></li>';
    }
    echo '</ul>';
}
