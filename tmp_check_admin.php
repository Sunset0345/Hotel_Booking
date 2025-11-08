<?php
try{
    $pdo=new PDO('mysql:host=127.0.0.1;dbname=hotel_booking;charset=utf8mb4','admin','admin');
    $stmt=$pdo->prepare("SELECT username, password, LENGTH(password) as len FROM admin WHERE username='admin@admin.admin'");
    $stmt->execute();
    $r=$stmt->fetch(PDO::FETCH_ASSOC);
    if($r){
        echo "FOUND\n";
        echo "username: " . $r['username'] . "\n";
        echo "len: " . $r['len'] . "\n";
        echo "hash: " . $r['password'] . "\n";
    } else {
        echo "NOT FOUND\n";
    }
}catch(Exception $e){
    echo "ERROR: " . $e->getMessage() . "\n";
}
