<?php
/**
 * Temporary debug page: prints current PHP session contents.
 * Remove this file when finished debugging.
 */
define('PREVENT_DIRECT_ACCESS', TRUE);
// start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>Session Debug</title>';
echo '<style>body{font-family:Inter,system-ui,Arial;margin:20px;background:#f6f8fa;color:#111}pre{background:#fff;border:1px solid #ddd;padding:12px;border-radius:6px;}</style>';
echo '</head><body>';
echo '<h2>PHP Session Debug</h2>';
echo '<p><strong>Warning:</strong> This page shows session contents. Do not leave it on a public server. Remove when done.</p>';

echo '<h3>$_SESSION</h3>';
echo '<pre>' . htmlspecialchars(print_r($_SESSION, true), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '</pre>';

echo '<p><a href="index.php">Back to home</a></p>';
echo '</body></html>';
