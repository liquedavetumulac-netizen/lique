<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();
$pageTitle = $pageTitle ?? 'MPDC System';
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | MPDC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
<aside class="sidebar">
    <div class="brand"><div class="brand-logo">MPDC</div><div><strong>MPDC SYSTEM</strong><small>Land & Property Records</small></div></div>
    <nav>
      <a href="dashboard.php"><i class="bi bi-grid"></i> Dashboard</a>
      <a href="properties.php"><i class="bi bi-buildings"></i> Properties</a>
      <a href="titles.php"><i class="bi bi-file-earmark-text"></i> Land Titles</a>
      <a href="mapping.php"><i class="bi bi-geo-alt"></i> Property Mapping</a>
      <a href="markers.php"><i class="bi bi-pin-map"></i> Boundary Markers</a>
      <a href="government.php"><i class="bi bi-bank"></i> Government Properties</a>
      <a href="documents.php"><i class="bi bi-folder2-open"></i> Documents</a>
      <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
      <a href="verification.php"><i class="bi bi-check2-circle"></i> Verification</a>
      <a href="chatbot.php"><i class="bi bi-robot"></i> MPDC Assistant</a>
      <a href="logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a>
      <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
      <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</aside>
<main class="main">
<header class="topbar"><div><span class="page-kicker">Municipal Planning and Development Coordinator</span><h1><?= e($pageTitle) ?></h1></div><div class="admin-pill"><i class="bi bi-person-circle"></i> <?= e($_SESSION['admin_name'] ?? 'Administrator') ?></div></header>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
