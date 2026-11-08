<?php
$pageTitle = 'Property Profile';
require_once 'includes/header.php';
$id = (int)($_GET['id'] ?? 0);
$s = $conn->prepare("SELECT p.*,b.name barangay,lt.title_number,lt.title_area_sqm FROM properties p LEFT JOIN barangays b ON b.id=p.barangay_id LEFT JOIN land_titles lt ON lt.property_id=p.id WHERE p.id=?");
$s->bind_param("i", $id);
$s->execute();
$p = $s->get_result()->fetch_assoc();
$s->close();
if (!$p) {
    echo '<div class="alert alert-danger">Property not found.</div>';
    require 'includes/footer.php';
    exit;
}
$markers = $conn->query("SELECT * FROM boundary_markers WHERE property_id=$id ORDER BY point_number");
?>
<div class="panel-head">
    <h3><?= e($p['property_code']) ?></h3>
    <div><a class="btn btn-primary" href="property_form.php?id=<?= $id ?>">Edit</a> <a class="btn btn-outline-primary" href="mapping.php?property_id=<?= $id ?>">View Map</a></div>
</div>
<div class="info-grid">
    <div><span>Title Number</span><strong><?= e($p['title_number']) ?></strong></div>
    <div><span>Owner</span><strong><?= e($p['owner_name']) ?></strong></div>
    <div><span>Barangay</span><strong><?= e($p['barangay']) ?></strong></div>
    <div><span>Recorded Area</span><strong><?= e($p['recorded_area_sqm']) ?> sqm</strong></div>
    <div><span>Calculated Area</span><strong><?= e($p['calculated_area_sqm']) ?> sqm</strong></div>
    <div><span>Geotag Status</span><strong><?= e($p['geotag_status']) ?></strong></div>
    <div><span>Verification</span><strong><?= e($p['verification_status']) ?></strong></div>
    <div><span>Coordinates</span><strong><?= e($p['latitude']) ?>, <?= e($p['longitude']) ?></strong></div>
</div>
<div class="card-panel mt-3">
    <h4>Boundary Markers</h4>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Point</th>
                    <th>Code</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody><?php while ($m = $markers->fetch_assoc()): ?><tr>
                        <td><?= $m['point_number'] ?></td>
                        <td><?= e($m['marker_code']) ?></td>
                        <td><?= $m['latitude'] ?></td>
                        <td><?= $m['longitude'] ?></td>
                        <td><?= e($m['marker_condition']) ?></td>
                    </tr><?php endwhile; ?></tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>