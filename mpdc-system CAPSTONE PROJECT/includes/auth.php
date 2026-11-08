<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        header("Location: index.php");
        exit;
    }
}

function current_admin_id(): ?int {
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
