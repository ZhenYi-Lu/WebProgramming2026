<?php
require_once __DIR__ . '/db.php';

$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) $ids = [];
$ids = array_values(array_unique(array_map('intval', $ids)));

if (count($ids) > 0) {
    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM recipients WHERE id IN ({$placeholders})");
    foreach ($ids as $i => $id) {
        $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
    }
    $stmt->execute();
}

header('Location: index.php');

