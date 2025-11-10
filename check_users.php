<?php
require 'app/config/config.php';
require 'app/config/database.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM users');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users in DB: " . $row['cnt'] . "\n";
    
    // Also show first 5 users
    $stmt2 = $pdo->query('SELECT user_id, full_name, email FROM users LIMIT 5');
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "\nFirst 5 users:\n";
    foreach ($rows as $u) {
        echo "  - ID: " . $u['user_id'] . ", Name: " . $u['full_name'] . ", Email: " . $u['email'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
