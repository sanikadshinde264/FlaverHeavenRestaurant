<?php
require_once __DIR__ . '/api/config.php';

// For user-facing pages we prefer a browser redirect when not authenticated
// instead of the API-style JSON error returned by `requireLogin()`.
if (empty($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}

$userId = (int) $_SESSION['user_id'];

/**
 * Format a DB TIME/DATETIME value (24-hour, e.g. "18:30:00") into a
 * 12-hour clock string with AM/PM (e.g. "6:30 PM"). If the value is
 * empty, already contains AM/PM, or can't be parsed, it is returned as-is.
 */
function formatTime12hr($time) {
  $time = trim((string) $time);
  if ($time === '') {
    return '';
  }
  if (preg_match('/am|pm/i', $time)) {
    return $time;
  }
  $timestamp = strtotime($time);
  if ($timestamp === false) {
    return $time;
  }
  return date('g:i A', $timestamp);
}

/**
 * Format a DB DATE + TIME (or DATETIME) pair into "Mon d, Y g:i A".
 * Falls back gracefully if only a date or only a datetime is available.
 */
function formatDateTime12hr($date, $time = '') {
  $date = trim((string) $date);
  $time = trim((string) $time);
  if ($date === '') {
    return '';
  }
  $combined = $time !== '' ? ($date . ' ' . $time) : $date;
  $timestamp = strtotime($combined);
  if ($timestamp === false) {
    return $combined;
  }
  return date('M j, Y', $timestamp) . ($time !== '' ? ', ' . date('g:i A', $timestamp) : '');
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

$stmt = $conn->prepare('SELECT id, customer_name, total_amount, payment_method, delivery_date, delivery_time, items_json, COALESCE(order_status, "pending") AS order_status, COALESCE(payment_status, "pending") AS payment_status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare('SELECT id, name, reservation_date, reservation_time, guests, event_type, COALESCE(status, "pending") AS status, COALESCE(payment_method, "") AS payment_method, COALESCE(payment_status, "pending") AS payment_status, payment_date, payment_time, created_at FROM reservations WHERE user_id = ? ORDER BY id DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Payment History: LEFT JOIN back to orders/reservations so we can show what
// each payment was actually for — the food items ordered, or the occasion
// name for a table reservation — instead of just a generic payment type.
$stmt = $conn->prepare('SELECT p.id, p.amount, p.payment_method, COALESCE(p.payment_status, "pending") AS payment_status, p.payment_date, p.payment_time, p.payment_type, p.created_at, o.items_json AS order_items_json, r.event_type AS reservation_event_type FROM payments p LEFT JOIN orders o ON p.order_id = o.id LEFT JOIN reservations r ON p.reservation_id = r.id WHERE p.user_id = ? ORDER BY p.id DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile | Flaver Heaven</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body{background:#060606;color:#f8f3e8;font-family:Montserrat,sans-serif;}
    .profile-shell{max-width:1200px;margin:40px auto;padding:24px;}
    .profile-card{background:rgba(255,255,255,0.04);border:1px solid rgba(212,175,55,0.25);border-radius:18px;padding:24px;margin-bottom:20px;transition:transform .2s ease, border-color .2s ease, box-shadow .2s ease, background .2s ease;}
    .profile-card:hover{transform:translateY(-3px);border-color:#d4af37;box-shadow:0 10px 25px rgba(212,175,55,0.15);background:rgba(255,255,255,0.07);}
    .profile-grid{display:grid;grid-template-columns:1fr;gap:20px;}
    table{width:100%;border-collapse:collapse;margin-top:12px;}
    th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.1);text-align:left;}
    th{color:#d4af37;}
    .pill{display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(212,175,55,0.15);color:#d4af37;font-size:12px;text-transform:capitalize;}
    .pill.pending{background:rgba(255,193,7,0.16);color:#ffd54f;}
    .pill.approved{background:rgba(76,175,80,0.18);color:#81c784;}
    .pill.paid{background:rgba(76,175,80,0.18);color:#81c784;}
    .pill.failed{background:rgba(244,67,54,0.16);color:#ef9a9a;}
    .pill.not-available{background:rgba(244,67,54,0.16);color:#ef9a9a;}
    a{color:#d4af37;text-decoration:none;}
    .logout-btn{display:inline-block;padding:8px 14px;border:1px solid rgba(212,175,55,0.35);border-radius:999px;background:rgba(212,175,55,0.12);color:#d4af37;font-weight:600;}
    .logout-btn:hover{background:rgba(212,175,55,0.22);} 
    @media (max-width: 768px){.profile-grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>
  <nav style="padding:20px 24px;background:#050505;border-bottom:1px solid rgba(255,255,255,0.08);">
    <a href="index.html" style="color:#d4af37;text-decoration:none;font-weight:700;">← Back to Home</a>
    <a href="logout.php" class="logout-btn" style="float:right;">Logout</a>
  </nav>
  <div class="profile-shell">
    <div class="profile-card">
      <h1 style="color:#d4af37;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
      <p>Your personal dining dashboard and activity history.</p>
    </div>
    <div class="profile-grid">
      <div class="profile-card">
        <h3>My Orders</h3>
        <?php if (empty($orders)): ?>
          <p>No orders yet.</p>
        <?php else: ?>
          <table>
            <thead><tr><th>Id</th><th>Food Items</th><th>Amount</th><th>Date</th><th>Method</th><th>Payment</th><th>Status</th><th>Delivery</th></tr></thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><?php echo (int)$order['id']; ?></td>
                  <td><?php echo htmlspecialchars(formatOrderItems($order['items_json'])); ?></td>
                  <td>₹<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                  <td><?php echo htmlspecialchars(formatDateTime12hr($order['created_at'])); ?></td>
                  <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                  <td><span class="pill <?php echo htmlspecialchars(strtolower($order['payment_status'] ?: 'pending')); ?>"><?php echo htmlspecialchars(ucfirst($order['payment_status'] ?: 'Pending')); ?></span></td>
                  <td><span class="pill"><?php echo htmlspecialchars($order['order_status'] ?: 'Pending'); ?></span></td>
                  <td><?php echo htmlspecialchars((($order['delivery_date'] ?: '—') . ' ' . formatTime12hr($order['delivery_time']))); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
      <div class="profile-card">
        <h3>My Reservations</h3>
        <?php if (empty($reservations)): ?>
          <p>No reservations yet.</p>
        <?php else: ?>
          <table>
            <thead><tr><th>Id</th><th>Occasion</th><th>Guests</th><th>Date</th><th>Time</th><th>Payment</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($reservations as $reservation): ?>
                <tr>
                  <td><?php echo (int)$reservation['id']; ?></td>
                  <td><?php echo htmlspecialchars($reservation['event_type'] ?: '—'); ?></td>
                  <td><?php echo (int)$reservation['guests']; ?></td>
                  <td><?php echo htmlspecialchars(formatDateTime12hr($reservation['reservation_date'])); ?></td>
                  <td><?php echo htmlspecialchars(formatTime12hr($reservation['reservation_time'])); ?></td>
                  <td><?php echo htmlspecialchars($reservation['payment_method'] ?: 'Pending'); ?></td>
                  <?php $reservationStatus = $reservation['status'] ?: 'pending'; ?>
                  <td><span class="pill <?php echo htmlspecialchars(str_replace(' ', '-', strtolower($reservationStatus))); ?>"><?php echo htmlspecialchars(ucwords($reservationStatus)); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
    <div class="profile-card">
      <h3>Payment History</h3>
      <?php if (empty($payments)): ?>
        <p>No payment history yet.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Id</th><th>For</th><th>Amount</th><th>Date</th><th>Method</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($payments as $payment):
              // Work out what this payment was actually for: food items from
              // the linked order, or the occasion name from the linked
              // reservation, falling back to the generic payment_type.
              if (!empty($payment['order_items_json'])) {
                $paymentFor = 'Food Order: ' . formatOrderItems($payment['order_items_json']);
              } elseif (!empty($payment['reservation_event_type'])) {
                $paymentFor = 'Reservation: ' . $payment['reservation_event_type'];
              } else {
                $paymentFor = ucwords(str_replace('_', ' ', $payment['payment_type'] ?: 'Payment'));
              }
            ?>
              <tr>
                <td><?php echo (int)$payment['id']; ?></td>
                <td><?php echo htmlspecialchars($paymentFor); ?></td>
                <td>₹<?php echo number_format((float)$payment['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($payment['payment_date'] ? formatDateTime12hr($payment['payment_date'], $payment['payment_time']) : formatDateTime12hr($payment['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                <td><span class="pill <?php echo htmlspecialchars(strtolower($payment['payment_status'] ?: 'pending')); ?>"><?php echo htmlspecialchars($payment['payment_status'] ?: 'Pending'); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>