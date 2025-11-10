<?php
define('PREVENT_DIRECT_ACCESS', TRUE);

// Mock $_SERVER for CLI execution
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'CLI';
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}

// Setup constants like index.php does
define('ROOT_DIR',  __DIR__ . DIRECTORY_SEPARATOR);
define('SYSTEM_DIR', ROOT_DIR . 'scheme' . DIRECTORY_SEPARATOR);
define('APP_DIR', ROOT_DIR . 'app' . DIRECTORY_SEPARATOR);
define('PUBLIC_DIR', 'public');

// Load the bootstrap
require_once SYSTEM_DIR . 'kernel/LavaLust.php';

try {
    // Get the Invoker instance (which should be initialized by LavaLust.php)
    $app = lava_instance();
    
    // Load the UsersModel
    $app->model('UsersModel');
    
    echo "=== Testing UsersModel ===\n\n";
    
    // Test 1: Try getting all users
    echo "Test 1: UsersModel->all()\n";
    $result = $app->UsersModel->all();
    echo "Type: " . gettype($result) . "\n";
    echo "Count: " . (is_array($result) ? count($result) : "N/A") . "\n";
    echo "Data:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: Try raw query
    echo "Test 2: Raw COUNT query\n";
    try {
        $stmt = $app->UsersModel->raw("SELECT COUNT(*) as cnt FROM users");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Result: " . json_encode($row) . "\n\n";
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }
    
    // Test 3: Try raw SELECT *
    echo "Test 3: Raw SELECT * query\n";
    try {
        $stmt = $app->UsersModel->raw("SELECT * FROM users");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Count: " . count($rows) . "\n";
        echo "Data:\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n\n";
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }
    
    // Test 4: Check the table name and properties
    echo "Test 4: Model configuration\n";
    echo "Table: " . $app->UsersModel->table . "\n";
    echo "Primary Key: " . $app->UsersModel->primary_key . "\n";
    
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}
?>