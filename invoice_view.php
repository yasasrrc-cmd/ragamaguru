<?php
require_once 'config.php';
requireLogin();

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get invoice details
$invoice = $conn->query("
    SELECT i.*, c.name, c.mobile, c.address 
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.id = $invoice_id
")->fetch_assoc();

if (!$invoice) {
    header("Location: invoices.php");
    exit();
}

// Get invoice items
$items = $conn->query("
    SELECT ii.*, s.service_name 
    FROM invoice_items ii
    JOIN services s ON ii.service_id = s.id
    WHERE ii.invoice_id = $invoice_id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $invoice['invoice_number']; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .invoice-table {
            width: 100%;
            margin: 20px 0;
        }
        .invoice-table th {
            background: #f0f0f0;
            text-align: left;
        }
        .invoice-total {
            text-align: right;
            margin-top: 20px;
        }
        .invoice-total table {
            margin-left: auto;
            width: 300px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="no-print" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <h1>Invoice Details</h1>
            <div>
                <button onclick="window.print()" class="btn">🖨️ Print</button>
                <a href="invoice_edit.php?id=<?php echo $invoice_id; ?>" class="btn btn-success">Edit</a>
                <a href="invoices.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
        
        <div class="section">
            <!-- Invoice Header -->
            <div class="invoice-header">
                <div>
                    <h1 style="color: #667eea; margin-bottom: 10px;">Ragamaguru</h1>
                    <p>No72<br>
                    Kurukulawa Ragama<br>
                    Phone: +94 70 39 29 829<br>
                    Email: info@ragamaguru.lk</p>
                </div>
                <div style="text-align: right;">
                    <h2>INVOICE</h2>
                    <p><strong><?php echo $invoice['invoice_number']; ?></strong></p>
                    <p>Date: <?php echo formatDate($invoice['invoice_date']); ?></p>
                    <p>
                        <span class="status-badge status-<?php echo $invoice['payment_status']; ?>">
                            <?php echo strtoupper($invoice['payment_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <!-- Bill To -->
            <div style="margin-bottom: 30px;">
                <h3>Bill To:</h3>
                <p><strong><?php echo $invoice['name']; ?></strong><br>
                Mobile: <?php echo $invoice['mobile']; ?><br>
                <?php echo nl2br($invoice['address'] ?: ''); ?></p>
            </div>
            
            <!-- Items Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service</th>
                        <th style="text-align: center;">Quantity</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $row_num = 1;
                    while ($item = $items->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $row_num++; ?></td>
                            <td><?php echo $item['service_name']; ?></td>
                            <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;">Rs. <?php echo number_format($item['price'], 2); ?></td>
                            <td style="text-align: right;">Rs. <?php echo number_format($item['total'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- Totals -->
            <div class="invoice-total">
                <table>
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td style="text-align: right;">Rs. <?php echo number_format($invoice['total_amount'], 2); ?></td>
                    </tr>
                    <tr style="border-top: 2px solid #333;">
                        <td><strong>Total Amount:</strong></td>
                        <td style="text-align: right;"><strong>Rs. <?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Paid Amount:</td>
                        <td style="text-align: right;">Rs. <?php echo number_format($invoice['paid_amount'], 2); ?></td>
                    </tr>
                    <tr style="border-top: 1px solid #999;">
                        <td><strong>Balance Due:</strong></td>
                        <td style="text-align: right;"><strong>Rs. <?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($invoice['payment_method']): ?>
                <div style="margin-top: 20px;">
                    <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $invoice['payment_method'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 50px; text-align: center; color: #666; font-size: 12px;">
                <p>Thank you for choosing Ragamaguru!</p>
                <p>This is a computer generated invoice.</p>
            </div>
        </div>
    </div>
</body>
</html>