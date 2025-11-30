<?php
require_once 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$name = isset($data['name']) ? cleanInput($data['name']) : '';
$mobile = isset($data['mobile']) ? cleanInput($data['mobile']) : '';
$dob = isset($data['dob']) ? cleanInput($data['dob']) : null;
$address = isset($data['address']) ? cleanInput($data['address']) : '';

// Validate mobile number
if (!preg_match('/^947\d{8}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number format']);
    exit();
}

// Generate 6-digit OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Check if customer exists
$stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing customer
    $customer = $result->fetch_assoc();
    $customer_id = $customer['id'];
    
    $stmt = $conn->prepare("UPDATE customers SET name = ?, dob = ?, address = ?, verification_code = ?, verification_expiry = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $dob, $address, $otp, $expiry, $customer_id);
    $stmt->execute();
} else {
    // Create new customer
    $stmt = $conn->prepare("INSERT INTO customers (name, mobile, dob, address, verification_code, verification_expiry, mobile_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssssss", $name, $mobile, $dob, $address, $otp, $expiry);
    $stmt->execute();
    $customer_id = $conn->insert_id;
}

// Send SMS
$message = "Your Ragamaguru verification code is: $otp. Valid for 10 minutes.";
$message2 = urlencode($message);
$sms_sent = sendSMS($mobile, $message2);

if ($sms_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully',
        'customer_id' => $customer_id
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Customer registered. OTP: ' . $otp . ' (SMS service unavailable)',
        'customer_id' => $customer_id,
        'otp' => $otp // Only for testing when SMS fails
    ]);
}
?>