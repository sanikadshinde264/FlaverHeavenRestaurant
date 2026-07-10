<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 'user'
        ]
    ]);
} else {
    echo json_encode(['loggedIn' => false, 'user' => null]);
}
