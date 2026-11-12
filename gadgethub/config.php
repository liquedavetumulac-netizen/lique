<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$host = 'localhost';
$db   = 'gadgethub';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config.php and import database.sql. Details: ' . htmlspecialchars($e->getMessage()));
}
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money($value) { return '₱' . number_format((float)$value, 2); }
function flash($message, $type='success') { $_SESSION['flash'] = ['message'=>$message,'type'=>$type]; }
function redirect($url='index.php') { header("Location: $url"); exit; }
