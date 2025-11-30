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
    $bet_amount = $data['bet_amount'] ?? 0;
    $cashout_multiplier = $data['cashout_multiplier'] ?? null;
    $crash_point = $data['crash_point'] ?? 0;
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT balance FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bet_amount <= 0 || $bet_amount > $user['balance']) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        exit;
    }
    
    $conn->beginTransaction();
    
    try {
        $query = "UPDATE users SET balance = balance - :amount WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':amount', $bet_amount);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $win_amount = 0;
        $status = 'lost';
        
        if ($cashout_multiplier !== null && $cashout_multiplier <= $crash_point) {
            $win_amount = $bet_amount * $cashout_multiplier;
            $status = 'won';
            
            $query = "UPDATE users SET balance = balance + :amount WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':amount', $win_amount);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
        }
        
        $query = "INSERT INTO bets (user_id, bet_amount, cashout_multiplier, win_amount, game_crash_point, status) 
                  VALUES (:user_id, :bet_amount, :cashout, :win_amount, :crash_point, :status)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':bet_amount', $bet_amount);
        $stmt->bindParam(':cashout', $cashout_multiplier);
        $stmt->bindParam(':win_amount', $win_amount);
        $stmt->bindParam(':crash_point', $crash_point);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        $query = "SELECT balance FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'balance' => $updated_user['balance'],
            'win_amount' => $win_amount,
            'status' => $status
        ]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Bet failed']);
    }
}
?>