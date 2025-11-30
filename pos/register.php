<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Check if register is already open
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$open_register = $stmt->fetch();

// Handle register opening
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'open' && !$open_register) {
        $opening_balance = floatval($_POST['opening_balance']);
        $stmt = $pdo->prepare("INSERT INTO cash_register (user_id, opening_balance) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $opening_balance]);
        header('Location: pos.php');
        exit;
    }
    
    if ($_POST['action'] === 'close' && $open_register) {
        $register_id = $open_register['id'];
        $closing_balance = floatval($_POST['closing_balance']);
        $notes = $_POST['notes'] ?? '';
        
        // Calculate expected balance
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as cash_sales 
            FROM sales 
            WHERE user_id = ? 
            AND payment_method = 'cash' 
            AND created_at >= ?
        ");
        $stmt->execute([$_SESSION['user_id'], $open_register['opened_at']]);
        $cash_sales = $stmt->fetch()['cash_sales'];
        
        // Get expenses during this register session
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as expenses 
            FROM expenses 
            WHERE user_id = ? 
            AND created_at >= ?
        ");
        $stmt->execute([$_SESSION['user_id'], $open_register['opened_at']]);
        $expenses = $stmt->fetch()['expenses'];
        
        $expected_balance = $open_register['opening_balance'] + $cash_sales - $expenses;
        $difference = $closing_balance - $expected_balance;
        
        $stmt = $pdo->prepare("
            UPDATE cash_register 
            SET closing_balance = ?, 
                expected_balance = ?, 
                difference = ?, 
                notes = ?, 
                closed_at = NOW(), 
                status = 'closed' 
            WHERE id = ?
        ");
        $stmt->execute([$closing_balance, $expected_balance, $difference, $notes, $register_id]);
        
        $success = "Register closed successfully";
        $open_register = null;
    }
}

// Get today's sales summary for current user
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END), 0) as cash_sales,
        COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total ELSE 0 END), 0) as card_sales,
        COALESCE(SUM(CASE WHEN payment_method NOT IN ('cash', 'card') THEN total ELSE 0 END), 0) as other_sales,
        COALESCE(SUM(total), 0) as total_sales
    FROM sales 
    WHERE user_id = ? AND DATE(created_at) = CURDATE()
");
$stmt->execute([$_SESSION['user_id']]);
$sales_summary = $stmt->fetch();

// Get today's expenses
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_expenses 
    FROM expenses 
    WHERE user_id = ? AND DATE(created_at) = CURDATE()
");
$stmt->execute([$_SESSION['user_id']]);
$total_expenses = $stmt->fetch()['total_expenses'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Register - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .register-icon {
            text-align: center;
            font-size: 80px;
            margin-bottom: 20px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 30px 0;
        }
        .summary-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }
        .summary-item h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .summary-item .amount {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="register-container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (!$open_register): ?>
            <!-- Open Register -->
            <div class="register-card">
                <div class="register-icon">🏦</div>
                <h1 style="text-align: center; margin-bottom: 10px;">Open Cash Register</h1>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">
                    Enter your starting cash amount to begin your shift
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="open">
                    
                    <div class="form-group">
                        <label>Opening Balance (Rs)</label>
                        <input type="number" name="opening_balance" step="0.01" min="0" required autofocus 
                               style="font-size: 24px; text-align: center;" placeholder="0.00">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Open Register & Start Shift
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Register Open - Show Summary and Close Option -->
            <div class="register-card">
                <div class="register-icon">✅</div>
                <h1 style="text-align: center; margin-bottom: 10px;">Register is Open</h1>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">
                    Opened at <?= date('h:i A', strtotime($open_register['opened_at'])) ?> with Rs <?= number_format($open_register['opening_balance'], 2) ?>
                </p>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <h4>Opening Balance</h4>
                        <div class="amount">Rs <?= number_format($open_register['opening_balance'], 2) ?></div>
                    </div>
                    <div class="summary-item">
                        <h4>Total Transactions</h4>
                        <div class="amount"><?= $sales_summary['total_transactions'] ?></div>
                    </div>
                    <div class="summary-item">
                        <h4>Cash Sales</h4>
                        <div class="amount">Rs <?= number_format($sales_summary['cash_sales'], 2) ?></div>
                    </div>
                    <div class="summary-item">
                        <h4>Card Sales</h4>
                        <div class="amount">Rs <?= number_format($sales_summary['card_sales'], 2) ?></div>
                    </div>
                    <div class="summary-item">
                        <h4>Other Sales</h4>
                        <div class="amount">Rs <?= number_format($sales_summary['other_sales'], 2) ?></div>
                    </div>
                    <div class="summary-item">
                        <h4>Total Expenses</h4>
                        <div class="amount" style="color: #dc3545;">Rs <?= number_format($total_expenses, 2) ?></div>
                    </div>
                    <div class="summary-item" style="grid-column: 1 / -1; border-left: 4px solid #28a745;">
                        <h4>Expected Cash Balance</h4>
                        <div class="amount" style="color: #28a745; font-size: 32px;">
                            Rs <?= number_format($open_register['opening_balance'] + $sales_summary['cash_sales'] - $total_expenses, 2) ?>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <a href="pos.php" class="btn btn-primary btn-lg" style="flex: 1;">
                        💰 Continue to POS
                    </a>
                    <a href="expenses.php" class="btn btn-warning btn-lg" style="flex: 1;">
                        📝 Add Expense
                    </a>
                    <button onclick="showCloseRegister()" class="btn btn-danger btn-lg" style="flex: 1;">
                        🔒 Close Register
                    </button>
                </div>
            </div>
            
            <!-- Close Register Modal -->
            <div id="close-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Close Cash Register</h2>
                        <span class="close" onclick="closeModal()">&times;</span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="close">
                        
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Expected Cash Balance:</strong> 
                                Rs <?= number_format($open_register['opening_balance'] + $sales_summary['cash_sales'] - $total_expenses, 2) ?>
                            </div>
                            
                            <div class="form-group">
                                <label>Actual Closing Balance (Rs) *</label>
                                <input type="number" name="closing_balance" step="0.01" min="0" required 
                                       style="font-size: 20px;" placeholder="Count your cash drawer">
                            </div>
                            
                            <div class="form-group">
                                <label>Notes (Optional)</label>
                                <textarea name="notes" rows="3" placeholder="Any notes about discrepancies or issues..."></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn btn-danger">Close Register</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        function showCloseRegister() {
            document.getElementById('close-modal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('close-modal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('close-modal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>