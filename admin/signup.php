<?php
session_start();
require_once __DIR__ . '/../api/config.php';

// 1. Redirect if already logged in (only when just visiting the page,
//    not when submitting a fresh signup attempt via POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? 'user') === 'admin') {
        header('Location: dashboard.php');
        exit;
    }
    header('Location: ../index.html');
    exit;
}

$error = '';
$oldUsername = '';

// 2. Handle Signup Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($oldUsername === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $oldUsername);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = 'That username is already taken.';
        } else {
            // The users table requires a unique, non-null email, but the admin
            // signup form only asks for a username, so a placeholder email is
            // generated from the username instead.
            $email = $oldUsername . '@admin.flaverheaven.local';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin';
            $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->bind_param('ssss', $oldUsername, $email, $hash, $role);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['username'] = $oldUsername;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'admin';

            header('Location: dashboard.php');
            exit;
        }
    }
}

// 3. Load the HTML View
include 'signup_view.html';
?>