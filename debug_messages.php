<?php
// Debug: Simulate what AdminController::messages() does

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load framework config
require 'app/config/config.php';
require 'app/config/database.php';

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Debug: Admin Messages Fetch ===\n\n";

// Test 1: Try the complex query
echo "1. Complex query with unread count:\n";
try {
    $sql = "SELECT u.*, (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.user_id AND m.from_admin = 0 AND m.is_read = 0) AS unread FROM users u ORDER BY u.full_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Result: " . count($users) . " users\n";
    foreach ($users as $u) {
        echo "   - ID: " . $u['user_id'] . ", Name: " . $u['full_name'] . ", Unread: " . $u['unread'] . "\n";
    }
    echo "   JSON output: " . json_encode($users) . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    $users = [];
}

// Test 2: Simple query as fallback
if (empty($users)) {
    echo "\n2. Simple fallback query:\n";
    try {
        $sql = "SELECT * FROM users ORDER BY full_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Result: " . count($users) . " users\n";
        foreach ($users as &$u) {
            $u['unread'] = 0;
        }
        foreach ($users as $u) {
            echo "   - ID: " . $u['user_id'] . ", Name: " . $u['full_name'] . ", Unread: " . $u['unread'] . "\n";
        }
        echo "   JSON output: " . json_encode($users) . "\n";
    } catch (Exception $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
    }
}

// Test 3: What would be passed to the view
echo "\n3. Final data passed to view:\n";
echo "   users array: " . json_encode($users) . "\n";
echo "   users array length: " . count($users) . "\n";
echo "   users isset: " . (isset($users) ? 'YES' : 'NO') . "\n";
echo "   users is_array: " . (is_array($users) ? 'YES' : 'NO') . "\n";

// Test what the view would render
echo "\n4. What the view JavaScript would see:\n";
if (isset($users) && is_array($users) && count($users) > 0) {
    echo "   json_encode would output: " . json_encode($users) . "\n";
} else {
    echo "   json_encode would output: []\n";
}

?>
