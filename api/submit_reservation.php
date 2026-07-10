<?php
require_once __DIR__ . '/config.php';

function formatReservationTimeValue($timeValue) {
    $timeValue = trim((string) $timeValue);
    if ($timeValue === '') {
        return '';
    }

    if (preg_match('/(am|pm)/i', $timeValue)) {
        return $timeValue;
    }

    if (!preg_match('/^\d{1,2}:\d{2}$/', $timeValue)) {
        return $timeValue;
    }

    [$hours, $minutes] = array_map('intval', explode(':', $timeValue));
    $suffix = $hours >= 12 ? 'PM' : 'AM';
    $hour12 = $hours % 12;
    if ($hour12 === 0) {
        $hour12 = 12;
    }

    return sprintf('%d:%02d %s', $hour12, $minutes, $suffix);
}

function normalizeReservationTimeValue($timeValue, $startTime = '', $endTime = '') {
    $timeValue = trim((string) $timeValue);
    $startTime = trim((string) $startTime);
    $endTime = trim((string) $endTime);

    if ($startTime !== '' && $endTime !== '') {
        return formatReservationTimeValue($startTime) . ' to ' . formatReservationTimeValue($endTime);
    }

    if ($timeValue === '') {
        return '';
    }

    if (preg_match('/(am|pm)/i', $timeValue)) {
        return $timeValue;
    }

    if (preg_match('/^(.*?)\s+to\s+(.*?)$/i', $timeValue, $matches)) {
        return formatReservationTimeValue($matches[1]) . ' to ' . formatReservationTimeValue($matches[2]);
    }

    return formatReservationTimeValue($timeValue);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$reservationDate = trim($input['reservation_date'] ?? '');
$reservationTime = normalizeReservationTimeValue(
    $input['reservation_time'] ?? '',
    $input['start_time'] ?? $input['startTime'] ?? '',
    $input['end_time'] ?? $input['endTime'] ?? ''
);
$guests = (int) ($input['guests'] ?? 0);
$eventType = trim((string) ($input['event_type'] ?? ''));
$paymentMethod = trim((string) ($input['payment_method'] ?? ''));
$paymentDate = trim((string) ($input['payment_date'] ?? ''));
$paymentTime = trim((string) ($input['payment_time'] ?? ''));
$paymentFor = trim((string) ($input['payment_for'] ?? ''));
$paymentConfirmed = !empty($input['payment_confirmed']);
$paymentStatus = strcasecmp($paymentMethod, 'Cash On Delivery') === 0 ? 'unpaid' : ($paymentConfirmed ? 'paid' : 'unpaid');
$paymentType = $paymentFor !== '' ? $paymentFor : 'table reservation';
$amount = (float) ($input['amount'] ?? 0);

if (!$name || !$email || !$phone || !$reservationDate || !$reservationTime || $guests < 1 || !$eventType) {
    http_response_code(400);
    sendJson(false, 'Please fill in all reservation details correctly.');
}

$userId = null;
if (!empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
} else {
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
        $userId = (int)$existing['id'];
    }
}

$stmt = $conn->prepare('INSERT INTO reservations (user_id, name, email, phone, reservation_date, reservation_time, guests, event_type, payment_method, payment_status, payment_date, payment_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->bind_param('isssssisssss', $userId, $name, $email, $phone, $reservationDate, $reservationTime, $guests, $eventType, $paymentMethod, $paymentStatus, $paymentDate, $paymentTime);
$stmt->execute();
$reservationId = $conn->insert_id;
$stmt->close();

// order_id is nullable and has a foreign key to orders(id), so a
// reservation-only payment (no linked order) must use NULL here, not 0 —
// there is no orders row with id 0, so 0 would violate the FK constraint.
$orderId = null;
$paymentStmt = $conn->prepare('INSERT INTO payments (user_id, order_id, reservation_id, amount, payment_method, payment_status, payment_type, payment_date, payment_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$paymentStmt->bind_param('iiidsssss', $userId, $orderId, $reservationId, $amount, $paymentMethod, $paymentStatus, $paymentType, $paymentDate, $paymentTime);
$paymentStmt->execute();
$paymentStmt->close();

sendJson(true, 'Table reservation saved successfully. We will confirm it shortly.', [
    'reservation_id' => $reservationId,
    'payment_status' => $paymentStatus
]);