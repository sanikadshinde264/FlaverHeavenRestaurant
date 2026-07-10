<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Standalone MySQL Server (managed via MySQL Workbench) connection settings.
// - host: '127.0.0.1' (or '::1'/'localhost') if MySQL Server runs on this same
//   machine; use its LAN/hostname if it runs elsewhere.
// - port: MySQL Workbench installs default to 3306. Only change this if you
//   picked a different port during setup (check Workbench > Server Status).
// - user/pass: the root password you set when installing MySQL Server (XAMPP's
//   MySQL has no root password by default - standalone MySQL always requires one).
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = 'root'; // <-- replace with your actual MySQL Workbench root password
$dbName = 'flaverheaven';

try {
    $conn = new mysqli($host, $user, $pass, $dbName, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

function sendJson($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        sendJson(false, 'Please login first to continue.');
    }
}