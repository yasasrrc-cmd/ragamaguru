<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

// Get report type and date range
$report_type = $_GET['type'] ?? 'sales';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Sales Report
if ($report_type === 'sales') {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as sale_date, 
               COUNT(*) as transactions,
               SUM(total) as total_sales,
               AVG(total) as avg_sale
        FROM sales 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY sale_date DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $daily_sales = $stmt->fetchAll();
    
    // Total summary
    $stmt = $pdo->prepare("SELECT SUM(total) as total, COUNT(*) as count FROM sales WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $summary = $stmt->fetch();
}

// Top Products Report
if ($report_type === 'products') {
    $stmt = $pdo->prepare("
        SELECT p.name, p.barcode, 
               SUM(si.quantity) as total_sold,
               SUM(si.subtotal) as total_revenue,
               p.stock
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        JOIN sales s ON si.sale_id = s.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 20
    ");
    $stmt->execute([$date_from, $date_to]);
    $top_products = $stmt->fetchAll();
}

// Low Stock Report
if ($report_type === 'inventory') {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock <= p.min_stock
        ORDER BY p.stock ASC
    ");
    $low_stock = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Reports & Analytics</h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <form method="GET" class="search-form">
                        <select name="type" class="form-control" style="width: 200px;">
                            <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Sales Report</option>
                            <option value="products" <?= $report_type === 'products' ? 'selected' : '' ?>>Top Products</option>
                            <option value="inventory" <?= $report_type === 'inventory' ? 'selected' : '' ?>>Low Stock</option>
                        </select>
                        
                        <?php if ($report_type !== 'inventory'): ?>
                        <input type="date" name="date_from" value="<?= $date_from ?>" required>
                        <span style="padding: 0 10px;">to</span>
                        <input type="date" name="date_to" value="<?= $date_to ?>" required>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <button type="button" onclick="window.print()" class="btn">🖨️ Print</button>
                    </form>
                </div>
                
                <?php if ($report_type === 'sales'): ?>
                    <div style="padding: 20px; background: #f8f9fa;">
                        <h3>Sales Summary (<?= date('M d, Y', strtotime($date_from)) ?> - <?= date('M d, Y', strtotime($date_to)) ?>)</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px;">
                            <div>
                                <strong style="display: block; color: #666;">Total Sales</strong>
                                <span style="font-size: 32px; color: var(--primary);">Rs <?= number_format($summary['total'] ?? 0, 2) ?></span>
                            </div>
                            <div>
                                <strong style="display: block; color: #666;">Transactions</strong>
                                <span style="font-size: 32px; color: var(--success);"><?= $summary['count'] ?? 0 ?></span>
                            </div>
                            <div>
                                <strong style="display: block; color: #666;">Average Sale</strong>
                                <span style="font-size: 32px; color: var(--info);">
                                    Rs <?= $summary['count'] > 0 ? number_format($summary['total'] / $summary['count'], 2) : '0.00' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transactions</th>
                                    <th>Total Sales</th>
                                    <th>Average Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($daily_sales)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No sales data for selected period</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($daily_sales as $day): ?>
                                    <tr>
                                        <td><?= date('M d, Y (l)', strtotime($day['sale_date'])) ?></td>
                                        <td><?= $day['transactions'] ?></td>
                                        <td><strong>Rs <?= number_format($day['total_sales'], 2) ?></strong></td>
                                        <td>Rs <?= number_format($day['avg_sale'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                
                <?php elseif ($report_type === 'products'): ?>
                    <div style="padding: 20px; background: #f8f9fa;">
                        <h3>Top Selling Products (<?= date('M d, Y', strtotime($date_from)) ?> - <?= date('M d, Y', strtotime($date_to)) ?>)</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Barcode</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Current Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_products)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No product sales for selected period</td>
                                </tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><strong><?= $rank++ ?></strong></td>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= htmlspecialchars($product['barcode']) ?></td>
                                        <td><span class="badge badge-success"><?= $product['total_sold'] ?></span></td>
                                        <td><strong>Rs <?= number_format($product['total_revenue'], 2) ?></strong></td>
                                        <td><?= $product['stock'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                
                <?php elseif ($report_type === 'inventory'): ?>
                    <div style="padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
                        <h3>⚠️ Low Stock Alert</h3>
                        <p>Products that are at or below minimum stock levels</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Barcode</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Min Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($low_stock)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">✓ All products have adequate stock</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($low_stock as $product): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($product['barcode']) ?></td>
                                        <td><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge <?= $product['stock'] == 0 ? 'badge-danger' : 'badge-warning' ?>">
                                                <?= $product['stock'] ?>
                                            </span>
                                        </td>
                                        <td><?= $product['min_stock'] ?></td>
                                        <td>
                                            <?php if ($product['stock'] == 0): ?>
                                                <span class="badge badge-danger">Out of Stock</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Low Stock</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>