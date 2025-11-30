<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT id, name, description, duration, price 
        FROM services 
        WHERE status = 'active' 
        ORDER BY name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>