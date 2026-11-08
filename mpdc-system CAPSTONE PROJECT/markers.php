<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$error = '';
$bearingRows = [];
$bearingValues = [
    'property_id' => '',
    'start_latitude' => '',
    'start_longitude' => ''
];
$manualValues = [
    'property_id' => '',
    'point_number' => '',
    'marker_code' => '',
    'latitude' => '',
    'longitude' => '',
    'marker_condition' => '',
    'description' => ''
];

$destination = static function (float $latitude, float $longitude, float $bearing, float $distance): array {
    $earthRadius = 6378137.0;
    $angularDistance = $distance / $earthRadius;
    $bearingRadians = deg2rad($bearing);
    $latitudeRadians = deg2rad($latitude);
    $longitudeRadians = deg2rad($longitude);

    $destinationLatitude = asin(
        sin($latitudeRadians) * cos($angularDistance)
        + cos($latitudeRadians) * sin($angularDistance) * cos($bearingRadians)
    );
    $destinationLongitude = $longitudeRadians + atan2(
        sin($bearingRadians) * sin($angularDistance) * cos($latitudeRadians),
        cos($angularDistance) - sin($latitudeRadians) * sin($destinationLatitude)
    );

    return [
        rad2deg($destinationLatitude),
        fmod(rad2deg($destinationLongitude) + 540.0, 360.0) - 180.0
    ];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['entry_mode'] ?? 'manual';

    if ($mode === 'bearing') {
        $bearingValues['property_id'] = trim($_POST['property_id'] ?? '');
        $bearingValues['start_latitude'] = trim($_POST['start_latitude'] ?? '');
        $bearingValues['start_longitude'] = trim($_POST['start_longitude'] ?? '');
        $northSouth = is_array($_POST['bearing_ns'] ?? null) ? $_POST['bearing_ns'] : [];
        $degrees = is_array($_POST['bearing_degrees'] ?? null) ? $_POST['bearing_degrees'] : [];
        $minutes = is_array($_POST['bearing_minutes'] ?? null) ? $_POST['bearing_minutes'] : [];
        $seconds = is_array($_POST['bearing_seconds'] ?? null) ? $_POST['bearing_seconds'] : [];
        $eastWest = is_array($_POST['bearing_ew'] ?? null) ? $_POST['bearing_ew'] : [];
        $distances = is_array($_POST['distance_m'] ?? null) ? $_POST['distance_m'] : [];
        $rowCount = count($degrees);

        for ($i = 0; $i < $rowCount; $i++) {
            $bearingRows[] = [
                'ns' => $northSouth[$i] ?? 'N',
                'degrees' => trim($degrees[$i] ?? ''),
                'minutes' => trim($minutes[$i] ?? '0'),
                'seconds' => trim($seconds[$i] ?? '0'),
                'ew' => $eastWest[$i] ?? 'E',
                'distance' => trim($distances[$i] ?? '')
            ];
        }

        $propertyId = filter_var($bearingValues['property_id'], FILTER_VALIDATE_INT);
        $startLatitude = filter_var($bearingValues['start_latitude'], FILTER_VALIDATE_FLOAT);
        $startLongitude = filter_var($bearingValues['start_longitude'], FILTER_VALIDATE_FLOAT);

        if (!$propertyId || $startLatitude === false || $startLongitude === false
            || !is_finite((float)$startLatitude) || !is_finite((float)$startLongitude)
            || $startLatitude < -90 || $startLatitude > 90
            || $startLongitude < -180 || $startLongitude > 180) {
            $error = 'Pumili ng property at maglagay ng valid na starting latitude at longitude.';
        } elseif ($rowCount < 3) {
            $error = 'Kailangan ng hindi bababa sa tatlong bearing at distance para makabuo ng boundary.';
        } elseif (count($northSouth) !== $rowCount || count($minutes) !== $rowCount
            || count($seconds) !== $rowCount || count($eastWest) !== $rowCount
            || count($distances) !== $rowCount) {
            $error = 'Hindi kumpleto ang mga bearing at distance row. Pakisuri at subukan muli.';
        } else {
            $legs = [];
            foreach ($bearingRows as $index => $row) {
                $degree = filter_var($row['degrees'], FILTER_VALIDATE_INT);
                $minute = filter_var($row['minutes'], FILTER_VALIDATE_INT);
                $second = filter_var($row['seconds'], FILTER_VALIDATE_FLOAT);
                $distance = filter_var($row['distance'], FILTER_VALIDATE_FLOAT);

                if (!in_array($row['ns'], ['N', 'S'], true)
                    || !in_array($row['ew'], ['E', 'W'], true)
                    || $degree === false || $degree < 0 || $degree > 90
                    || $minute === false || $minute < 0 || $minute > 59
                    || $second === false || $second < 0 || $second >= 60
                    || ($degree === 90 && ($minute !== 0 || $second != 0.0))
                    || $distance === false || !is_finite((float)$distance) || $distance <= 0) {
                    $error = 'Suriin ang bearing at distance sa Corner ' . ($index + 1) . '. Gumamit ng bearing na 0–90 degrees at positibong distance.';
                    break;
                }

                $angle = $degree + ($minute / 60) + ($second / 3600);
                $azimuth = match ($row['ns'] . $row['ew']) {
                    'NE' => $angle,
                    'SE' => 180 - $angle,
                    'SW' => 180 + $angle,
                    'NW' => 360 - $angle
                };
                $legs[] = [
                    'azimuth' => $azimuth,
                    'distance' => $distance,
                    'description' => sprintf(
                        'Bearing: %s %d° %02d\' %05.2f" %s; distance: %.2f m',
                        $row['ns'],
                        $degree,
                        $minute,
                        $second,
                        $row['ew'],
                        $distance
                    )
                ];
            }

            $computedPoints = [];
            if ($error === '') {
                $latitude = (float)$startLatitude;
                $longitude = (float)$startLongitude;
                foreach ($legs as $leg) {
                    [$latitude, $longitude] = $destination($latitude, $longitude, $leg['azimuth'], $leg['distance']);
                    $computedPoints[] = [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'description' => $leg['description']
                    ];
                }

                $lastPoint = $computedPoints[count($computedPoints) - 1];
                $lat1 = deg2rad((float)$startLatitude);
                $lat2 = deg2rad($lastPoint['latitude']);
                $latDifference = $lat2 - $lat1;
                $lngDifference = deg2rad($lastPoint['longitude'] - (float)$startLongitude);
                $a = sin($latDifference / 2) ** 2
                    + cos($lat1) * cos($lat2) * sin($lngDifference / 2) ** 2;
                $closingErrorMeters = 6378137.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));

                if ($closingErrorMeters <= 1.0) {
                    array_pop($computedPoints);
                    if (count($computedPoints) < 3) {
                        $error = 'Kulang ang corners: kailangan ng hindi bababa sa tatlong boundary corners bago ang closing line.';
                    } else {
                        $lastIndex = count($computedPoints) - 1;
                        $computedPoints[$lastIndex]['description'] .= '; closing leg: ' . $lastPoint['description'];
                    }
                }
            }

            if ($error === '') {
                $adminId = current_admin_id();
                $conn->begin_transaction();
                try {
                    $propertyStmt = $conn->prepare('SELECT property_code FROM properties WHERE id = ? FOR UPDATE');
                    $propertyStmt->bind_param('i', $propertyId);
                    $propertyStmt->execute();
                    $property = $propertyStmt->get_result()->fetch_assoc();
                    $propertyStmt->close();

                    if (!$property) {
                        throw new RuntimeException('The selected property does not exist.');
                    }

                    $pointStmt = $conn->prepare('SELECT COALESCE(MAX(point_number), 0) AS last_point FROM boundary_markers WHERE property_id = ?');
                    $pointStmt->bind_param('i', $propertyId);
                    $pointStmt->execute();
                    $nextPoint = (int)$pointStmt->get_result()->fetch_assoc()['last_point'] + 1;
                    $pointStmt->close();

                    $insert = $conn->prepare(
                        'INSERT INTO boundary_markers
                            (property_id, marker_code, point_number, latitude, longitude, coordinate_reference, coordinate_source, description, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $coordinateReference = 'WGS84';
                    $coordinateSource = 'Calculated from title bearing and distance';

                    foreach ($computedPoints as $index => $point) {
                        $pointNumber = $nextPoint + $index;
                        $safeCode = substr((string)preg_replace('/[^A-Za-z0-9_-]/', '-', $property['property_code']), 0, 75);
                        $markerCode = $safeCode . '-P' . $pointNumber;
                        $latitude = $point['latitude'];
                        $longitude = $point['longitude'];
                        $description = $point['description'];
                        $insert->bind_param(
                            'isiddsssi',
                            $propertyId,
                            $markerCode,
                            $pointNumber,
                            $latitude,
                            $longitude,
                            $coordinateReference,
                            $coordinateSource,
                            $description,
                            $adminId
                        );
                        $insert->execute();
                    }
                    $insert->close();
                    log_activity($conn, $adminId, 'CREATE', 'boundary_markers', null, count($computedPoints) . ' boundary corners plotted from title bearings.');
                    $conn->commit();

                    flash('success', count($computedPoints) . ' boundary corners plotted and saved.');
                    header('Location: markers.php');
                    exit;
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    error_log($exception->getMessage());
                    $error = $exception->getCode() === 1062
                        ? 'May kaparehong marker code o point number na. I-refresh ang form at subukan muli.'
                        : 'Hindi na-save ang mga corner dahil nagkaroon ng problema sa database.';
                } catch (RuntimeException $exception) {
                    $conn->rollback();
                    $error = 'Hindi makita ang napiling property. Pumili ng property at subukan muli.';
                }
            }
        }
    } else {
        foreach ($manualValues as $field => $value) {
            $manualValues[$field] = trim($_POST[$field] ?? $value);
        }

        $propertyId = filter_var($manualValues['property_id'], FILTER_VALIDATE_INT);
        $pointNumber = filter_var($manualValues['point_number'], FILTER_VALIDATE_INT);
        $latitude = filter_var($manualValues['latitude'], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($manualValues['longitude'], FILTER_VALIDATE_FLOAT);

        if (!$propertyId || !$pointNumber || $pointNumber < 1 || $manualValues['marker_code'] === ''
            || $latitude === false || $longitude === false
            || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $error = 'Maglagay ng property, point number, marker code, at valid na coordinates.';
        } else {
            $insert = $conn->prepare(
                'INSERT INTO boundary_markers
                    (property_id, marker_code, point_number, latitude, longitude, description, marker_condition, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $adminId = current_admin_id();
            $insert->bind_param(
                'isiddssi',
                $propertyId,
                $manualValues['marker_code'],
                $pointNumber,
                $latitude,
                $longitude,
                $manualValues['description'],
                $manualValues['marker_condition'],
                $adminId
            );

            try {
                $insert->execute();
                log_activity($conn, $adminId, 'CREATE', 'boundary_markers', $insert->insert_id, 'Boundary marker added.');
                $insert->close();
                flash('success', 'Boundary marker added.');
                header('Location: markers.php');
                exit;
            } catch (mysqli_sql_exception $exception) {
                error_log($exception->getMessage());
                $error = $exception->getCode() === 1062
                    ? 'May kaparehong marker code o point number na para sa property.'
                    : 'Hindi na-save ang marker dahil nagkaroon ng problema sa database.';
                $insert->close();
            }
        }
    }
}

$props = $conn->query(
    'SELECT p.id, p.property_code, lt.title_number
     FROM properties p
     LEFT JOIN land_titles lt ON lt.property_id = p.id
     ORDER BY p.property_code'
);
$properties = $props->fetch_all(MYSQLI_ASSOC);
$rows = $conn->query(
    'SELECT bm.*, p.property_code
     FROM boundary_markers bm
     JOIN properties p ON p.id = bm.property_id
     ORDER BY bm.property_id, bm.point_number'
);
$pageTitle = 'Boundary Markers';
require 'includes/header.php';
?>
<div class="card-panel mb-4">
    <h3>Plot Boundary from Title Bearings</h3>
    <p class="text-muted">Maglagay ng starting coordinate at isang bearing/distance para sa bawat segment. Awtomatikong iko-compute at ise-save ang coordinates ng bawat corner. Kung ang huling segment ay bumabalik sa starting point (within 1 m), ituturing ito bilang closing line at hindi isasama bilang dagdag na corner.</p>
    <div class="alert alert-warning">
        Kailangan ang kilala at tamang WGS84 coordinate ng starting tie point. Ang computed plot ay gabay lamang at hindi kapalit ng survey o beripikasyon ng lisensyadong Geodetic Engineer.
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="row g-3" id="bearingForm">
        <input type="hidden" name="entry_mode" value="bearing">
        <div class="col-md-4">
            <label class="form-label">Property</label>
            <select class="form-select" name="property_id" required>
                <option value="">Select property</option>
                <?php foreach ($properties as $property): ?>
                    <option value="<?= (int)$property['id'] ?>" <?= $bearingValues['property_id'] == $property['id'] ? 'selected' : '' ?>><?= e($property['property_code']) ?><?= $property['title_number'] ? ' - Title ' . e($property['title_number']) : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Starting Latitude</label>
            <input class="form-control" type="number" name="start_latitude" min="-90" max="90" step="any" value="<?= e($bearingValues['start_latitude']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Starting Longitude</label>
            <input class="form-control" type="number" name="start_longitude" min="-180" max="180" step="any" value="<?= e($bearingValues['start_longitude']) ?>" required>
        </div>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr><th>Segment</th><th>N/S</th><th>Degrees</th><th>Minutes</th><th>Seconds</th><th>E/W</th><th>Distance (m)</th><th></th></tr></thead>
                    <tbody id="bearingRows">
                        <?php foreach ($bearingRows ?: array_fill(0, 3, null) as $index => $row): ?>
                            <tr class="bearing-row">
                                <td class="segment-label">Starting Point to Corner <?= $index + 1 ?></td>
                                <td><select class="form-select" name="bearing_ns[]"><option value="N" <?= ($row['ns'] ?? 'N') === 'N' ? 'selected' : '' ?>>N</option><option value="S" <?= ($row['ns'] ?? '') === 'S' ? 'selected' : '' ?>>S</option></select></td>
                                <td><input class="form-control" type="number" name="bearing_degrees[]" min="0" max="90" value="<?= e($row['degrees'] ?? '0') ?>" required></td>
                                <td><input class="form-control" type="number" name="bearing_minutes[]" min="0" max="59" value="<?= e($row['minutes'] ?? '0') ?>" required></td>
                                <td><input class="form-control" type="number" name="bearing_seconds[]" min="0" max="59.99" step="0.01" value="<?= e($row['seconds'] ?? '0') ?>" required></td>
                                <td><select class="form-select" name="bearing_ew[]"><option value="E" <?= ($row['ew'] ?? 'E') === 'E' ? 'selected' : '' ?>>E</option><option value="W" <?= ($row['ew'] ?? '') === 'W' ? 'selected' : '' ?>>W</option></select></td>
                                <td><input class="form-control" type="number" name="distance_m[]" min="0.01" step="any" value="<?= e($row['distance'] ?? '') ?>" required></td>
                                <td><button class="btn btn-outline-danger remove-bearing" type="button" aria-label="Remove corner">&times;</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-outline-primary" id="addBearing" type="button"><i class="bi bi-plus-lg"></i> Add Corner</button>
        </div>
        <div class="col-12"><button class="btn btn-primary">Plot and Save Boundary</button></div>
    </form>
</div>

<div class="card-panel mb-4">
    <h3>Add One GPS Marker</h3>
    <p class="text-muted">Gamitin ito kung mayroon ka nang aktuwal na latitude at longitude para sa isang marker.</p>
    <form method="post" class="row g-3">
        <input type="hidden" name="entry_mode" value="manual">
        <div class="col-md-3"><label class="form-label">Property</label><select class="form-select" name="property_id" required><option value="">Select property</option><?php foreach ($properties as $property): ?><option value="<?= (int)$property['id'] ?>" <?= $manualValues['property_id'] == $property['id'] ? 'selected' : '' ?>><?= e($property['property_code']) ?><?= $property['title_number'] ? ' - Title ' . e($property['title_number']) : '' ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label">Point #</label><input class="form-control" type="number" name="point_number" min="1" value="<?= e($manualValues['point_number']) ?>" required></div>
        <div class="col-md-2"><label class="form-label">Marker Code</label><input class="form-control" name="marker_code" value="<?= e($manualValues['marker_code']) ?>" required></div>
        <div class="col-md-2"><label class="form-label">Latitude</label><input class="form-control" type="number" name="latitude" min="-90" max="90" step="any" value="<?= e($manualValues['latitude']) ?>" required></div>
        <div class="col-md-2"><label class="form-label">Longitude</label><input class="form-control" type="number" name="longitude" min="-180" max="180" step="any" value="<?= e($manualValues['longitude']) ?>" required></div>
        <div class="col-md-3"><label class="form-label">Condition</label><input class="form-control" name="marker_condition" value="<?= e($manualValues['marker_condition']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Description</label><input class="form-control" name="description" value="<?= e($manualValues['description']) ?>"></div>
        <div class="col-12"><button class="btn btn-outline-primary">Save GPS Marker</button></div>
    </form>
</div>

<div class="card-panel">
    <h3>Markers</h3>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Property</th><th>Point</th><th>Code</th><th>Latitude</th><th>Longitude</th><th>Bearing / Description</th><th>Condition</th></tr></thead>
            <tbody>
                <?php while ($marker = $rows->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($marker['property_code']) ?></td>
                        <td><?= (int)$marker['point_number'] ?></td>
                        <td><?= e($marker['marker_code']) ?></td>
                        <td><?= e($marker['latitude']) ?></td>
                        <td><?= e($marker['longitude']) ?></td>
                        <td><?= e($marker['description']) ?></td>
                        <td><?= e($marker['marker_condition']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.getElementById('addBearing').addEventListener('click', function () {
    const body = document.getElementById('bearingRows');
    const row = body.querySelector('.bearing-row').cloneNode(true);
    row.querySelectorAll('input').forEach(input => input.value = input.name === 'bearing_degrees[]' || input.name === 'bearing_minutes[]' || input.name === 'bearing_seconds[]' ? '0' : '');
    row.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    body.appendChild(row);
    updateBearingRows();
});

document.getElementById('bearingRows').addEventListener('click', function (event) {
    if (event.target.closest('.remove-bearing') && this.querySelectorAll('.bearing-row').length > 3) {
        event.target.closest('.bearing-row').remove();
        updateBearingRows();
    }
});

function updateBearingRows() {
    document.querySelectorAll('#bearingRows .bearing-row').forEach((row, index) => {
        row.querySelector('.segment-label').textContent = 'Starting Point to Corner ' + (index + 1);
        row.querySelector('.remove-bearing').disabled = document.querySelectorAll('#bearingRows .bearing-row').length <= 3;
    });
}
updateBearingRows();
</script>
<?php require 'includes/footer.php'; ?>
