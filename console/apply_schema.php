<?php
// Run: php apply_schema.php
// This script reads the DB config and applies scheme/database/hotel_booking.sql
// It will execute each SQL statement separated by a semicolon.

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Load DB config
$dbConfigFile = ROOT . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
if (!file_exists($dbConfigFile)) {
    echo "database.php not found at $dbConfigFile\n";
    exit(1);
}

require $dbConfigFile;

if (!isset($database) || !isset($database['main'])) {
    echo "Invalid database configuration in $dbConfigFile\n";
    exit(1);
}

$cfg = $database['main'];
$driver = isset($cfg['driver']) ? $cfg['driver'] : 'mysql';
$host = $cfg['hostname'] ?? 'localhost';
$port = $cfg['port'] ?? '3306';
$user = $cfg['username'] ?? '';
$pass = $cfg['password'] ?? '';
$charset = $cfg['charset'] ?? 'utf8mb4';
$path = $cfg['path'] ?? '';

// Build DSN without selecting a database so CREATE DATABASE / USE works
switch (strtolower($driver)) {
    case 'mysql':
        $dsn = "mysql:host=$host;port=$port;charset=$charset";
        break;
    case 'pgsql':
        $dsn = "pgsql:host=$host;port=$port";
        break;
    case 'sqlite':
        if (empty($path)) {
            echo "SQLite requires 'path' in database.php\n";
            exit(1);
        }
        $dsn = "sqlite:$path";
        break;
    default:
        echo "Unsupported driver: $driver\n";
        exit(1);
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . "\n";
    exit(1);
}

$sqlFile = ROOT . 'scheme' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'hotel_booking.sql';
if (!file_exists($sqlFile)) {
    echo "SQL file not found at $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
// Remove PHP style fences if any and normalize newlines
$sql = trim($sql);

// Split statements by semicolon followed by newline (best-effort)
$statements = preg_split('/;\s*\n/', $sql);

$success = 0;
$failed = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;

    try {
        $pdo->exec($stmt);
        $success++;
        echo "OK: " . preg_replace('/\s+/', ' ', substr($stmt, 0, 80)) . "...\n";
    } catch (Exception $e) {
        $failed++;
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "SQL: " . substr($stmt, 0, 200) . "\n\n";
    }
}

echo "\nDone. Success: $success. Failed: $failed.\n";

if ($failed) exit(2);
exit(0);
