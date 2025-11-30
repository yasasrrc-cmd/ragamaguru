<?php
require_once 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$customer_id = isset($data['customer_id']) ? intval($data['customer_id']) : 0;
$service_id = isset($data['service_id']) ? intval($data['service_id']) : 0;
$appointment_date = isset($data['appointment_date']) ? cleanInput($data['appointment_date']) : '';
$appointment_time = isset($data['appointment_time']) ? cleanInput($data['appointment_time']) : '';

// Validate inputs
if (!$customer_id || !$service_id || !$appointment_date || !$appointment_time) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit();
}

// Convert 12-hour format to 24-hour if needed
if (strpos($appointment_time, 'AM') !== false || strpos($appointment_time, 'PM') !== false) {
    $appointment_time = date('H:i:s', strtotime($appointment_time));
}

// Check if customer is verified
$customer = $conn->query("SELECT mobile_verified, mobile, name FROM customers WHERE id = $customer_id")->fetch_assoc();
if (!$customer || !$customer['mobile_verified']) {
    echo json_encode(['success' => false, 'message' => 'Mobile not verified']);
    exit();
}

// Check if slot is still available
$check = $conn->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
$check->bind_param("ss", $appointment_date, $appointment_time);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This time slot is no longer available']);
    exit();
}

// Get service details
$service = $conn->query("SELECT service_name, price FROM services WHERE id = $service_id")->fetch_assoc();

// Create appointment
$stmt = $conn->prepare("INSERT INTO appointments (customer_id, service_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'confirmed')");
$stmt->bind_param("iiss", $customer_id, $service_id, $appointment_date, $appointment_time);

if ($stmt->execute()) {
    $appointment_id = $conn->insert_id;
    
    // Send confirmation SMS
    $date_formatted = date('d M Y', strtotime($appointment_date));
    $time_formatted = date('h:i A', strtotime($appointment_time));
    $message = "Hi {$customer['name']}, your appointment at Ragamaguru is confirmed for {$date_formatted} at {$time_formatted} for {$service['service_name']}. Thank you!";
    
    sendSMS($customer['mobile'], $message);
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment confirmed successfully',
        'appointment_id' => $appointment_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating appointment: ' . $conn->error]);
}
?>