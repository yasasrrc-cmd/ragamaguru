<?php
require_once 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$mobile = isset($data['mobile']) ? cleanInput($data['mobile']) : '';
$otp = isset($data['otp']) ? cleanInput($data['otp']) : '';

if (empty($mobile) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Mobile and OTP required']);
    exit();
}

// Verify OTP
$stmt = $conn->prepare("SELECT id, verification_code, verification_expiry FROM customers WHERE mobile = ?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Customer not found']);
    exit();
}

$customer = $result->fetch_assoc();

// Check if OTP expired
if (strtotime($customer['verification_expiry']) < time()) {
    echo json_encode(['success' => false, 'message' => 'OTP expired. Please request a new code.']);
    exit();
}

// Verify OTP
if ($customer['verification_code'] === $otp) {
    // Mark mobile as verified
    $stmt = $conn->prepare("UPDATE customers SET mobile_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $customer['id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Mobile verified successfully',
        'customer_id' => $customer['id']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
}
?>