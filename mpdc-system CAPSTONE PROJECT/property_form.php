<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$error = '';
$p = ['property_code' => '', 'owner_name' => '', 'property_type' => 'Private', 'barangay_id' => '', 'lot_number' => '', 'block_number' => '', 'recorded_area_sqm' => '', 'technical_description' => '', 'survey_plan_number' => '', 'tax_declaration_number' => '', 'date_acquired' => '', 'property_status' => 'Active', 'verification_status' => 'Not Yet Verified', 'latitude' => '', 'longitude' => '', 'coordinate_reference' => '', 'coordinate_source' => '', 'remarks' => ''];
if ($editing) {
  $s = $conn->prepare("SELECT * FROM properties WHERE id=?");
  $s->bind_param("i", $id);
  $s->execute();
  $p = $s->get_result()->fetch_assoc();
  $s->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fields = ['property_code', 'owner_name', 'property_type', 'barangay_id', 'lot_number', 'block_number', 'recorded_area_sqm', 'technical_description', 'survey_plan_number', 'tax_declaration_number', 'date_acquired', 'property_status', 'verification_status', 'latitude', 'longitude', 'coordinate_reference', 'coordinate_source', 'remarks'];
  foreach ($fields as $f) $p[$f] = trim($_POST[$f] ?? '');
  if ($p['property_code'] === '' || $p['recorded_area_sqm'] === '' || $p['barangay_id'] === '') $error = 'Property code, barangay and recorded area are required.';
  elseif ($p['latitude'] !== '' && ($p['latitude'] < -90 || $p['latitude'] > 90)) $error = 'Invalid latitude.';
  elseif ($p['longitude'] !== '' && ($p['longitude'] < -180 || $p['longitude'] > 180)) $error = 'Invalid longitude.';
  else {
    if ($editing) {
      $sql = "UPDATE properties SET property_code=?,owner_name=?,property_type=?,barangay_id=?,lot_number=?,block_number=?,recorded_area_sqm=?,technical_description=?,survey_plan_number=?,tax_declaration_number=?,date_acquired=?,property_status=?,verification_status=?,latitude=?,longitude=?,coordinate_reference=?,coordinate_source=?,remarks=?,updated_by=? WHERE id=?";
      $s = $conn->prepare($sql);
      $admin = current_admin_id();
      $s->bind_param("sssissdssssssddssssi", $p['property_code'], $p['owner_name'], $p['property_type'], $p['barangay_id'], $p['lot_number'], $p['block_number'], $p['recorded_area_sqm'], $p['technical_description'], $p['survey_plan_number'], $p['tax_declaration_number'], $p['date_acquired'], $p['property_status'], $p['verification_status'], $p['latitude'], $p['longitude'], $p['coordinate_reference'], $p['coordinate_source'], $p['remarks'], $admin, $id);
    } else {
      $sql = "INSERT INTO properties(property_code,owner_name,property_type,barangay_id,lot_number,block_number,recorded_area_sqm,technical_description,survey_plan_number,tax_declaration_number,date_acquired,property_status,verification_status,latitude,longitude,coordinate_reference,coordinate_source,remarks,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      $s = $conn->prepare($sql);
      $admin = current_admin_id();
      $s->bind_param("sssissdssssssddssssii", $p['property_code'], $p['owner_name'], $p['property_type'], $p['barangay_id'], $p['lot_number'], $p['block_number'], $p['recorded_area_sqm'], $p['technical_description'], $p['survey_plan_number'], $p['tax_declaration_number'], $p['date_acquired'], $p['property_status'], $p['verification_status'], $p['latitude'], $p['longitude'], $p['coordinate_reference'], $p['coordinate_source'], $p['remarks'], $admin, $admin);
    }
    if ($s->execute()) {
      $newid = $editing ? $id : $s->insert_id;
      $s->close();
      log_activity($conn, current_admin_id(), $editing ? 'UPDATE' : 'CREATE', 'properties', $newid, 'Property record saved.');
      header("Location: property_view.php?id=$newid");
      exit;
    }
    $error = 'Could not save property. Check for duplicate property code.';
  }
}
$barangays = $conn->query("SELECT id,name FROM barangays WHERE is_active=1 ORDER BY name");
$pageTitle = $editing ? 'Edit Property' : 'Add Property';
require 'includes/header.php';
?>
<div class="card-panel">
  <h3><?= e($pageTitle) ?></h3><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="row g-3">
    <?php
    $inputs = [
      'property_code' => 'Property Code',
      'owner_name' => 'Owner Name',
      'property_type' => 'Property Type',
      'lot_number' => 'Lot Number',
      'block_number' => 'Block Number',
      'recorded_area_sqm' => 'Recorded Area (sqm)',
      'survey_plan_number' => 'Survey Plan Number',
      'tax_declaration_number' => 'Tax Declaration Number',
      'date_acquired' => 'Date Acquired',
      'latitude' => 'Latitude',
      'longitude' => 'Longitude',
      'coordinate_reference' => 'Coordinate Reference',
      'coordinate_source' => 'Coordinate Source'
    ];
    foreach ($inputs as $name => $label): ?><div class="col-md-4"><label><?= e($label) ?></label><input class="form-control" name="<?= e($name) ?>" type="<?= ($name === 'date_acquired' ? 'date' : 'text') ?>" value="<?= e($p[$name]) ?>" <?= ($name === 'property_code' || $name === 'recorded_area_sqm') ? 'required' : '' ?>></div><?php endforeach; ?>
    <div class="col-md-4"><label>Barangay</label><select class="form-select" name="barangay_id" required>
        <option value="">Select</option><?php while ($b = $barangays->fetch_assoc()): ?><option value="<?= $b['id'] ?>" <?= $p['barangay_id'] == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endwhile; ?>
      </select></div>
    <div class="col-md-4"><label>Property Status</label><select class="form-select" name="property_status">
        <option>Active</option>
        <option>Inactive</option>
        <option>Disposed</option>
      </select></div>
    <div class="col-md-4"><label>Verification Status</label><select class="form-select" name="verification_status"><?php foreach (['Not Yet Verified', 'For Verification', 'Field Verified', 'Verified', 'Needs Correction'] as $v): ?><option <?= $p['verification_status'] === $v ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label>Technical Description</label><textarea class="form-control" name="technical_description" rows="3"><?= e($p['technical_description']) ?></textarea></div>
    <div class="col-12"><label>Remarks</label><textarea class="form-control" name="remarks" rows="3"><?= e($p['remarks']) ?></textarea></div>
    <div class="col-12"><button class="btn btn-primary">Save Property</button> <a href="properties.php" class="btn btn-secondary">Cancel</a></div>
  </form>
</div>
<?php require 'includes/footer.php'; ?>