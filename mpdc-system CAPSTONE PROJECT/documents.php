<?php
$pageTitle = 'Documents';
require_once 'includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $pid = (int)$_POST['property_id'];
    $type = trim($_POST['document_type']);
    $f = $_FILES['document'];
    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
    if ($f['error'] !== UPLOAD_ERR_OK || !in_array($f['type'], $allowed, true) || $f['size'] > 10 * 1024 * 1024) flash('danger', 'Invalid file. PDF/JPG/PNG up to 10MB only.');
    else {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $stored = bin2hex(random_bytes(12)) . '.' . $ext;
        $path = 'uploads/documents/' . $stored;
        move_uploaded_file($f['tmp_name'], $path);
        $s = $conn->prepare("INSERT INTO property_documents(property_id,document_type,original_filename,stored_filename,file_path,mime_type,file_size,uploaded_by) VALUES(?,?,?,?,?,?,?,?)");
        $a = current_admin_id();
        $s->bind_param("isssssis", $pid, $type, $f['name'], $stored, $path, $f['type'], $f['size'], $a);
        $s->execute();
        flash('success', 'Document uploaded.');
    }
    header('Location: documents.php');
    exit;
}
$props = $conn->query("SELECT id,property_code FROM properties ORDER BY property_code");
$rows = $conn->query("SELECT d.*,p.property_code FROM property_documents d JOIN properties p ON p.id=d.property_id ORDER BY d.id DESC");
?>
<div class="card-panel mb-4">
    <h3>Upload Property Document</h3>
    <form method="post" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-4"><label>Property</label><select class="form-select" name="property_id"><?php while ($p = $props->fetch_assoc()): ?><option value="<?= $p['id'] ?>"><?= e($p['property_code']) ?></option><?php endwhile; ?></select></div>
        <div class="col-md-3"><label>Document Type</label><input class="form-control" name="document_type" placeholder="Land Title / Survey Plan"></div>
        <div class="col-md-5"><label>File</label><input class="form-control" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></div>
        <div class="col-12"><button class="btn btn-primary">Upload</button></div>
    </form>
</div>
<div class="card-panel">
    <h3>Documents</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Property</th>
                <th>Type</th>
                <th>File</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody><?php while ($r = $rows->fetch_assoc()): ?><tr>
                    <td><?= e($r['property_code']) ?></td>
                    <td><?= e($r['document_type']) ?></td>
                    <td><a href="<?= e($r['file_path']) ?>" target="_blank"><?= e($r['original_filename']) ?></a></td>
                    <td><?= e($r['uploaded_at']) ?></td>
                </tr><?php endwhile; ?></tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>