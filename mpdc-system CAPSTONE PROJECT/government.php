<?php
$pageTitle = 'Government Properties';
require_once 'includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = trim($_POST['property_code']);
  $name = trim($_POST['property_name']);
  $type = trim($_POST['property_type']);
  $area = (float)$_POST['area_sqm'];
  $barangay = (int)$_POST['barangay_id'];
  $lat = $_POST['latitude'] === '' ? null : (float)$_POST['latitude'];
  $lng = $_POST['longitude'] === '' ? null : (float)$_POST['longitude'];
  $s = $conn->prepare("INSERT INTO government_properties(property_code,property_name,property_type,barangay_id,area_sqm,latitude,longitude,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?)");
  $a = current_admin_id();
  $s->bind_param("sssiddsii", $code, $name, $type, $barangay, $area, $lat, $lng, $a, $a); // fallback below if types fail
  if (!$s->execute()) {
    $s->close();
    $s = $conn->prepare("INSERT INTO government_properties(property_code,property_name,property_type,barangay_id,area_sqm,latitude,longitude,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?)");
    $s->bind_param("sssiddsii", $code, $name, $type, $barangay, $area, $lat, $lng, $a, $a);
  }
  if ($s->execute()) {
    flash('success', 'Government property saved.');
    header('Location: government.php');
    exit;
  }
}
$barangays = $conn->query("SELECT id,name FROM barangays ORDER BY name");
$rows = $conn->query("SELECT g.*,b.name barangay FROM government_properties g LEFT JOIN barangays b ON b.id=g.barangay_id ORDER BY g.id DESC");
?>
<div class="card-panel mb-4">
  <h3>Add Government Property</h3>
  <form method="post" class="row g-3">
    <div class="col-md-3"><label>Property Code</label><input class="form-control" name="property_code" required></div>
    <div class="col-md-4"><label>Property Name</label><input class="form-control" name="property_name" required></div>
    <div class="col-md-3"><label>Property Type</label><input class="form-control" name="property_type"></div>
    <div class="col-md-2"><label>Area sqm</label><input class="form-control" name="area_sqm" type="number" step=".01"></div>
    <div class="col-md-3"><label>Barangay</label><select class="form-select" name="barangay_id"><?php while ($b = $barangays->fetch_assoc()): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endwhile; ?></select></div>
    <div class="col-md-3"><label>Latitude</label><input class="form-control" name="latitude"></div>
    <div class="col-md-3"><label>Longitude</label><input class="form-control" name="longitude"></div>
    <div class="col-12"><button class="btn btn-primary">Save</button></div>
  </form>
</div>
<div class="card-panel">
  <h3>Government Property List</h3>
  <table class="table">
    <thead>
      <tr>
        <th>Code</th>
        <th>Name</th>
        <th>Type</th>
        <th>Barangay</th>
        <th>Area</th>
        <th>Coordinates</th>
      </tr>
    </thead>
    <tbody><?php while ($r = $rows->fetch_assoc()): ?><tr>
          <td><?= e($r['property_code']) ?></td>
          <td><?= e($r['property_name']) ?></td>
          <td><?= e($r['property_type']) ?></td>
          <td><?= e($r['barangay']) ?></td>
          <td><?= e($r['area_sqm']) ?></td>
          <td><?= e($r['latitude']) ?>, <?= e($r['longitude']) ?></td>
        </tr><?php endwhile; ?></tbody>
  </table>
</div>
<?php require 'includes/footer.php'; ?>