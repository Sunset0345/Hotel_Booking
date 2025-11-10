<?php
// Simulate admin message fragment request
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simulate logged-in admin
$_SESSION['admin'] = [
    'admin_id' => 1,
    'username' => 'admin',
    'full_name' => 'Admin User'
];

require 'app/config/config.php';
require 'app/config/database.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Simulating AdminController::messages() GET request ===\n";
    
    // Get users with unread count (same query as controller)
    $users = [];
    try {
        $sql = "SELECT u.*, (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.user_id AND m.from_admin = 0 AND m.is_read = 0) AS unread FROM users u ORDER BY u.full_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Successfully fetched " . count($users) . " users\n";
    } catch (Exception $e) {
        echo "Query failed: " . $e->getMessage() . "\n";
        // Fallback
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY full_name ASC");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$uu) {
            $uu['unread'] = 0;
        }
        unset($uu);
        echo "Fallback: Got " . count($users) . " users\n";
    }
    
    echo "\nData that would be passed to view:\n";
    echo "  \$users = " . json_encode($users) . "\n";
    echo "  isset(\$users) = " . (isset($users) ? 'true' : 'false') . "\n";
    echo "  is_array(\$users) = " . (is_array($users) ? 'true' : 'false') . "\n";
    echo "  count(\$users) = " . (isset($users) ? count($users) : 'N/A') . "\n";
    
    echo "\nWhat the view's PHP would output in JavaScript:\n";
    if (isset($users) && is_array($users) && count($users) > 0) {
        $output = json_encode($users);
        echo "window.msgs_state.users = " . substr($output, 0, 200) . "...\n";
    } else {
        echo "window.msgs_state.users = []\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
?>
