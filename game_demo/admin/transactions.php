<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

$query = "SELECT t.*, u.username FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC LIMIT 100";
$stmt = $conn->prepare($query);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transactions - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>💰 Transactions</h1>
            <a href="index.php" class="btn">Back to Dashboard</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transactions as $txn): ?>
                <tr>
                    <td><?php echo $txn['id']; ?></td>
                    <td><?php echo htmlspecialchars($txn['username']); ?></td>
                    <td><span class="badge badge-<?php echo $txn['type']; ?>"><?php echo ucfirst($txn['type']); ?></span></td>
                    <td>$<?php echo number_format($txn['amount'], 2); ?></td>
                    <td><span class="badge badge-<?php echo $txn['status']; ?>"><?php echo ucfirst($txn['status']); ?></span></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($txn['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>