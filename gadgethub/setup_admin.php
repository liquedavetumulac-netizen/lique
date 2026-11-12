<?php
require 'config.php';

// Run this page ONCE to create the first administrator account.
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$username = 'admin';
$temporaryPassword = 'Admin@12345';
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);

if ($stmt->fetch()) {
    $message = 'The admin account already exists. You can sign in now.';
} else {
    $hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $insert->execute([$username, $hash]);
    $message = 'Admin account created successfully.';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>GadgetHub Admin Setup</title><link rel="stylesheet" href="assets/login.css"></head>
<body><main class="setup-card"><span class="eyebrow">GADGETHUB SETUP</span><h1><?= e($message) ?></h1>
<p>Username: <b>admin</b><br>Temporary password: <b>Admin@12345</b></p>
<p class="warning">Important: Delete <b>setup_admin.php</b> from your gadgethub folder after this page succeeds.</p>
<a class="setup-link" href="login.php">Go to login →</a></main></body></html>
