<?php
require_once 'config.php';
header('Content-Type: application/json');

$date = isset($_GET['date']) ? cleanInput($_GET['date']) : '';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if (empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Date required']);
    exit();
}

// Get day of week (0=Sunday, 6=Saturday)
$day_of_week = date('w', strtotime($date));

// Check if day is open
$hours = $conn->query("SELECT start_time, end_time, is_open FROM business_hours WHERE day_of_week = $day_of_week")->fetch_assoc();

if (!$hours || !$hours['is_open']) {
    echo json_encode(['success' => true, 'slots' => []]);
    exit();
}

// Check if whole day is blocked
$blocked_check = $conn->prepare("SELECT id FROM blocked_slots WHERE block_date = ? AND start_time IS NULL");
$blocked_check->bind_param("s", $date);
$blocked_check->execute();
if ($blocked_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => true, 'slots' => []]);
    exit();
}

// Get slot duration from settings
$slot_duration = $conn->query("SELECT setting_value FROM booking_settings WHERE setting_key = 'slot_duration'")->fetch_assoc()['setting_value'];

// Get existing appointments for this date
$appointments = [];
$result = $conn->query("SELECT appointment_time FROM appointments WHERE appointment_date = '$date' AND status != 'cancelled'");
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row['appointment_time'];
}

// Get blocked time slots for this date
$blocked_times = [];
$result = $conn->query("SELECT start_time, end_time FROM blocked_slots WHERE block_date = '$date' AND start_time IS NOT NULL");
while ($row = $result->fetch_assoc()) {
    $blocked_times[] = ['start' => $row['start_time'], 'end' => $row['end_time']];
}

// Generate time slots
$slots = [];
$start = strtotime($hours['start_time']);
$end = strtotime($hours['end_time']);
$current_time = time();
$selected_date_time = strtotime($date);

while ($start < $end) {
    $time = date('H:i:s', $start);
    $time_display = date('h:i A', $start);
    
    // Check if slot is available
    $available = true;
    
    // Don't allow booking in the past
    $slot_datetime = $selected_date_time + ($start - strtotime('today'));
    if ($slot_datetime <= $current_time) {
        $available = false;
    }
    
    // Check if already booked
    if (in_array($time, $appointments)) {
        $available = false;
    }
    
    // Check if blocked
    foreach ($blocked_times as $blocked) {
        if ($time >= $blocked['start'] && $time < $blocked['end']) {
            $available = false;
            break;
        }
    }
    
    $slots[] = [
        'time' => $time_display,
        'time_24' => $time,
        'available' => $available
    ];
    
    $start += ($slot_duration * 60);
}

echo json_encode([
    'success' => true,
    'slots' => $slots,
    'date' => $date
]);
?>