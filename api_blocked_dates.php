<?php
require_once 'config.php';
header('Content-Type: application/json');

// Get blocked dates (whole day blocks)
$blocked_dates = [];
$result = $conn->query("SELECT block_date FROM blocked_slots WHERE start_time IS NULL GROUP BY block_date");
while ($row = $result->fetch_assoc()) {
    $blocked_dates[] = $row['block_date'];
}

// Get blocked time slots
$blocked_slots = [];
$result = $conn->query("SELECT block_date, start_time, end_time FROM blocked_slots WHERE start_time IS NOT NULL");
while ($row = $result->fetch_assoc()) {
    $blocked_slots[] = [
        'date' => $row['block_date'],
        'start' => $row['start_time'],
        'end' => $row['end_time']
    ];
}

echo json_encode([
    'success' => true,
    'blocked_dates' => $blocked_dates,
    'blocked_slots' => $blocked_slots
]);
?>