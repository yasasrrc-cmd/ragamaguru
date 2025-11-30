<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Get date filter
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get sales
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    WHERE DATE(s.created_at) BETWEEN ? AND ? 
    ORDER BY s.created_at DESC
");
$stmt->execute([$date_from, $date_to]);
$sales = $stmt->fetchAll();

// Calculate totals
$total_sales = array_sum(array_column($sales, 'total'));
$total_transactions = count($sales);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Sales History</h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <form method="GET" class="search-form">
                        <input type="date" name="date_from" value="<?= $date_from ?>" required>
                        <span style="padding: 0 10px;">to</span>
                        <input type="date" name="date_to" value="<?= $date_to ?>" required>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="sales.php" class="btn">Reset</a>
                    </form>
                </div>
                
                <div style="padding: 20px; background: #f8f9fa; display: flex; justify-content: space-around;">
                    <div>
                        <strong style="display: block; color: #666;">Total Sales</strong>
                        <span style="font-size: 24px; color: var(--primary);">Rs <?= number_format($total_sales, 2) ?></span>
                    </div>
                    <div>
                        <strong style="display: block; color: #666;">Transactions</strong>
                        <span style="font-size: 24px; color: var(--success);"><?= $total_transactions ?></span>
                    </div>
                    <div>
                        <strong style="display: block; color: #666;">Average Sale</strong>
                        <span style="font-size: 24px; color: var(--info);">
                            Rs <?= $total_transactions > 0 ? number_format($total_sales / $total_transactions, 2) : '0.00' ?>
                        </span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Cashier</th>
                                <th>Total</th>
                                <th>Payment Method</th>
                                <th>Date & Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No sales found for the selected date range</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($sale['invoice_no']) ?></strong></td>
                                    <td><?= htmlspecialchars($sale['full_name']) ?></td>
                                    <td><strong>Rs <?= number_format($sale['total'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge">
                                            <?= ucfirst($sale['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($sale['created_at'])) ?></td>
                                    <td>
                                        <a href="invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                            View Invoice
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>