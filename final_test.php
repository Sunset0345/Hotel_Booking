<?php
require 'app/config/config.php';
require 'app/config/database.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing message queries...\n\n";
    
    // 1. Count users
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM users');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users in database: " . $row['cnt'] . "\n";
    
    // 2. Get all users
    $stmt = $pdo->query('SELECT * FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Fetched " . count($users) . " users:\n";
    foreach ($users as $u) {
        echo "  - ID " . $u['user_id'] . ": " . $u['full_name'] . "\n";
    }
    
    // 3. Try with complex query
    echo "\nTrying complex query with unread subquery...\n";
    $sql = "SELECT u.*, (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.user_id AND m.from_admin = 0 AND m.is_read = 0) AS unread FROM users u ORDER BY u.full_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Complex query result: " . count($users) . " users\n";
    foreach ($users as $u) {
        echo "  - ID " . $u['user_id'] . ": " . $u['full_name'] . " (unread: " . $u['unread'] . ")\n";
    }
    
    // 4. Test json_encode
    echo "\njson_encode of users:\n";
    echo json_encode($users) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
