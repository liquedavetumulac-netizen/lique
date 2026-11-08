<?php
$pageTitle = 'Land Titles';
require_once 'includes/header.php';
$q = trim($_GET['q'] ?? '');
$sql = "SELECT lt.*,p.property_code,p.owner_name,p.recorded_area_sqm,b.name barangay FROM land_titles lt JOIN properties p ON p.id=lt.property_id LEFT JOIN barangays b ON b.id=p.barangay_id WHERE 1";
$params = [];
$types = '';
if ($q !== '') {
    $sql .= " AND (lt.title_number LIKE ? OR p.owner_name LIKE ? OR p.property_code LIKE ?)";
    $x = "%$q%";
    $params = [$x, $x, $x];
    $types = 'sss';
}
$sql .= " ORDER BY lt.id DESC";
$s = $conn->prepare($sql);
if ($params) $s->bind_param($types, ...$params);
$s->execute();
$rs = $s->get_result();
?>
<div class="card-panel">
    <div class="panel-head">
        <form class="search-form"><input name="q" value="<?= e($q) ?>" placeholder="Search title number..."><button class="btn btn-primary">Search</button></form><a href="title_form.php" class="btn btn-success">Add Land Title</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Title Number</th>
                    <th>Property</th>
                    <th>Owner</th>
                    <th>Barangay</th>
                    <th>Title Area</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody><?php while ($r = $rs->fetch_assoc()): ?><tr>
                        <td><?= e($r['title_number']) ?></td>
                        <td><?= e($r['property_code']) ?></td>
                        <td><?= e($r['owner_name']) ?></td>
                        <td><?= e($r['barangay']) ?></td>
                        <td><?= e($r['title_area_sqm']) ?> sqm</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="property_view.php?id=<?= $r['property_id'] ?>">View Property</a></td>
                    </tr><?php endwhile; ?></tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>