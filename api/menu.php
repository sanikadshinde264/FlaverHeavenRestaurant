<?php
// Public endpoint: returns all available menu items from the database.
// No login required - this powers the menu section on the public site.
require_once __DIR__ . '/config.php';

$result = $conn->query('SELECT id, name, description, price, category, type, image, available FROM menu_items WHERE available = 1 ORDER BY category, id');
$items = $result->fetch_all(MYSQLI_ASSOC);

foreach ($items as &$item) {
    $item['id'] = (int) $item['id'];
    $item['price'] = (float) $item['price'];
    $item['available'] = (bool) $item['available'];
}

sendJson(true, 'Menu loaded.', ['items' => $items]);
