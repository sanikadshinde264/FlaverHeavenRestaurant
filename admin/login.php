<?php
session_start();
require_once __DIR__ . '/../api/config.php';

// 1. Redirect if already logged in (only when just visiting the page,
//    not when submitting a fresh login attempt via POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? 'user') === 'admin') {
        header('Location: dashboard.php');
        exit;
    }
    header('Location: ../index.html');
    exit;
}

$error = '';

// 2. Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username/email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, email, password_hash, role FROM users WHERE (username = ? OR email = ?) AND role = ? LIMIT 1');
        $role = 'admin';
        $stmt->bind_param('sss', $username, $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['user_id'] = (int)$admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = $admin['role'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Invalid admin credentials.';
    }
}

// 3. Load the HTML View
include 'login_view.html';
?>