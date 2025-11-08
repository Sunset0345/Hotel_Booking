<?php
try{
    $pdo=new PDO('mysql:host=127.0.0.1;dbname=hotel_booking;charset=utf8mb4','admin','admin');
    $stmt=$pdo->prepare("SELECT user_id, full_name, email, LENGTH(password) as len FROM users LIMIT 20");
    $stmt->execute();
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    if($rows){
        foreach($rows as $r){
            echo "id: {$r['user_id']} | name: {$r['full_name']} | email: {$r['email']} | pw_len: {$r['len']}\n";
        }
    } else {
        echo "NO USERS FOUND\n";
    }
}catch(Exception $e){
    echo "ERROR: " . $e->getMessage() . "\n";
}
