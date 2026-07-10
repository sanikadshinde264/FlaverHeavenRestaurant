<?php
require_once __DIR__ . '/config.php';

function getOrCreateOrderUser($conn) {
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    $guestUsername = 'guest_' . time() . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
    $guestEmail = $guestUsername . '@guest.local';
    $guestPasswordHash = password_hash('guest12345', PASSWORD_DEFAULT);
    $role = 'user';

    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $guestUsername);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssss', $guestUsername, $guestEmail, $guestPasswordHash, $role);
        $stmt->execute();
        $userId = $conn->insert_id;
        $stmt->close();
    } else {
        $userId = (int) $existing['id'];
    }

    $_SESSION['user_id'] = $userId;
    return $userId;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$customerName = trim($input['customer_name'] ?? '');
$mobile = trim($input['mobile'] ?? '');
$address = trim($input['address'] ?? '');
$deliveryDate = trim($input['delivery_date'] ?? '');
$deliveryTime = trim($input['delivery_time'] ?? '');
$items = $input['items'] ?? [];
$specialInstructions = trim($input['special_instructions'] ?? '');
$paymentMethod = trim($input['payment_method'] ?? '');
$paymentDate = trim($input['payment_date'] ?? date('Y-m-d'));
$paymentTime = trim($input['payment_time'] ?? date('H:i:s'));
$totalAmount = (float) ($input['total_amount'] ?? 0);

if (!$customerName || !$mobile || !$address || !$deliveryDate || !$deliveryTime || !is_array($items) || empty($items) || !$paymentMethod) {
    http_response_code(400);
    sendJson(false, 'Please complete the order and payment details before placing the order.');
}

$itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);
$userId = getOrCreateOrderUser($conn);
$reservationId = null;
$paymentType = 'food';

// Cash On Delivery is only "paid" once the order is delivered (handled by the
// admin in Order/Payment Management). Card/UPI methods are only "paid" if the
// customer actually completed the card verification step on the payment form.
$paymentConfirmed = !empty($input['payment_confirmed']);
if (strcasecmp($paymentMethod, 'Cash On Delivery') === 0) {
    $paymentStatus = 'unpaid';
} else {
    $paymentStatus = $paymentConfirmed ? 'paid' : 'unpaid';
}

$stmt = $conn->prepare('INSERT INTO orders (user_id, customer_name, mobile, address, delivery_date, delivery_time, items_json, special_instructions, total_amount, payment_method, payment_status, payment_date, payment_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->bind_param('isssssssdssss', $userId, $customerName, $mobile, $address, $deliveryDate, $deliveryTime, $itemsJson, $specialInstructions, $totalAmount, $paymentMethod, $paymentStatus, $paymentDate, $paymentTime);
$stmt->execute();
$orderId = $conn->insert_id;
$stmt->close();

$paymentStmt = $conn->prepare('INSERT INTO payments (user_id, order_id, reservation_id, amount, payment_method, payment_status, payment_type, payment_date, payment_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$paymentStmt->bind_param('iiissssss', $userId, $orderId, $reservationId, $totalAmount, $paymentMethod, $paymentStatus, $paymentType, $paymentDate, $paymentTime);
$paymentStmt->execute();
$paymentStmt->close();

$message = $paymentStatus === 'paid'
    ? 'Order placed successfully. Your payment has been received.'
    : 'Order placed successfully. Payment is unpaid — ' . (strcasecmp($paymentMethod, 'Cash On Delivery') === 0 ? 'pay on delivery.' : 'please complete payment.');

sendJson(true, $message, [
    'order_id' => $orderId,
    'payment_status' => $paymentStatus
]);