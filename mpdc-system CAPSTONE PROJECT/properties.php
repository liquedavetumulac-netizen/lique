<?php
$pageTitle = 'Properties';
require_once 'includes/header.php';
$q = trim($_GET['q'] ?? '');
$sql = "SELECT p.*,b.name barangay,lt.title_number FROM properties p LEFT JOIN barangays b ON b.id=p.barangay_id LEFT JOIN land_titles lt ON lt.property_id=p.id WHERE 1";
$params = [];
$types = '';
if ($q !== '') {
    $sql .= " AND (p.property_code LIKE ? OR p.owner_name LIKE ? OR lt.title_number LIKE ? OR p.lot_number LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}
$sql .= " ORDER BY p.id DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();
?>
<div class="card-panel">
    <div class="panel-head">
        <form class="search-form"><input name="q" value="<?= e($q) ?>" placeholder="Search title, owner, property code, lot..."><button class="btn btn-primary">Search</button></form><a href="property_form.php" class="btn btn-success"><i class="bi bi-plus"></i> Add Property</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Owner</th>
                    <th>Barangay</th>
                    <th>Area</th>
                    <th>Geotag</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $rows->fetch_assoc()): ?><tr>
                        <td><?= e($r['property_code']) ?></td>
                        <td><?= e($r['title_number']) ?></td>
                        <td><?= e($r['owner_name']) ?></td>
                        <td><?= e($r['barangay']) ?></td>
                        <td><?= e($r['recorded_area_sqm']) ?> sqm</td>
                        <td><?= ($r['latitude'] !== null && $r['longitude'] !== null) ? '<span class="badge bg-success">Geotagged</span>' : '<span class="badge bg-secondary">No coordinates</span>' ?></td>
                        <td><?= e($r['verification_status']) ?></td>
                        <td class="actions"><a class="btn btn-sm btn-outline-primary" href="property_view.php?id=<?= $r['id'] ?>">View</a> <a class="btn btn-sm btn-outline-secondary" href="property_form.php?id=<?= $r['id'] ?>">Edit</a> <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this property?')" href="property_delete.php?id=<?= $r['id'] ?>">Delete</a></td>
                    </tr><?php endwhile; ?></tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>