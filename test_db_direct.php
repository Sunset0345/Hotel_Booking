<?php
// Simple direct database test - bypassing the framework for now
define('PREVENT_DIRECT_ACCESS', TRUE);

// Load just the database config
require_once 'app/config/database.php';

try {
    // Get the config (it's an associative array with a 'main' key)
    $db = $database['main'];
    
    echo "Database Configuration:\n";
    echo "  Driver: " . $db['driver'] . "\n";
    echo "  Host: " . $db['hostname'] . "\n";
    echo "  Port: " . $db['port'] . "\n";
    echo "  Database: " . $db['database'] . "\n";
    echo "  Username: " . $db['username'] . "\n\n";
    
    // Get the DSN
    $dsn = sprintf(
        "%s:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        $db['driver'],         // mysql
        $db['hostname'],       // localhost
        $db['port'],           // 3306
        $db['database']        // hotel_booking
    );
    
    echo "DSN: " . $dsn . "\n\n";
    
    // Create PDO connection
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Database connection successful\n\n";
    
    // Test 1: Count users
    echo "Test 1: SELECT COUNT(*) FROM users\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Count: " . $row['cnt'] . "\n\n";
    
    // Test 2: Get all users
    echo "Test 2: SELECT * FROM users\n";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Retrieved " . count($users) . " users:\n";
    echo json_encode($users, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Test the SELECT statement that the model would use
    echo "Test 3: Raw query execution (what Model::all() does)\n";
    $stmt = $pdo->prepare("SELECT * FROM users");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Retrieved " . count($rows) . " users with prepared statement\n";
    echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
