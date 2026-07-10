<?php
$host = '127.0.0.1';
$user = 'root';
$pass = 'root';
$dbName = 'flaverheaven';
$port = 3306;
$conn = new mysqli($host, $user, $pass, $dbName, $port);
if ($conn->connect_error) {
    echo 'ERROR: ' . $conn->connect_error . PHP_EOL;
    exit(1);
}
$result = $conn->query("SELECT id, username, email, role, password_hash FROM users WHERE role = 'admin'");
if (!$result) {
    echo 'QUERY ERROR: ' . $conn->error . PHP_EOL;
    exit(1);
}
while ($row = $result->fetch_assoc()) {
    echo json_encode($row) . PHP_EOL;
}
$conn->close();
