<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $id = (int)($_POST['reservation_id'] ?? 0);
  $status = trim($_POST['status'] ?? 'pending');
  if ($id > 0) {
    $stmt = $conn->prepare('UPDATE reservations SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();
  }
  header('Location: reservations.php');
  exit;
}

/**
 * Format a DB DATE + TIME/TIME-RANGE pair into a readable string.
 * reservation_time is already stored as e.g. "6:00 PM to 8:00 PM", so it's
 * shown as-is; only the date is prettified.
 */
function formatReservationDate($date) {
  $date = trim((string) $date);
  if ($date === '') {
    return '—';
  }
  $timestamp = strtotime($date);
  if ($timestamp === false) {
    return $date;
  }
  return date('M j, Y', $timestamp);
}

$search = trim($_GET['search'] ?? '');
$query = 'SELECT r.*, u.username FROM reservations r LEFT JOIN users u ON u.id = r.user_id';
if ($search !== '') {
  $query .= ' WHERE r.name LIKE ? OR u.username LIKE ?';
  $stmt = $conn->prepare($query . ' ORDER BY r.id DESC');
  $like = "%$search%";
  $stmt->bind_param('ss', $like, $like);
} else {
  $stmt = $conn->prepare($query . ' ORDER BY r.id DESC');
}
$stmt->execute();
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php include 'reservations.html'; ?>