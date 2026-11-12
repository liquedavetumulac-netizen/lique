<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect();
$entity = $_POST['entity'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$tables = ['product' => 'products', 'category' => 'categories', 'brand' => 'brands', 'supplier' => 'suppliers'];
if (!$id || !isset($tables[$entity])) {
    flash('Invalid delete request.', 'error');
    redirect();
}
try {
    $st = $pdo->prepare("DELETE FROM {$tables[$entity]} WHERE id=?");
    $st->execute([$id]);
    flash($st->rowCount() ? 'Record deleted successfully.' : 'Record was not found.');
} catch (PDOException $e) {
    flash('Cannot delete this record because it is linked to existing records.', 'error');
}
redirect('index.php?page=' . (['product' => 'products', 'category' => 'categories', 'brand' => 'brands', 'supplier' => 'suppliers'][$entity]));
