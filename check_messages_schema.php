<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hotel_booking;charset=utf8mb4', 'admin', 'admin');
$stmt = $pdo->query("DESCRIBE messages");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Messages table columns:\n";
foreach ($columns as $col) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")" . ($col['Null'] === 'NO' ? " NOT NULL" : " NULLABLE") . "\n";
}
?>
