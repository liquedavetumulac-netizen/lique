document.addEventListener("DOMContentLoaded", () => {
  const mapEl = document.getElementById("map");
  if (mapEl && typeof L !== "undefined") {
    const props = window.MPDC_PROPERTIES || [],
      markers = window.MPDC_MARKERS || [];
    let center = [13.83, 121.982],
      zoom = 12;
    const selected = window.SELECTED_PROPERTY || 0;
    const chosen = props.find((p) => Number(p.id) === Number(selected));
    if (chosen) {
      center = [Number(chosen.latitude), Number(chosen.longitude)];
      zoom = 17;
    }
    const map = L.map("map").setView(center, zoom);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);
    props.forEach((p) => {
      const marker = L.marker([Number(p.latitude), Number(p.longitude)]).addTo(
        map,
      );
      marker.bindPopup(
        `<strong>${escapeHtml(p.property_code)}</strong><br>Title: ${escapeHtml(p.title_number || "—")}<br>Area: ${escapeHtml(p.recorded_area_sqm || "—")} sqm<br><a href="property_view.php?id=${p.id}">View Property</a>`,
      );
    });
    const grouped = {};
    markers.forEach((m) => {
      if (!grouped[m.property_id]) grouped[m.property_id] = [];
      grouped[m.property_id].push([Number(m.latitude), Number(m.longitude)]);
    });
    Object.keys(grouped).forEach((id) => {
      if (grouped[id].length >= 3)
        L.polygon(grouped[id], { weight: 3, fillOpacity: 0.18 })
          .addTo(map)
          .bindPopup("Boundary markers for property ID " + id);
      grouped[id].forEach((pt, i) =>
        L.circleMarker(pt, { radius: 5 })
          .addTo(map)
          .bindTooltip("Mohon " + (i + 1)),
      );
    });
    const form = document.getElementById("mapSearch");
    if (form)
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        const q = document
          .getElementById("titleSearch")
          .value.trim()
          .toLowerCase();
        const p = props.find(
          (x) =>
            (x.title_number || "").toLowerCase() === q ||
            (x.title_number || "").toLowerCase().includes(q),
        );
        if (p) {
          map.setView([Number(p.latitude), Number(p.longitude)], 17);
          props.find(() => false);
          alert("Property located on the map.");
        } else alert("No matching geotagged title was found.");
      });
  }
});
function escapeHtml(s) {
  return String(s ?? "").replace(
    /[&<>"']/g,
    (c) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      })[c],
  );
}
