<?php
$pageTitle = 'Property Mapping';
require_once 'includes/header.php';   // naglo-load din ng db.php ($conn)
 
$selected     = (int)($_GET['property_id'] ?? 0);
$selectedType = $_GET['type'] ?? 'property';
 
$properties = $conn->query("
    SELECT p.id, p.property_code, p.owner_name, p.latitude, p.longitude,
           p.recorded_area_sqm, lt.title_number, b.name AS barangay
    FROM properties p
    LEFT JOIN land_titles lt ON lt.property_id = p.id
    LEFT JOIN barangays b ON b.id = p.barangay_id
    ORDER BY p.property_code
")->fetch_all(MYSQLI_ASSOC);
 
$government = $conn->query("
    SELECT id, property_code, property_name, property_type, location,
           area_sqm, latitude, longitude
    FROM government_properties
    ORDER BY id DESC
")->fetch_all(MYSQLI_ASSOC);
 
$markers = $conn->query("
    SELECT property_id, point_number, latitude, longitude, marker_code, description
    FROM boundary_markers
    ORDER BY property_id, point_number
")->fetch_all(MYSQLI_ASSOC);
?>
<div class="card-panel mb-3" style="padding:14px">
  <label class="form-label fw-bold">Select property / government property</label>
  <select id="propertySelect" class="form-select">
    <option value="">— Select —</option>
    <?php foreach($properties as $r): ?>
      <option value="property:<?= (int)$r['id'] ?>" <?= ($selectedType==='property' && $selected===(int)$r['id']) ? 'selected' : '' ?>>
        Land Title: <?= h($r['title_number'] ?: $r['property_code']) ?> — <?= h($r['owner_name'] ?: 'No owner') ?>
      </option>
    <?php endforeach; ?>
    <?php foreach($government as $r): ?>
      <option value="government:<?= (int)$r['id'] ?>" <?= ($selectedType==='government' && $selected===(int)$r['id']) ? 'selected' : '' ?>>
        Government: <?= h($r['property_code']) ?> — <?= h($r['property_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <small class="text-muted d-block mt-2">
    Satellite view (Esri). Red = land/government location, numbered blue = recorded mohon points.
    GPS points are entered in Boundary Markers.
  </small>
</div>
 
<div id="map" style="height:70vh;min-height:480px;border-radius:12px;border:1px solid #ead4a8"></div>
 
<script>
const PROPERTIES = <?= json_encode($properties, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const GOVERNMENT = <?= json_encode($government, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const MARKERS    = <?= json_encode($markers,    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const SELECTED   = <?= json_encode($selected) ?>;
const SELECTED_TYPE = <?= json_encode($selectedType) ?>;
 
window.addEventListener('load', function () {
  const map = L.map('map').setView([14.15, 121.98], 12);
 
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri, Maxar, Earthstar Geographics'
  }).addTo(map);
 
  const layer = L.layerGroup().addTo(map);
  const esc = s => String(s ?? '—').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const ok = v => v !== null && v !== '' && !isNaN(Number(v));
 
  function pointsOf(id) {
    return MARKERS.filter(m => Number(m.property_id) === Number(id))
                  .sort((a, b) => Number(a.point_number) - Number(b.point_number));
  }
 
  function centerOfProperty(p) {
    if (ok(p.latitude) && ok(p.longitude)) return [Number(p.latitude), Number(p.longitude)];
    const pts = pointsOf(p.id);
    if (!pts.length) return null;
    const lat = pts.reduce((s, x) => s + Number(x.latitude), 0) / pts.length;
    const lng = pts.reduce((s, x) => s + Number(x.longitude), 0) / pts.length;
    return [lat, lng];
  }
 
  function redPin(pos, html) {
    return L.circleMarker(pos, {radius: 8, color: '#fff', weight: 2, fillColor: '#8b1e3f', fillOpacity: 1})
            .bindPopup(html);
  }
 
  function showAll() {
    layer.clearLayers();
    const bounds = [];
    PROPERTIES.forEach(p => {
      const c = centerOfProperty(p);
      if (!c) return;
      bounds.push(c);
      redPin(c, '<strong>' + esc(p.title_number || p.property_code) + '</strong><br>Owner: ' + esc(p.owner_name) +
        '<br>Area: ' + esc(p.recorded_area_sqm) + ' sqm<br>Barangay: ' + esc(p.barangay) +
        '<br><a href="property_view.php?id=' + Number(p.id) + '">View Property</a>').addTo(layer);
    });
    GOVERNMENT.forEach(g => {
      if (!ok(g.latitude) || !ok(g.longitude)) return;
      const c = [Number(g.latitude), Number(g.longitude)];
      bounds.push(c);
      redPin(c, '<strong>' + esc(g.property_name || g.property_code) + '</strong><br>Type: ' + esc(g.property_type) +
        '<br>Location: ' + esc(g.location) + '<br>Area: ' + esc(g.area_sqm) + ' sqm').addTo(layer);
    });
    if (bounds.length) map.fitBounds(bounds, {padding: [40, 40], maxZoom: 16});
  }
 
  function showSelected(type, id) {
    layer.clearLayers();
    if (type === 'government') {
      const g = GOVERNMENT.find(x => Number(x.id) === id);
      if (!g || !ok(g.latitude) || !ok(g.longitude)) { alert('No coordinates recorded for this government property yet.'); return; }
      const c = [Number(g.latitude), Number(g.longitude)];
      redPin(c, '<strong>' + esc(g.property_name || g.property_code) + '</strong>').addTo(layer).openPopup();
      map.setView(c, 18);
      return;
    }
    const p = PROPERTIES.find(x => Number(x.id) === id);
    if (!p) return;
    const pts = pointsOf(id);
    const c = centerOfProperty(p);
    if (!c) { alert('No coordinates or mohon points recorded for this property yet.'); return; }
 
    const path = pts.map(m => [Number(m.latitude), Number(m.longitude)]);
    if (path.length >= 3) {
      L.polygon(path, {color: '#8b1e3f', weight: 3, fillColor: '#8b1e3f', fillOpacity: 0.2}).addTo(layer);
    } else if (path.length === 2) {
      L.polyline(path, {color: '#8b1e3f', weight: 3}).addTo(layer);
    }
    pts.forEach(m => {
      L.marker([Number(m.latitude), Number(m.longitude)], {
        icon: L.divIcon({
          className: '',
          html: '<div style="background:#1565c0;color:#fff;border:2px solid #fff;border-radius:50%;width:24px;height:24px;line-height:20px;text-align:center;font:bold 12px Arial">' + Number(m.point_number) + '</div>',
          iconSize: [24, 24], iconAnchor: [12, 12]
        })
      }).bindPopup('Point ' + Number(m.point_number) + '<br>' + esc(m.marker_code) +
        (m.description ? '<br>' + esc(m.description) : '')).addTo(layer);
    });
    if (ok(p.latitude) && ok(p.longitude)) {
      redPin(c, '<strong>' + esc(p.title_number || p.property_code) + '</strong><br>Owner: ' + esc(p.owner_name)).addTo(layer);
    }
    if (path.length) map.fitBounds(path, {padding: [50, 50], maxZoom: 19});
    else map.setView(c, 18);
  }
 
  const select = document.getElementById('propertySelect');
  select.addEventListener('change', function () {
    if (!this.value) { showAll(); return; }
    const parts = this.value.split(':');
    showSelected(parts[0], Number(parts[1]));
  });
 
  if (SELECTED) { select.value = SELECTED_TYPE + ':' + SELECTED; showSelected(SELECTED_TYPE, SELECTED); }
  else showAll();
});
</script>
<?php require_once 'includes/footer.php'; ?>