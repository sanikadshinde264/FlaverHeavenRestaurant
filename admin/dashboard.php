<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

$stats = [];
$stats['users'] = $conn->query('SELECT COUNT(*) AS c FROM users')->fetch_assoc()['c'];
$stats['orders'] = $conn->query('SELECT COUNT(*) AS c FROM orders')->fetch_assoc()['c'];
$stats['today_orders'] = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$stats['pending_orders'] = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'pending'")->fetch_assoc()['c'];
$stats['completed_orders'] = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'delivered'")->fetch_assoc()['c'];
$stats['reservations'] = $conn->query('SELECT COUNT(*) AS c FROM reservations')->fetch_assoc()['c'];
$stats['revenue'] = $conn->query('SELECT COALESCE(SUM(total_amount),0) AS c FROM orders WHERE payment_status = "paid"')->fetch_assoc()['c'];

$recent = $conn->query('SELECT o.id, u.username, o.total_amount, o.order_status, o.created_at FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 8');
$recentOrders = $recent->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'dashboard.html'; ?>