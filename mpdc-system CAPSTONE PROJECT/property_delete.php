<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("DELETE FROM properties WHERE id=?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    log_activity($conn, current_admin_id(), 'DELETE', 'properties', $id, 'Property deleted.');
    flash('success', 'Property deleted.');
} else flash('danger', 'Could not delete property.');
header("Location: properties.php");
exit;
