<?php
// Simulate the controller environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['admin'] = ['admin_id' => 1, 'full_name' => 'Admin'];

// Load the framework
chdir(__DIR__);

// Try to load the model and call all()
echo "=== Testing UsersModel->all() ===\n\n";

try {
    // Load config
    require 'app/config/config.php';
    require 'app/config/database.php';
    require 'scheme/kernel/Database.php';
    require 'scheme/kernel/Model.php';
    require 'app/models/UsersModel.php';
    
    // Create model instance
    echo "Creating UsersModel instance...\n";
    $usersModel = new UsersModel();
    
    echo "Calling all()...\n";
    $users = $usersModel->all();
    
    echo "Result type: " . gettype($users) . "\n";
    echo "Result count: " . (is_array($users) ? count($users) : "N/A (not array)") . "\n";
    echo "\nUsers data:\n";
    echo json_encode($users, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

?>
