<?php
require_once __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = strtolower($input['action'] ?? 'login');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$username && !$email) {
    http_response_code(400);
    sendJson(false, 'Username or email is required.');
}

if (strlen($password) < 4) {
    http_response_code(400);
    sendJson(false, 'Password must be at least 4 characters long.');
}

$lookup = $username ?: $email;
$stmt = $conn->prepare('SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $lookup, $lookup);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($action === 'register') {
    if ($user) {
        http_response_code(409);
        sendJson(false, 'A user with that username or email already exists.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
    $role = 'user';
    $stmt->bind_param('ssss', $username, $email, $hash, $role);
    $stmt->execute();
    $stmt->close();

    $userId = $conn->insert_id;
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user';

    sendJson(true, 'Account created successfully. Welcome to Flavor Haven!', [
        'user' => ['id' => $userId, 'username' => $username, 'email' => $email, 'role' => 'user']
    ]);
}

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    sendJson(false, 'Invalid username/email or password.');
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

sendJson(true, 'Login successful. Redirecting to your dashboard.', [
    'user' => ['id' => $user['id'], 'username' => $user['username'], 'email' => $user['email'], 'role' => $user['role']]
]);
