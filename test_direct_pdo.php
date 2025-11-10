Test: Direct UsersModel->all() call

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'app/config/config.php';
require 'app/config/database.php';

// Initialize the Database connection manually
$db = new Database();

// Create a simple test to directly query users
try {
    // Direct PDO query
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query('SELECT * FROM users ORDER BY full_name ASC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n\n=== DIRECT PDO QUERY ===\n";
    echo "Users count: " . count($users) . "\n";
    echo json_encode($users, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
