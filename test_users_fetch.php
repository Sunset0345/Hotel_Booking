<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=hotel_booking;charset=utf8mb4', 'admin', 'admin');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test 1: Simple select all
    echo "=== Test 1: Simple SELECT all users ===\n";
    $stmt = $pdo->query('SELECT user_id, full_name, email FROM users ORDER BY full_name ASC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($users) . " users\n";
    foreach ($users as $u) {
        echo "  - " . $u['full_name'] . " (ID: " . $u['user_id'] . ")\n";
    }
    
    // Test 2: With unread subquery
    echo "\n=== Test 2: SELECT with unread count subquery ===\n";
    $stmt2 = $pdo->query("SELECT u.user_id, u.full_name, (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.user_id AND m.from_admin = 0 AND m.is_read = 0) AS unread FROM users u ORDER BY u.full_name ASC");
    $users2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($users2) . " users with unread counts\n";
    foreach ($users2 as $u) {
        echo "  - " . $u['full_name'] . " (ID: " . $u['user_id'] . ", unread: " . $u['unread'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}
?>
