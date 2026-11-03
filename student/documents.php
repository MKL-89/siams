<?php
/**
 * Student: upload the acceptance letter, final report and other documents as PDF (FR-08).
 * Uploading the same type again replaces the earlier file.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/placements.php';
require_once __DIR__ . '/../includes/documents.php';
require_role('student');

$studentId = my_student_id();

// Documents are linked to the student's current placement, when there is one.
$stmt = db()->prepare(
    "SELECT placement_id FROM placements WHERE student_id = ? AND status IN ('approved','ongoing','completed')
     ORDER BY start_date DESC LIMIT 1"
);
$stmt->execute([$studentId]);
$placementId = $stmt->fetchColumn() ?: null;

$errors = [];
$type = $_POST['doc_type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    } elseif (!array_key_exists($type, DOC_TYPES)) {
        $errors[] = 'Please choose the type of document.';
    } elseif ($type === 'report' && !$placementId) {
        $errors[] = 'You can upload your final report once you have an approved placement.';
    } elseif ($problem = check_pdf_upload($_FILES['document'] ?? null)) {
        $errors[] = $problem;
    }

    if (!$errors) {
        $stored = store_upload($_FILES['document']);
        $pdo = db();
        $pdo->beginTransaction();

        // Replace an earlier upload of the same type (except "other", which can have several).
        $old = [];
        if ($type !== 'other') {
            $q = $pdo->prepare('SELECT document_id, file_path FROM documents WHERE student_id = ? AND doc_type = ?');
            $q->execute([$studentId, $type]);
            $old = $q->fetchAll();
            $pdo->prepare('DELETE FROM documents WHERE student_id = ? AND doc_type = ?')->execute([$studentId, $type]);
        }
        $pdo->prepare('INSERT INTO documents (student_id, placement_id, doc_type, original_name, file_path) VALUES (?, ?, ?, ?, ?)')
            ->execute([$studentId, $placementId, $type, clean_filename($_FILES['document']['name']), $stored]);
        $pdo->commit();

        foreach ($old as $o) {
            @unlink(UPLOAD_DIR . '/' . basename($o['file_path']));
        }
        flash(DOC_TYPES[$type] . ($old ? ' replaced.' : ' uploaded.'));
        redirect('/student/documents.php');
    }
}

$stmt = db()->prepare('SELECT * FROM documents WHERE student_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$studentId]);
$docs = $stmt->fetchAll();

$page_title = 'My documents';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My documents</h1>
<?php show_flash(); ?>
<div class="row g-4">
    <div class="col-lg-5">
        <form method="post" enctype="multipart/form-data" class="card shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="card-body">
                <h2 class="h5 mb-3">Upload a document</h2>
                <?php show_errors($errors); ?>
                <div class="mb-3">
                    <label class="form-label" for="doc_type">Type of document</label>
                    <select class="form-select" id="doc_type" name="doc_type" required>
                        <option value="">Select...</option>
                        <?php foreach (DOC_TYPES as $key => $label): ?>
                            <option value="<?= $key ?>"<?= $type === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="document">PDF file</label>
                    <input type="file" class="form-control" id="document" name="document" accept="application/pdf,.pdf" required>
                    <div class="form-text">PDF only, up to <?= MAX_UPLOAD_MB ?> MB. Uploading the same type again replaces the earlier file.</div>
                </div>
                <button class="btn btn-siams w-100"><i class="bi bi-upload me-1"></i>Upload</button>
            </div>
        </form>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Document</th><th>File</th><th>Uploaded</th></tr></thead>
                    <tbody>
                    <?php if (!$docs): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No documents uploaded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($docs as $d): ?>
                        <tr>
                            <td class="fw-semibold"><?= e(DOC_TYPES[$d['doc_type']] ?? ucfirst($d['doc_type'])) ?></td>
                            <td class="small"><i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                <a href="<?= BASE_URL ?>/download.php?id=<?= (int) $d['document_id'] ?>" target="_blank" rel="noopener"><?= e($d['original_name']) ?></a></td>
                            <td class="small"><?= nice_date($d['uploaded_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
