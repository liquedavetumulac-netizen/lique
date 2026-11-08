<?php
$pageTitle = 'MPDC Assistant';
require_once 'includes/header.php';

$message = '';
$response = 'Kumusta! Ako ang MPDC Assistant. Maaari akong tumulong maghanap ng property record o magpaliwanag kung paano gamitin ang system. Halimbawa: “Sino ang may-ari ng property ABC-123?”';
$related = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $normalized = strtolower($message);
    $response = 'Pakisulat muna ang tanong mo para matulungan kita.';

    if ($message !== '') {
        $hasAny = static function (string $text, array $terms): bool {
            foreach ($terms as $term) {
                if (strpos($text, $term) !== false) {
                    return true;
                }
            }
            return false;
        };

        $identifier = null;
        if (preg_match('/\b(?:title(?:\s+(?:number|no\.?))?|tct|otc|property(?:\s+code)?|code|lot(?:\s+(?:number|no\.?))?)\s*[:#-]?\s*([a-z0-9][a-z0-9\/-]*)/i', $message, $matches)) {
            $identifier = $matches[1];
        } elseif (preg_match('/\b([a-z0-9][a-z0-9\/-]*[-\/][a-z0-9][a-z0-9\/-]*)\b/i', $message, $matches)) {
            $identifier = $matches[1];
        } elseif (preg_match('/\b(?:may-ari|may ari|owner)\s+(?:(?:ng|ni|si|na si|name is|is)\s+)?([a-z][a-z .\'-]{1,80})/i', $message, $matches)) {
            $identifier = trim($matches[1], " \t\n\r\0\x0B.,!?");
        }

        if (preg_match('/^(?:hello|hi|hey|kumusta|magandang umaga|magandang hapon|magandang gabi)[!,. ]*$/i', $message)) {
            $response = 'Kumusta! Ano ang maitutulong ko? Maaari kang magtanong tungkol sa property, titulo, may-ari, lokasyon, verification, o kung paano gamitin ang system.';
        } elseif ($identifier !== null) {
            $like = '%' . $identifier . '%';
            $stmt = $conn->prepare(
                "SELECT p.id, p.property_code, p.owner_name, p.lot_number, p.recorded_area_sqm,
                        p.latitude, p.longitude, p.verification_status, p.geotag_status,
                        p.property_status, p.municipality, p.province, b.name AS barangay,
                        lt.title_number
                 FROM properties p
                 LEFT JOIN land_titles lt ON lt.property_id = p.id
                 LEFT JOIN barangays b ON b.id = p.barangay_id
                 WHERE p.property_code LIKE ? OR p.owner_name LIKE ?
                    OR p.lot_number LIKE ? OR lt.title_number LIKE ?
                 LIMIT 2"
            );
            $stmt->bind_param('ssss', $like, $like, $like, $like);
            $stmt->execute();
            $result = $stmt->get_result();
            $propertyRows = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (count($propertyRows) > 1) {
                $response = 'Maraming record ang tumugma sa “' . $identifier . '”. Pakibigay ang buong property code o title number para matukoy ko ang tamang record.';
            } elseif (count($propertyRows) === 1) {
                $property = $propertyRows[0];
                $related = (int)$property['id'];
                $markerStmt = $conn->prepare('SELECT COUNT(*) AS total FROM boundary_markers WHERE property_id = ?');
                $markerStmt->bind_param('i', $related);
                $markerStmt->execute();
                $markerCount = (int)$markerStmt->get_result()->fetch_assoc()['total'];
                $markerStmt->close();

                if ($hasAny($normalized, ['sino', 'may-ari', 'may ari', 'owner'])) {
                    $response = 'Ang nakatalang may-ari ng property ' . $property['property_code'] . ' ay ' . ($property['owner_name'] ?: 'hindi nakatala') . '.';
                } elseif ($hasAny($normalized, ['area', 'lawak', 'sukat', 'sqm', 'square meter'])) {
                    $response = 'Ang recorded area ng property ' . $property['property_code'] . ' ay ' . ($property['recorded_area_sqm'] !== null ? number_format((float)$property['recorded_area_sqm'], 2) . ' sqm' : 'hindi nakatala') . '.';
                } elseif ($hasAny($normalized, ['saan', 'lokasyon', 'location', 'barangay', 'address'])) {
                    $location = array_filter([$property['barangay'], $property['municipality'], $property['province']]);
                    $response = 'Lokasyon ng property ' . $property['property_code'] . ': ' . ($location ? implode(', ', $location) : 'hindi nakatala') . '.';
                } elseif ($hasAny($normalized, ['coordinate', 'coordinates', 'gps', 'latitude', 'longitude', 'map', 'mapping', 'location'])) {
                    if ($property['latitude'] !== null && $property['longitude'] !== null) {
                        $response = 'May geotag ang property ' . $property['property_code'] . ': ' . $property['latitude'] . ', ' . $property['longitude'] . '. Tingnan ito sa Property Mapping. Paalala: ang GPS point ay hindi awtomatikong legal na survey boundary.';
                    } else {
                        $response = 'Walang pangunahing GPS coordinates na nakatala para sa property ' . $property['property_code'] . '. Maaaring tingnan kung may boundary markers sa Property Mapping.';
                    }
                } elseif ($hasAny($normalized, ['marker', 'mohon', 'boundary', 'boundaries', 'hangganan'])) {
                    $response = 'May ' . $markerCount . ' boundary marker' . ($markerCount === 1 ? '' : 's') . ' na nakatala para sa property ' . $property['property_code'] . '. Makikita o maidagdag ang mga ito sa Boundary Markers.';
                } elseif ($hasAny($normalized, ['verify', 'verification', 'verified', 'beripika', 'status'])) {
                    $response = 'Verification status ng property ' . $property['property_code'] . ': ' . $property['verification_status'] . '. Geotag status: ' . $property['geotag_status'] . '.';
                } else {
                    $response = 'Nakita ko ang property ' . $property['property_code']
                        . ($property['title_number'] ? ' (Title: ' . $property['title_number'] . ')' : '')
                        . '. May-ari: ' . ($property['owner_name'] ?: 'hindi nakatala')
                        . '; Barangay: ' . ($property['barangay'] ?: 'hindi nakatala')
                        . '; Recorded area: ' . ($property['recorded_area_sqm'] !== null ? number_format((float)$property['recorded_area_sqm'], 2) . ' sqm' : 'hindi nakatala')
                        . '; Verification: ' . $property['verification_status']
                        . '; Boundary markers: ' . $markerCount . '.';
                }
            } else {
                $governmentStmt = $conn->prepare(
                    'SELECT property_code, property_name, property_type, location, area_sqm, latitude, longitude
                     FROM government_properties
                     WHERE property_code LIKE ? OR property_name LIKE ? OR location LIKE ?
                     LIMIT 2'
                );
                $governmentStmt->bind_param('sss', $like, $like, $like);
                $governmentStmt->execute();
                $governmentRows = $governmentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $governmentStmt->close();

                if (count($governmentRows) === 1) {
                    $government = $governmentRows[0];
                    if ($hasAny($normalized, ['saan', 'lokasyon', 'location', 'coordinate', 'gps', 'map', 'mapping'])) {
                        $response = 'Lokasyon ng ' . $government['property_name'] . ': ' . ($government['location'] ?: 'hindi nakatala') . '.';
                        if ($government['latitude'] !== null && $government['longitude'] !== null) {
                            $response .= ' Coordinates: ' . $government['latitude'] . ', ' . $government['longitude'] . '.';
                        }
                    } elseif ($hasAny($normalized, ['area', 'lawak', 'sukat', 'sqm'])) {
                        $response = 'Ang nakatalang area ng ' . $government['property_name'] . ' ay ' . ($government['area_sqm'] !== null ? number_format((float)$government['area_sqm'], 2) . ' sqm' : 'hindi nakatala') . '.';
                    } else {
                        $response = 'Nakita ko ang government property ' . $government['property_name']
                            . ' (' . $government['property_code'] . '). Type: ' . ($government['property_type'] ?: 'hindi nakatala')
                            . '; Lokasyon: ' . ($government['location'] ?: 'hindi nakatala')
                            . '; Area: ' . ($government['area_sqm'] !== null ? number_format((float)$government['area_sqm'], 2) . ' sqm' : 'hindi nakatala') . '.';
                    }
                } elseif (count($governmentRows) > 1) {
                    $response = 'Maraming government property record ang tumugma sa “' . $identifier . '”. Pakibigay ang buong property code.';
                } else {
                    $response = 'Wala akong mahanap na property o government-property record na tumutugma sa “' . $identifier . '”. Pakisuri ang code o title number at subukan ulit.';
                }
            }
        } elseif ($hasAny($normalized, ['ilan', 'bilang', 'count', 'how many', 'karami', 'total'])) {
            if ($hasAny($normalized, ['government', 'pamahalaan'])) {
                $count = (int)$conn->query('SELECT COUNT(*) AS total FROM government_properties')->fetch_assoc()['total'];
                $response = 'May ' . $count . ' government propert' . ($count === 1 ? 'y' : 'ies') . ' na nakatala sa system.';
            } elseif ($hasAny($normalized, ['marker', 'mohon', 'boundary'])) {
                $count = (int)$conn->query('SELECT COUNT(*) AS total FROM boundary_markers')->fetch_assoc()['total'];
                $response = 'May ' . $count . ' boundary marker' . ($count === 1 ? '' : 's') . ' na nakatala sa system.';
            } elseif ($hasAny($normalized, ['verified', 'beripika'])) {
                $count = (int)$conn->query("SELECT COUNT(*) AS total FROM properties WHERE verification_status = 'Verified'")->fetch_assoc()['total'];
                $response = $count . ' propert' . ($count === 1 ? 'y' : 'ies') . ' ang may “Verified” status.';
            } else {
                $count = (int)$conn->query('SELECT COUNT(*) AS total FROM properties')->fetch_assoc()['total'];
                $response = 'May ' . $count . ' property record' . ($count === 1 ? '' : 's') . ' na nakatala sa system.';
            }
        } elseif ($hasAny($normalized, ['add property', 'magdagdag ng property', 'create property', 'new property'])) {
            $response = 'Para magdagdag ng property, buksan ang Properties at piliin ang Add Property. Ilagay ang property code at iba pang detalye, pagkatapos ay i-save. Maaari mong idagdag ang title at boundary markers sa kani-kanilang menu.';
        } elseif ($hasAny($normalized, ['mapping', 'map', 'location', 'coordinates', 'gps'])) {
            $response = 'Buksan ang Property Mapping at pumili o maghanap ng property. Ipinapakita nito ang mga nakatalang coordinates at boundary markers. Hindi dapat ituring na legal survey boundary ang GPS points kung hindi pa beripikado.';
        } elseif ($hasAny($normalized, ['marker', 'mohon', 'boundary', 'hangganan'])) {
            $response = 'Sa Boundary Markers, piliin ang property at itala ang bawat mohon kasama ang point number at coordinates. Kailangan ng hindi bababa sa tatlong puntos para maipakita bilang polygon sa mapa.';
        } elseif ($hasAny($normalized, ['verify', 'verification', 'beripika'])) {
            $response = 'Buksan ang Verification para suriin ang mga record. Ihambing ang opisyal na title/recorded information sa field data; huwag ituring na legal na survey ang hindi pa beripikadong GPS coordinates.';
        } elseif ($hasAny($normalized, ['report', 'reports', 'ulat'])) {
            $response = 'Buksan ang Reports para tingnan ang property, title, government-property, at verification reports.';
        } elseif ($hasAny($normalized, ['document', 'papel', 'file', 'upload'])) {
            $response = 'Buksan ang Documents para makita at iugnay ang mga dokumento sa property record.';
        } elseif ($hasAny($normalized, ['help', 'tulong', 'ano ang kaya', 'what can you'])) {
            $response = 'Matutulungan kitang maghanap ng property gamit ang property code, title number, o lot number; tingnan ang owner, area, lokasyon, status, at markers; at magbigay ng gabay sa Mapping, Verification, Reports, at Documents. Ano ang gusto mong malaman?';
        } else {
            $response = 'Hindi ko pa matukoy ang tanong. Maaari kang magtanong gamit ang property code o title number, gaya ng “Ano ang status ng property ABC-123?” o magtanong tungkol sa mapping, boundary markers, verification, reports, o documents.';
        }
    }

    $adminId = current_admin_id();
    $logStmt = $conn->prepare('INSERT INTO chatbot_logs (admin_id, user_message, assistant_response, related_property_id) VALUES (?, ?, ?, ?)');
    $logStmt->bind_param('issi', $adminId, $message, $response, $related);
    $logStmt->execute();
    $logStmt->close();
}
?>
<div class="chat-panel">
  <div class="chat-head"><i class="bi bi-robot"></i>
    <div><strong>MPDC Assistant</strong><small>Sumasagot gamit ang mga record sa MPDC system</small></div>
  </div>
  <div class="chat-response"><?= nl2br(e($response)) ?></div>
  <form method="post" class="chat-form">
    <input name="message" value="<?= e($message) ?>" placeholder="Hal. Sino ang may-ari ng property ABC-123?" required>
    <button class="btn btn-primary">Send</button>
  </form>
</div>
<?php require 'includes/footer.php'; ?>
