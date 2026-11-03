<?php
/** Sends an uploaded document to a user who is allowed to see it. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/documents.php';
require_login();

$stmt = db()->prepare('SELECT * FROM documents WHERE document_id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$doc = $stmt->fetch();

if (!$doc || !can_view_document($doc)) {
    http_response_code(404);   // the same answer whether it does not exist or is not allowed
    exit('Document not found.');
}

$path = UPLOAD_DIR . '/' . basename($doc['file_path']);
if (!is_file($path)) {
    http_response_code(404);
    exit('Document not found.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . clean_filename($doc['original_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
