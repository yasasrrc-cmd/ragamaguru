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
    $won = $data['won'] ?? false;
    $win_amount = $data['win_amount'] ?? 0;
    $multiplier = $data['multiplier'] ?? 1.0;
    $bones_collected = $data['bones_collected'] ?? 0;
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $conn->beginTransaction();
    
    try {
        // If won, add winnings to balance
        if ($won && $win_amount > 0) {
            $query = "UPDATE users SET balance = balance + :amount WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':amount', $win_amount);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            // Record win transaction
            $query = "INSERT INTO transactions (user_id, type, amount, status) VALUES (:user_id, 'bet_win', :amount, 'completed')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':amount', $win_amount);
            $stmt->execute();
        } else {
            // Record loss transaction
            $query = "INSERT INTO transactions (user_id, type, amount, status) VALUES (:user_id, 'bet_loss', :amount, 'completed')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':amount', $bet_amount);
            $stmt->execute();
        }
        
        // Record chicken game bet
        $status = $won ? 'won' : 'lost';
        $query = "INSERT INTO chicken_bets (user_id, bet_amount, multiplier, win_amount, bones_collected, status) 
                  VALUES (:user_id, :bet_amount, :multiplier, :win_amount, :bones_collected, :status)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':bet_amount', $bet_amount);
        $stmt->bindParam(':multiplier', $multiplier);
        $stmt->bindParam(':win_amount', $win_amount);
        $stmt->bindParam(':bones_collected', $bones_collected);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        // Get updated balance
        $query = "SELECT balance FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'balance' => $user['balance'],
            'win_amount' => $win_amount,
            'status' => $status
        ]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Bet failed: ' . $e->getMessage()]);
    }
}
?>