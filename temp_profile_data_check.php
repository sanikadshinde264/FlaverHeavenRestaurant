<?php
$conn = new mysqli('127.0.0.1', 'root', 'root', 'flaverheaven', 3306);
if ($conn->connect_error) {
    echo 'ERROR: '.$conn->connect_error.PHP_EOL;
    exit(1);
}
$res = $conn->query('SELECT id, username, email, role FROM users ORDER BY id');
while ($row = $res->fetch_assoc()) {
    echo 'USER: '.json_encode($row).PHP_EOL;
}
$res = $conn->query('SELECT id, user_id, name, reservation_date, reservation_time, status, payment_status FROM reservations ORDER BY id');
while ($row = $res->fetch_assoc()) {
    echo 'RES: '.json_encode($row).PHP_EOL;
}
$res = $conn->query('SELECT id, user_id, customer_name, total_amount, payment_method, payment_status, order_status, created_at FROM orders ORDER BY id');
while ($row = $res->fetch_assoc()) {
    echo 'ORD: '.json_encode($row).PHP_EOL;
}
$conn->close();
