<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
  $searchLike = "%$search%";
  $stmt = $conn->prepare('SELECT id, username, email, role, created_at FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC');
  $stmt->bind_param('ss', $searchLike, $searchLike);
} else {
  $stmt = $conn->prepare('SELECT id, username, email, role, created_at FROM users ORDER BY id DESC');
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
  $id = (int)($_POST['user_id'] ?? 0);
  if ($id > 0) {
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ? AND role = ?');
    $role = 'user';
    $stmt->bind_param('is', $id, $role);
    $stmt->execute();
    $stmt->close();
  }
  header('Location: users.php' . ($search ? '?search=' . urlencode($search) : ''));
  exit;
}
?>
<?php include 'users.html'; ?>