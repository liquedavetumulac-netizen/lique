<?php
/*
 * RUN THIS FILE ONCE LOCALLY TO CREATE THE MPDC ADMIN ACCOUNT.
 * Delete this file after creating the account.
 */

require_once 'db.php';

$username = 'admin';
$password = 'ChangeMe123!';
$fullName = 'MPDC Administrator';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO admins (username, password_hash, full_name, is_active)
     VALUES (?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE
       password_hash = VALUES(password_hash),
       full_name = VALUES(full_name),
       is_active = 1"
);

$stmt->bind_param("sss", $username, $hash, $fullName);

if ($stmt->execute()) {
    echo "Admin account created/updated successfully.<br>";
    echo "Username: admin<br>";
    echo "Temporary password: ChangeMe123!<br>";
    echo "<strong>Change the password immediately after logging in.</strong>";
} else {
    echo "Failed to create admin account: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>
