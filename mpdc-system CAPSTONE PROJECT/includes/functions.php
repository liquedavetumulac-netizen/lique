<?php
require_once __DIR__ . '/../db.php';

function h($value): string {
    return e($value);
}

function log_activity(mysqli $conn, ?int $adminId, string $action, string $module, ?int $recordId, string $description): void {
    $stmt = $conn->prepare("INSERT INTO activity_logs
        (admin_id, action, module, record_type, record_id, description, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $recordType = $module;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $stmt->bind_param("isssisss", $adminId, $action, $module, $recordType, $recordId, $description, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type'=>$type, 'message'=>$message];
}

function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function setting(mysqli $conn, string $key, string $default=''): string {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key=? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['setting_value'] ?? $default;
}
?>
