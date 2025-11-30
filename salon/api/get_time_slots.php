<?php
require_once '../config.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit;
}

try {
    // Check if date is blocked
    $stmt = $conn->prepare("SELECT id FROM blocked_dates WHERE block_date = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This date is not available', 'slots' => []]);
        exit;
    }
    
    // Get day of week
    $day_of_week = date('l', strtotime($date));
    
    // Get availability for this day
    $stmt = $conn->prepare("
        SELECT start_time, end_time, is_available 
        FROM availability 
        WHERE day_of_week = ?
    ");
    $stmt->bind_param("s", $day_of_week);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || !$result->fetch_assoc()['is_available']) {
        echo json_encode(['success' => false, 'message' => 'Salon is closed on this day', 'slots' => []]);
        exit;
    }
    
    $result->data_seek(0);
    $availability = $result->fetch_assoc();
    
    // Generate time slots (30-minute intervals)
    $slots = [];
    $start = strtotime($availability['start_time']);
    $end = strtotime($availability['end_time']);
    $interval = 30 * 60; // 30 minutes
    
    for ($time = $start; $time < $end; $time += $interval) {
        $slot_time = date('H:i:s', $time);
        
        // Check if slot is blocked
        $stmt = $conn->prepare("
            SELECT id FROM blocked_time_slots 
            WHERE block_date = ? 
            AND start_time <= ? 
            AND end_time > ?
        ");
        $stmt->bind_param("sss", $date, $slot_time, $slot_time);
        $stmt->execute();
        $blocked_result = $stmt->get_result();
        
        if ($blocked_result->num_rows > 0) {
            continue;
        }
        
        // Check if slot is already booked
        $stmt = $conn->prepare("
            SELECT id FROM appointments 
            WHERE appointment_date = ? 
            AND appointment_time = ? 
            AND status NOT IN ('cancelled')
        ");
        $stmt->bind_param("ss", $date, $slot_time);
        $stmt->execute();
        $booked_result = $stmt->get_result();
        
        if ($booked_result->num_rows > 0) {
            continue;
        }
        
        // If current date, check if time has passed
        if ($date === date('Y-m-d')) {
            $current_time = time();
            if ($time <= $current_time) {
                continue;
            }
        }
        
        $slots[] = $slot_time;
    }
    
    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'date' => $date
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>