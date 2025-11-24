<?php
// Local debug endpoint to inspect revenue sources: payments and bookings
// WARNING: Do NOT expose this on a public server. Intended for local development only.
defined('PREVENT_DIRECT_ACCESS') OR define('PREVENT_DIRECT_ACCESS', true);

require_once __DIR__ . '/../app/config/database.php';

try{
    $cfg = $database['main'];
    $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $cfg['driver'], $cfg['hostname'], $cfg['port'], $cfg['database'], $cfg['charset']);
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['status'=>'error','msg'=>'DB connection failed','detail'=>$e->getMessage()]);
    exit;
}

header('Content-Type: application/json');

try{
    // Sum of approved payments
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total_payments FROM payments WHERE payment_status = 'approved'");
    $stmt->execute();
    $paymentsRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPayments = isset($paymentsRow['total_payments']) ? (float)$paymentsRow['total_payments'] : 0.0;

    // Sum of bookings grouped by status
    $stmt2 = $pdo->prepare("SELECT COALESCE(LOWER(status),'') AS status, COALESCE(SUM(total_amount),0) AS total FROM bookings GROUP BY COALESCE(LOWER(status),'')");
    $stmt2->execute();
    $bookingsByStatus = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Sum of bookings for statuses that the dashboard falls back to (approved, completed)
    $stmt3 = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) AS fallback_total FROM bookings WHERE COALESCE(LOWER(status),'') IN ('approved','completed')");
    $stmt3->execute();
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $bookingsFallback = isset($row3['fallback_total']) ? (float)$row3['fallback_total'] : 0.0;

    // List bookings that are completed or ended (audit), and approved (dashboard fallback)
    $stmt4 = $pdo->prepare("SELECT booking_id, user_id, room_id, check_in, check_out, status, total_amount FROM bookings WHERE COALESCE(LOWER(status),'') IN ('completed','ended','approved') ORDER BY date_booked DESC");
    $stmt4->execute();
    $bookingsList = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'total_payments_approved' => $totalPayments,
        'bookings_fallback_total' => $bookingsFallback,
        'bookings_by_status' => $bookingsByStatus,
        'bookings_list' => $bookingsList
    ], JSON_PRETTY_PRINT);
    exit;
} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['status'=>'error','msg'=>'Query failed','detail'=>$e->getMessage()]);
    exit;
}

?>
