<?php
require_once 'config.php';
header('Content-Type: application/json');

$category = isset($_GET['category']) ? cleanInput($_GET['category']) : '';

if (empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Category required']);
    exit();
}

// Get services for this category
$stmt = $conn->prepare("SELECT * FROM services WHERE category_type = ? AND active = 1 ORDER BY service_name ASC");
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode([
    'success' => true,
    'services' => $services,
    'category' => $category
]);
?>