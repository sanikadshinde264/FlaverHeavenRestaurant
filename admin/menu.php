<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
  $id = (int)($_POST['item_id'] ?? 0);
  if ($id > 0) {
    $stmt = $conn->prepare('DELETE FROM menu_items WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
  }
  header('Location: menu.php');
  exit;
}

$stmt = $conn->query('SELECT * FROM menu_items ORDER BY id DESC');
$items = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'menu.html'; ?>