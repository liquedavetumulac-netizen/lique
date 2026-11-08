<?php
$pageTitle = 'Verification';
require_once 'includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['property_id'];
    $status = $_POST['verification_status'];
    $notes = trim($_POST['field_notes']);
    $date = $_POST['verification_date'] ?: null;
    $a = current_admin_id();
    $s = $conn->prepare("UPDATE properties SET verification_status=?,updated_by=? WHERE id=?");
    $s->bind_param("sii", $status, $a, $id);
    $s->execute();
    $v = $conn->prepare("INSERT INTO verification_records(property_id,verification_status,verification_date,verified_by,field_notes) VALUES(?,?,?,?,?)");
    $v->bind_param("issis", $id, $status, $date, $a, $notes);
    $v->execute();
    flash('success', 'Verification updated.');
    header('Location: verification.php');
    exit;
}
$props = $conn->query("SELECT p.id,p.property_code,p.owner_name,p.verification_status,p.recorded_area_sqm,p.calculated_area_sqm FROM properties p ORDER BY p.id DESC");
?>
<div class="card-panel">
    <h3>Property Verification</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Owner</th>
                    <th>Recorded Area</th>
                    <th>Calculated</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $props->fetch_assoc()): ?><tr>
                        <td><?= e($p['property_code']) ?></td>
                        <td><?= e($p['owner_name']) ?></td>
                        <td><?= $p['recorded_area_sqm'] ?></td>
                        <td><?= $p['calculated_area_sqm'] ?></td>
                        <td><?= e($p['verification_status']) ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2"><input type="hidden" name="property_id" value="<?= $p['id'] ?>"><select name="verification_status" class="form-select form-select-sm"><?php foreach (['Not Yet Verified', 'For Verification', 'Field Verified', 'Verified', 'Needs Correction'] as $v): ?><option <?= $p['verification_status'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select><input type="date" name="verification_date" class="form-control form-control-sm"><input name="field_notes" class="form-control form-control-sm" placeholder="Notes"><button class="btn btn-sm btn-primary">Save</button></form>
                        </td>
                    </tr><?php endwhile; ?></tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>