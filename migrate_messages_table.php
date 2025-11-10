<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hotel_booking;charset=utf8mb4', 'admin', 'admin');

try {
    // Add is_read column if it doesn't exist
    echo "Adding 'is_read' column to messages table...\n";
    $pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0");
    echo "  ✓ is_read column added\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "  - is_read column already exists\n";
    } else {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
}

try {
    // Add date_sent column if it doesn't exist
    echo "Adding 'date_sent' column to messages table...\n";
    $pdo->exec("ALTER TABLE messages ADD COLUMN date_sent TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "  ✓ date_sent column added\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "  - date_sent column already exists\n";
    } else {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
}

// Show updated schema
echo "\nUpdated messages table schema:\n";
$stmt = $pdo->query("DESCRIBE messages");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\nDone!\n";
?>
