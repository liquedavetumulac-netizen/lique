<?php
$pageTitle = 'Reports';
require_once 'includes/header.php';
$type = $_GET['type'] ?? 'properties';
$allowed = ['properties', 'titles', 'government', 'verification'];
if (!in_array($type, $allowed, true)) $type = 'properties';
if ($type === 'titles') $rs = $conn->query("SELECT lt.title_number,p.property_code,p.owner_name,lt.title_area_sqm,b.name barangay FROM land_titles lt JOIN properties p ON p.id=lt.property_id LEFT JOIN barangays b ON b.id=p.barangay_id ORDER BY lt.title_number");
elseif ($type === 'government') $rs = $conn->query("SELECT g.property_code,g.property_name,g.property_type,g.area_sqm,b.name barangay FROM government_properties g LEFT JOIN barangays b ON b.id=g.barangay_id ORDER BY g.property_code");
elseif ($type === 'verification') $rs = $conn->query("SELECT p.property_code,p.owner_name,p.verification_status,p.geotag_status,p.recorded_area_sqm,p.calculated_area_sqm FROM properties p ORDER BY p.verification_status,p.property_code");
else $rs = $conn->query("SELECT p.property_code,lt.title_number,p.owner_name,b.name barangay,p.recorded_area_sqm,p.verification_status,p.geotag_status FROM properties p LEFT JOIN land_titles lt ON lt.property_id=p.id LEFT JOIN barangays b ON b.id=p.barangay_id ORDER BY p.property_code");
?>
<div class="card-panel">
    <div class="panel-head">
        <h3><?= ucfirst($type) ?> Report</h3><button onclick="window.print()" class="btn btn-outline-primary">Print</button>
    </div>
    <div class="report-links"><a href="?type=properties">Properties</a><a href="?type=titles">Titles</a><a href="?type=government">Government</a><a href="?type=verification">Verification</a></div>
    <div class="table-responsive">
        <table class="table">
            <thead><?php if ($type === 'properties'): ?><tr>
                        <th>Property</th>
                        <th>Title</th>
                        <th>Owner</th>
                        <th>Barangay</th>
                        <th>Area</th>
                        <th>Verification</th>
                        <th>Geotag</th>
                    </tr><?php elseif ($type === 'titles'): ?><tr>
                        <th>Title</th>
                        <th>Property</th>
                        <th>Owner</th>
                        <th>Barangay</th>
                        <th>Area</th>
                    </tr><?php elseif ($type === 'government'): ?><tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Barangay</th>
                        <th>Area</th>
                    </tr><?php else: ?><tr>
                        <th>Property</th>
                        <th>Owner</th>
                        <th>Verification</th>
                        <th>Geotag</th>
                        <th>Recorded</th>
                        <th>Calculated</th>
                    </tr><?php endif; ?></thead>
            <tbody>
                <?php while ($r = $rs->fetch_assoc()): ?><tr><?php foreach ($r as $v): ?><td><?= e($v) ?></td><?php endforeach; ?></tr><?php endwhile; ?></tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>