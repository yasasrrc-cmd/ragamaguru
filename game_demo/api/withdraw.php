<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $amount = $data['amount'] ?? 0;
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT balance FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($amount <= 0 || $amount > $user['balance']) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    $conn->beginTransaction();
    
    try {
        $query = "UPDATE users SET balance = balance - :amount WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $query = "INSERT INTO transactions (user_id, type, amount, status) VALUES (:user_id, 'withdrawal', :amount, 'completed')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':amount', $amount);
        $stmt->execute();
        
        $new_balance = $user['balance'] - $amount;
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'balance' => $new_balance]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Transaction failed']);
    }
}
?>