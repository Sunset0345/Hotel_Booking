<?php
// Debug endpoint: prints the current session user data as JSON for local testing only.
// Do NOT deploy this file to production.

defined('PREVENT_DIRECT_ACCESS') OR null; // harmless if constant not defined
session_start();
header('Content-Type: application/json');
// Allow CORS locally for quick testing (remove in production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}

$user = $_SESSION['user'] ?? null;
if (!$user) {
    echo json_encode(['status' => 'no_session', 'user' => null]);
    exit;
}
// Normalize ints
if (isset($user['is_verified'])) { $user['is_verified'] = (int)$user['is_verified']; }
if (isset($user['verification_requested'])) { $user['verification_requested'] = (int)$user['verification_requested']; }

echo json_encode(['status' => 'ok', 'user' => $user]);
