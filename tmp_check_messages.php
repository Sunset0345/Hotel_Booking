<?php
// allow direct CLI include of config guarded by PREVENT_DIRECT_ACCESS
if(!defined('PREVENT_DIRECT_ACCESS')) define('PREVENT_DIRECT_ACCESS', true);
require __DIR__ . '/app/config/database.php';
$config = $database['main'];
$dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $config['driver'], $config['hostname'], $config['port'], $config['database'], $config['charset']);
try{
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch(Exception $e){
    echo "DB connect failed: " . $e->getMessage() . PHP_EOL; exit(1);
}

for($uid = 1; $uid <= 4; $uid++){
    echo "--- user_id={$uid} ---\n";
    $stmt = $pdo->prepare('SELECT m.*, u.full_name FROM messages m JOIN users u ON u.user_id = m.user_id WHERE m.user_id = ? ORDER BY m.date_sent ASC');
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(!$rows){
        echo "No messages found for user_id={$uid}\n";
    } else {
        foreach($rows as $r){
            echo "[{$r['message_id']}] from_admin={$r['from_admin']} is_read={$r['is_read']} date_sent={$r['date_sent']} message=" . trim(substr($r['message'],0,120)) . "\n";
        }
    }
}

// show unread counts per user
echo "\nUnread counts:\n";
$stmt = $pdo->query("SELECT u.user_id, u.full_name, COALESCE((SELECT COUNT(*) FROM messages m WHERE m.user_id = u.user_id AND m.from_admin = 0 AND m.is_read = 0),0) AS unread FROM users u ORDER BY u.full_name ASC");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($all as $a){ echo "user_id={$a['user_id']} unread={$a['unread']} name={$a['full_name']}\n"; }

echo "\nDone.\n";
