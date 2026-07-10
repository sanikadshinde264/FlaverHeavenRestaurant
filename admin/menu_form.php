<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

$item = null;
if (isset($_GET['id'])) {
  $stmt = $conn->prepare('SELECT * FROM menu_items WHERE id = ?');
  $stmt->bind_param('i', $id = (int)$_GET['id']);
  $stmt->execute();
  $item = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $category = trim($_POST['category'] ?? '');
  $type = trim($_POST['type'] ?? 'veg');
  $image = trim($_POST['image'] ?? '');
  $available = isset($_POST['available']) ? 1 : 0;
  $id = (int)($_POST['id'] ?? 0);

  if ($id > 0) {
    $stmt = $conn->prepare('UPDATE menu_items SET name=?, description=?, price=?, category=?, type=?, image=?, available=? WHERE id=?');
    $stmt->bind_param('ssdsssi', $name, $description, $price, $category, $type, $image, $available, $id);
  } else {
    $stmt = $conn->prepare('INSERT INTO menu_items (name, description, price, category, type, image, available) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssdsssi', $name, $description, $price, $category, $type, $image, $available);
  }
  $stmt->execute();
  $stmt->close();
  header('Location: menu.php');
  exit;
}
?>
<?php include 'menu_form.html'; ?>