<?php
$host='127.0.0.1';
$port=3306;
$user='root';
$pass='root';
$db='flaverheaven';

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error . PHP_EOL);
}

$conn->query('SET FOREIGN_KEY_CHECKS = 0');
$conn->query('TRUNCATE TABLE payments');
$conn->query('TRUNCATE TABLE reservations');
$conn->query('TRUNCATE TABLE orders');
$conn->query('TRUNCATE TABLE menu_items');
$conn->query('TRUNCATE TABLE users');
$conn->query('SET FOREIGN_KEY_CHECKS = 1');

$tables = ['users','orders','reservations','payments','menu_items'];
foreach ($tables as $table) {
    $countRes = $conn->query('SELECT COUNT(*) AS c FROM ' . $table);
    $count = $countRes->fetch_assoc()['c'];
    $statusRes = $conn->query('SHOW TABLE STATUS LIKE "' . $table . '"');
    $status = $statusRes->fetch_assoc();
    echo $table . ': rows=' . $count . ', auto_increment=' . $status['Auto_increment'] . PHP_EOL;
}

$conn->close();
