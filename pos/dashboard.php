<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Get statistics
$stats = [];

// Total sales today
$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = CURDATE()");
$stats['today_sales'] = $stmt->fetch()['total'];

// Total sales this month
$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stats['month_sales'] = $stmt->fetch()['total'];

// Total products
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
$stats['total_products'] = $stmt->fetch()['count'];

// Low stock products
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock <= min_stock");
$stats['low_stock'] = $stmt->fetch()['count'];

// Recent sales
$stmt = $pdo->query("SELECT s.*, u.full_name FROM sales s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 10");
$recent_sales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">💰</div>
                    <div class="stat-content">
                        <h3>Rs <?= number_format($stats['today_sales'], 2) ?></h3>
                        <p>Today's Sales</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">📊</div>
                    <div class="stat-content">
                        <h3>Rs <?= number_format($stats['month_sales'], 2) ?></h3>
                        <p>This Month</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">📦</div>
                    <div class="stat-content">
                        <h3><?= $stats['total_products'] ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #F44336;">⚠️</div>
                    <div class="stat-content">
                        <h3><?= $stats['low_stock'] ?></h3>
                        <p>Low Stock Items</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Recent Sales</h2>
                    <a href="sales.php" class="btn btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Cashier</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td><?= htmlspecialchars($sale['invoice_no']) ?></td>
                                <td><?= htmlspecialchars($sale['full_name']) ?></td>
                                <td>Rs <?= number_format($sale['total'], 2) ?></td>
                                <td><span class="badge"><?= ucfirst($sale['payment_method']) ?></span></td>
                                <td><?= date('M d, Y H:i', strtotime($sale['created_at'])) ?></td>
                                <td>
                                    <a href="invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>