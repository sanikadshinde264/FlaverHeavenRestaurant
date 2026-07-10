<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {
  $paymentId = (int)($_POST['payment_id'] ?? 0);
  $status = trim($_POST['payment_status'] ?? 'unpaid');
  $allowed = ['paid', 'unpaid'];
  if ($paymentId > 0 && in_array($status, $allowed, true)) {
    $stmt = $conn->prepare('SELECT payment_type, order_id, reservation_id FROM payments WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $paymentRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($paymentRow) {
      $stmt = $conn->prepare('UPDATE payments SET payment_status = ? WHERE id = ?');
      $stmt->bind_param('si', $status, $paymentId);
      $stmt->execute();
      $stmt->close();

      if (($paymentRow['payment_type'] ?? 'food') === 'food' && (int)($paymentRow['order_id'] ?? 0) > 0) {
        $stmt = $conn->prepare('UPDATE orders SET payment_status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $paymentRow['order_id']);
        $stmt->execute();
        $stmt->close();
      } elseif (($paymentRow['payment_type'] ?? 'table reservation') === 'table reservation' && (int)($paymentRow['reservation_id'] ?? 0) > 0) {
        $stmt = $conn->prepare('UPDATE reservations SET payment_status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $paymentRow['reservation_id']);
        $stmt->execute();
        $stmt->close();
      }
    }
  }
  header('Location: payments.php');
  exit;
}

$paymentsResult = $conn->query("SELECT p.id, p.user_id, p.order_id, p.reservation_id, p.amount, p.payment_method, p.payment_status, p.payment_type, p.payment_date, p.payment_time, p.created_at, u.username, u.email FROM payments p LEFT JOIN users u ON u.id = p.user_id ORDER BY p.id DESC");
$rows = [];
while ($row = $paymentsResult->fetch_assoc()) {
  $rows[] = [
    'payment_id' => (int) $row['id'],
    'user_id' => (int) $row['user_id'],
    'username' => $row['username'] ?? 'Guest',
    'email' => $row['email'] ?? '',
    'amount' => (float) $row['amount'],
    'payment_method' => $row['payment_method'] ?? '—',
    'payment_status' => $row['payment_status'] ?? 'unpaid',
    'payment_type' => $row['payment_type'] ?? 'food',
    'payment_date' => $row['payment_date'] ?? null,
    'payment_time' => $row['payment_time'] ?? null,
    'created_at' => $row['created_at'] ?? null,
  ];
}
?>
<?php include 'payments.html'; ?>
