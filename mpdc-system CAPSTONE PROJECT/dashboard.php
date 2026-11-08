<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$counts = [];
foreach (
    [
        'properties' => 'Total Properties',
        'land_titles' => 'Land Titles',
        'government_properties' => 'Government Properties',
        'boundary_markers' => 'Boundary Markers',
        'property_documents' => 'Documents',
        'verification_records' => 'Verification Records'
    ] as $table => $label
) {
    $r = $conn->query("SELECT COUNT(*) c FROM `$table`")->fetch_assoc();
    $counts[$label] = (int)$r['c'];
}
$verified = (int)$conn->query("SELECT COUNT(*) c FROM properties WHERE verification_status='Verified'")->fetch_assoc()['c'];
$geotagged = (int)$conn->query("SELECT COUNT(*) c FROM properties WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetch_assoc()['c'];
$recent = $conn->query("SELECT p.id,p.property_code,p.owner_name,p.verification_status,p.created_at,b.name barangay
 FROM properties p LEFT JOIN barangays b ON b.id=p.barangay_id ORDER BY p.created_at DESC LIMIT 8");
$hasProperties = $counts['Total Properties'] > 0;
?>
<div class="card-panel mb-4">
    <div class="panel-head">
        <div>
            <h3 class="mb-1">Record a New Entry</h3>
            <p class="text-muted mb-0">Pumili ng uri ng record na idaragdag sa system.</p>
        </div>
        <a class="btn btn-primary" href="property_form.php"><i class="bi bi-plus-lg"></i> Record Property</a>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="title_form.php"><i class="bi bi-file-earmark-plus"></i> Add Land Title</a>
        <a class="btn btn-outline-primary" href="government.php"><i class="bi bi-bank"></i> Add Government Property</a>
        <a class="btn btn-outline-primary" href="markers.php"><i class="bi bi-geo"></i> Add Boundary Marker</a>
    </div>
</div>
<div class="stats-grid">
    <?php foreach ($counts as $label => $value): ?><div class="stat-card"><span><?= e($label) ?></span><strong><?= $value ?></strong></div><?php endforeach; ?>
    <div class="stat-card success"><span>Geotagged</span><strong><?= $geotagged ?></strong></div>
    <div class="stat-card success"><span>Verified</span><strong><?= $verified ?></strong></div>
</div>
<div class="card-panel mt-4">
    <div class="panel-head">
        <h3>Recent Properties</h3><a class="btn btn-sm btn-primary" href="properties.php">Manage Properties</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Property Code</th>
                    <th>Owner</th>
                    <th>Barangay</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$hasProperties): ?>
                    <tr><td colspan="6" class="text-center py-4">
                        Wala pang property record. I-click ang <strong>Record Property</strong> para magsimula.
                    </td></tr>
                <?php else: while ($r = $recent->fetch_assoc()): ?><tr>
                        <td><?= e($r['property_code']) ?></td>
                        <td><?= e($r['owner_name']) ?></td>
                        <td><?= e($r['barangay']) ?></td>
                        <td><span class="badge bg-light text-dark"><?= e($r['verification_status']) ?></span></td>
                        <td><?= e($r['created_at']) ?></td>
                        <td><a href="property_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>