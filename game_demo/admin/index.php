<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

$query = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM bets";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_aviator_bets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM chicken_bets";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_chicken_bets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_deposits = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$query = "SELECT SUM(win_amount) as total FROM bets WHERE status = 'won'";
$stmt = $conn->prepare($query);
$stmt->execute();
$aviator_payouts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$query = "SELECT SUM(win_amount) as total FROM chicken_bets WHERE status = 'won'";
$stmt = $conn->prepare($query);
$stmt->execute();
$chicken_payouts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$total_payouts = $aviator_payouts + $chicken_payouts;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Aviator</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🎮 Admin Dashboard</h1>
            <div class="admin-nav">
                <a href="users.php" class="btn">Users</a>
                <a href="transactions.php" class="btn">Transactions</a>
                <a href="../game.php" class="btn btn-success">Aviator Game</a>
                <a href="../chicken-game.php" class="btn" style="background: #ff6b6b; color: white;">Chicken Run</a>
                <a href="../logout.php" class="btn">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_aviator_bets; ?></h3>
                <p>Aviator Bets</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_chicken_bets; ?></h3>
                <p>Chicken Bets</p>
            </div>
            <div class="stat-card">
                <h3>$<?php echo number_format($total_deposits, 2); ?></h3>
                <p>Total Deposits</p>
            </div>
            <div class="stat-card">
                <h3>$<?php echo number_format($aviator_payouts, 2); ?></h3>
                <p>Aviator Payouts</p>
            </div>
            <div class="stat-card">
                <h3>$<?php echo number_format($chicken_payouts, 2); ?></h3>
                <p>Chicken Payouts</p>
            </div>
        </div>
    </div>
</body>
</html>