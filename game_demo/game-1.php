<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

$query = "SELECT balance FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Play Aviator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="game-container">
        <header>
            <h1>🛩️ Aviator</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="balance">Balance: $<span id="userBalance"><?php echo number_format($user['balance'], 2); ?></span></span>
                <button onclick="showWallet()" class="btn btn-small">Wallet</button>
                <a href="logout.php" class="btn btn-small">Logout</a>
            </div>
        </header>

        <div class="game-area">
            <canvas id="gameCanvas"></canvas>
            <div class="multiplier-display">
                <h2 id="multiplier">1.00x</h2>
                <p id="gameStatus">Waiting for next round...</p>
            </div>
        </div>

        <div class="betting-panel">
            <div class="bet-input">
                <label>Bet Amount:</label>
                <input type="number" id="betAmount" min="1" value="10" step="1">
                <button onclick="placeBet()" id="betButton" class="btn btn-primary">Place Bet</button>
                <button onclick="cashOut()" id="cashoutButton" class="btn btn-success" disabled>Cash Out</button>
            </div>
            <div class="auto-cashout">
                <label>Auto Cash Out:</label>
                <input type="number" id="autoCashout" min="1.1" step="0.1" value="2.0">
                <span>x</span>
            </div>
        </div>

        <div class="game-history">
            <h3>Recent Rounds</h3>
            <div id="history"></div>
        </div>
    </div>

    <div id="walletModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeWallet()">&times;</span>
            <h2>Wallet</h2>
            <div class="wallet-actions">
                <div>
                    <h3>Deposit</h3>
                    <input type="number" id="depositAmount" min="1" placeholder="Amount">
                    <button onclick="deposit()" class="btn btn-primary">Deposit</button>
                </div>
                <div>
                    <h3>Withdraw</h3>
                    <input type="number" id="withdrawAmount" min="1" placeholder="Amount">
                    <button onclick="withdraw()" class="btn">Withdraw</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/game.js"></script>
</body>
</html>