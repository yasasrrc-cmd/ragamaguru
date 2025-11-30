<?php
require_once 'config.php';
header('Content-Type: application/json');

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$customer_id) {
    echo json_encode(['error' => 'Invalid customer ID']);
    exit();
}

// Get customer appointments
$appointments = $conn->query("
    SELECT a.*, s.service_name 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.customer_id = $customer_id
    ORDER BY a.appointment_date DESC
    LIMIT 5
");

$appointmentList = [];
while ($apt = $appointments->fetch_assoc()) {
    $appointmentList[] = $apt;
}

// Get last visit
$lastVisit = $conn->query("
    SELECT appointment_date FROM appointments 
    WHERE customer_id = $customer_id AND status = 'completed'
    ORDER BY appointment_date DESC LIMIT 1
")->fetch_assoc();

echo json_encode([
    'total' => count($appointmentList),
    'appointments' => $appointmentList,
    'last_visit' => $lastVisit ? formatDate($lastVisit['appointment_date']) : 'No previous visits'
]);
?>