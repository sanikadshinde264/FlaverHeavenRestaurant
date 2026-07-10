<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $id = (int)($_POST['order_id'] ?? 0);
  $status = trim($_POST['status'] ?? 'pending');
  if ($id > 0) {
    $stmt = $conn->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();

    // Cash On Delivery orders only become "paid" once they're actually
    // delivered. Without this, marking an order Delivered left payment_status
    // stuck on "pending" forever in both `orders` and `payments`, so the
    // customer's profile kept showing an unpaid COD order even after delivery.
    if ($status === 'delivered') {
      $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND LOWER(payment_method) = 'cash on delivery' AND payment_status <> 'paid'");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $stmt->close();

      $stmt = $conn->prepare("UPDATE payments SET payment_status = 'paid' WHERE order_id = ? AND LOWER(payment_method) = 'cash on delivery' AND payment_status <> 'paid'");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $stmt->close();
    }
  }
  header('Location: orders.php');
  exit;
}

/**
 * Turn an order's items_json into a readable "Name (xQty), Name (xQty)" string.
 */
function formatOrderItems($itemsJson) {
  $items = json_decode((string) $itemsJson, true);
  if (!is_array($items) || empty($items)) {
    return '—';
  }
  $parts = [];
  foreach ($items as $item) {
    $name = trim((string) ($item['name'] ?? ''));
    if ($name === '') {
      continue;
    }
    $qty = (int) ($item['qty'] ?? 1);
    $parts[] = $qty > 1 ? ($name . ' (x' . $qty . ')') : $name;
  }
  return $parts ? implode(', ', $parts) : '—';
}

/**
 * Format a DB DATE + TIME pair into "Mon d, Y g:i A" (12-hour with AM/PM).
 * Falls back gracefully if only a date is available.
 */
function formatDateTime12hr($date, $time = '') {
  $date = trim((string) $date);
  $time = trim((string) $time);
  if ($date === '') {
    return '—';
  }
  $combined = $time !== '' ? ($date . ' ' . $time) : $date;
  $timestamp = strtotime($combined);
  if ($timestamp === false) {
    return $combined;
  }
  return date('M j, Y', $timestamp) . ($time !== '' ? ', ' . date('g:i A', $timestamp) : '');
}

$search = trim($_GET['search'] ?? '');
$query = 'SELECT o.id, o.user_id, o.customer_name, o.mobile, o.address, o.delivery_date, o.delivery_time, o.items_json, o.special_instructions, o.total_amount, o.payment_method, COALESCE(o.payment_status, "pending") AS payment_status, COALESCE(o.order_status, "pending") AS order_status, o.payment_date, o.payment_time, o.created_at, u.username FROM orders o LEFT JOIN users u ON u.id = o.user_id';
if ($search !== '') {
  $query .= ' WHERE o.customer_name LIKE ? OR u.username LIKE ?';
  $stmt = $conn->prepare($query . ' ORDER BY o.id DESC');
  $like = "%$search%";
  $stmt->bind_param('ss', $like, $like);
} else {
  $stmt = $conn->prepare($query . ' ORDER BY o.id DESC');
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php include 'orders.html'; ?>