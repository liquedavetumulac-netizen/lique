<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property = (int)($_POST['property_id'] ?? 0);
    $title = trim($_POST['title_number'] ?? '');
    $area = (float)($_POST['title_area_sqm'] ?? 0);
    $owner = trim($_POST['registered_owner'] ?? '');
    $type = trim($_POST['title_type'] ?? '');
    if (!$property || !$title) {
        $error = 'Property and title number are required.';
    } else {
        $s = $conn->prepare("INSERT INTO land_titles(property_id,title_number,title_type,registered_owner,title_area_sqm,created_by,updated_by) VALUES(?,?,?,?,?,?,?)");
        $admin = current_admin_id();
        $s->bind_param("isssdii", $property, $title, $type, $owner, $area, $admin, $admin);
        if ($s->execute()) {
            log_activity($conn, $admin, 'CREATE', 'land_titles', $s->insert_id, 'Land title added.');
            flash('success', 'Land title saved.');
            header('Location: titles.php');
            exit;
        }
        $error = 'Could not save title. The title number may already exist or the property already has a title.';
    }
}
$props = $conn->query("SELECT id,property_code,owner_name FROM properties ORDER BY property_code");
$pageTitle = 'Add Land Title';
require 'includes/header.php'; ?>
<div class="card-panel">
    <h3>Add Land Title</h3><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post" class="row g-3">
        <div class="col-md-6"><label>Property</label><select name="property_id" class="form-select" required>
                <option value="">Select property</option><?php while ($p = $props->fetch_assoc()): ?><option value="<?= $p['id'] ?>"><?= e($p['property_code'] . ' — ' . $p['owner_name']) ?></option><?php endwhile; ?>
            </select></div>
        <div class="col-md-6"><label>Title Number</label><input class="form-control" name="title_number" required></div>
        <div class="col-md-4"><label>Title Type</label><input class="form-control" name="title_type"></div>
        <div class="col-md-4"><label>Registered Owner</label><input class="form-control" name="registered_owner"></div>
        <div class="col-md-4"><label>Title Area (sqm)</label><input class="form-control" type="number" step="0.01" name="title_area_sqm"></div>
        <div class="col-12"><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="titles.php">Cancel</a></div>
    </form>
</div>
<?php require 'includes/footer.php'; ?>