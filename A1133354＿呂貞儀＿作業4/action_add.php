<?php
require_once __DIR__ . '/db.php';

$email = trim((string)($_POST['email'] ?? ''));
if (!is_valid_email($email)) {
    http_response_code(400);
    echo 'Invalid email';
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('INSERT IGNORE INTO recipients (email, is_opt_in) VALUES (:email, 1)');
$stmt->execute([':email' => $email]);

header('Location: index.php');

