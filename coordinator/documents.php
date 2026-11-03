<?php
/** Coordinator: all documents uploaded by students, with a filter by type and a search by student. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/documents.php';
require_role('coordinator');

$type = $_GET['type'] ?? '';
$q    = trim($_GET['q'] ?? '');

$sql = 'SELECT d.*, u.full_name, s.reg_no, o.name AS org_name
        FROM documents d
        JOIN students s ON s.student_id = d.student_id
        JOIN users u ON u.user_id = s.user_id
        LEFT JOIN placements p ON p.placement_id = d.placement_id
        LEFT JOIN organizations o ON o.org_id = p.org_id
        WHERE 1 = 1';
$params = [];
if (array_key_exists($type, DOC_TYPES)) {
    $sql .= ' AND d.doc_type = ?';
    $params[] = $type;
}
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR s.reg_no LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
$sql .= ' ORDER BY d.uploaded_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

// Placed students who have not yet uploaded a final report.
$missing = db()->query(
    "SELECT COUNT(*) FROM placements p
     WHERE p.status IN ('approved','ongoing','completed')
       AND NOT EXISTS (SELECT 1 FROM documents d WHERE d.student_id = p.student_id AND d.doc_type = 'report')"
)->fetchColumn();

$page_title = 'Documents';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-1">Student documents</h1>
<p class="text-muted"><?= (int) $missing ?> placed student(s) have not uploaded a final report yet.</p>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-6"><input class="form-control" name="q" placeholder="Search by student name or registration number" value="<?= e($q) ?>"></div>
    <div class="col-md-4">
        <select class="form-select" name="type">
            <option value="">All document types</option>
            <?php foreach (DOC_TYPES as $key => $label): ?>
                <option value="<?= $key ?>"<?= $type === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Organization</th><th>Document</th><th>File</th><th>Uploaded</th></tr></thead>
            <tbody>
            <?php if (!$docs): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No documents found.</td></tr>
            <?php endif; ?>
            <?php foreach ($docs as $d): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($d['full_name']) ?></div><div class="small text-muted"><?= e($d['reg_no']) ?></div></td>
                    <td class="small"><?= e($d['org_name'] ?? '—') ?></td>
                    <td><?= e(DOC_TYPES[$d['doc_type']] ?? ucfirst($d['doc_type'])) ?></td>
                    <td class="small"><i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                        <a href="<?= BASE_URL ?>/download.php?id=<?= (int) $d['document_id'] ?>" target="_blank" rel="noopener"><?= e($d['original_name']) ?></a></td>
                    <td class="small"><?= nice_date($d['uploaded_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
