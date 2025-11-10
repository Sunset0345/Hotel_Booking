<?php
require 'app/config/config.php';
require 'app/config/database.php';

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);

// Test: What does UsersModel->all() return?
echo "Testing UsersModel->all()...\n\n";

$stmt = $pdo->query("SELECT * FROM users ORDER BY full_name ASC LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Users fetched: " . count($users) . "\n";
foreach ($users as $u) {
    echo json_encode($u) . "\n";
}

echo "\n\nJSON encoded array:\n";
echo json_encode($users) . "\n";

?>
