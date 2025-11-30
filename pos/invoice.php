<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$sale_id = $_GET['id'] ?? 0;

// Get sale details
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as cashier_name 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    die('Sale not found');
}

// Get sale items
$stmt = $pdo->prepare("
    SELECT si.*, p.name as product_name 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();

// Get settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= htmlspecialchars($sale['invoice_no']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; padding: 20px; max-width: 800px; margin: 0 auto; }
        .invoice { background: white; padding: 40px; border: 2px solid #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px dashed #333; padding-bottom: 20px; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .items-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .items-table th, .items-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .items-table th { background: #f5f5f5; font-weight: bold; }
        .totals { margin-top: 20px; border-top: 2px solid #333; padding-top: 20px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 16px; }
        .total-row.grand { font-size: 20px; font-weight: bold; margin-top: 10px; padding-top: 10px; border-top: 2px dashed #333; }
        .footer { margin-top: 30px; text-align: center; border-top: 2px dashed #333; padding-top: 20px; }
        .print-btn { background: #667eea; color: white; border: none; padding: 15px 30px; font-size: 16px; cursor: pointer; border-radius: 5px; margin-bottom: 20px; }
        .print-btn:hover { background: #5568d3; }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            .invoice { border: none; padding: 20px; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn">🖨️ Print Invoice</button>
    
    <div class="invoice">
        <div class="header">
            <h1><?= htmlspecialchars($settings['store_name'] ?? 'POS System') ?></h1>
            <?php if (!empty($settings['store_address'])): ?>
                <p><?= htmlspecialchars($settings['store_address']) ?></p>
            <?php endif; ?>
            <?php if (!empty($settings['store_phone'])): ?>
                <p>Tel: <?= htmlspecialchars($settings['store_phone']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="info-section">
            <div class="info-row">
                <strong>Invoice #:</strong>
                <span><?= htmlspecialchars($sale['invoice_no']) ?></span>
            </div>
            <div class="info-row">
                <strong>Date:</strong>
                <span><?= date('F d, Y h:i A', strtotime($sale['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <strong>Cashier:</strong>
                <span><?= htmlspecialchars($sale['cashier_name']) ?></span>
            </div>
            <div class="info-row">
                <strong>Payment Method:</strong>
                <span><?= ucfirst($sale['payment_method']) ?></span>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td style="text-align: center;"><?= $item['quantity'] ?></td>
                    <td style="text-align: right;">Rs <?= number_format($item['price'], 2) ?></td>
                    <td style="text-align: right;">Rs <?= number_format($item['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <div class="total-row">
                <strong>Subtotal:</strong>
                <span>Rs <?= number_format($sale['total'], 2) ?></span>
            </div>
            <?php if (!empty($settings['tax_rate']) && $settings['tax_rate'] > 0): ?>
            <div class="total-row">
                <strong>Tax (<?= $settings['tax_rate'] ?>%):</strong>
                <span>Rs <?= number_format($sale['total'] * ($settings['tax_rate'] / 100), 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand">
                <strong>TOTAL:</strong>
                <span>Rs <?= number_format($sale['total'], 2) ?></span>
            </div>
            <div class="total-row">
                <strong>Amount Paid:</strong>
                <span>Rs <?= number_format($sale['amount_paid'], 2) ?></span>
            </div>
            <div class="total-row">
                <strong>Change:</strong>
                <span>Rs <?= number_format($sale['change_amount'], 2) ?></span>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Please keep this receipt for your records</p>
        </div>
    </div>
</body>
</html>