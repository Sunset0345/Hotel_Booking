<?php
// Direct test of admin messages endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simulate admin logged in
$_SESSION['admin'] = [
    'admin_id' => 1,
    'username' => 'admin',
    'full_name' => 'Test Admin'
];

// Simulate AJAX request
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

// Load framework
chdir(__DIR__);
require 'index.php';

?>
