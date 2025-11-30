<?php
require_once '../config.php';

$invoice_id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'normal';

if (!$invoice_id) {
    die('Invalid invoice ID');
}

// Get invoice details
$stmt = $conn->prepare("
    SELECT i.*, c.name as customer_name, c.mobile, c.city
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.id = ?
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();

if (!$invoice) {
    die('Invoice not found');
}

// Get invoice items
$stmt = $conn->prepare("
    SELECT ii.*, s.name as service_name, e.name as employee_name
    FROM invoice_items ii
    JOIN services s ON ii.service_id = s.id
    LEFT JOIN employees e ON ii.employee_id = e.id
    WHERE ii.invoice_id = ?
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$items = $stmt->get_result();

if ($type === 'thermal'): ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thermal Receipt - <?php echo $invoice['invoice_number']; ?></title>
    <style>
        * { margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 58mm;
            margin: 0 auto;
            padding: 5mm;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .info { margin-bottom: 10px; font-size: 11px; }
        .items { margin: 10px 0; }
        .item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .item-name { max-width: 60%; }
        .totals {
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 10px;
        }
        @media print {
            body { width: 58mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SALON RECEIPT</h1>
        <div><?php echo SITE_NAME; ?></div>
    </div>
    
    <div class="info">
        <div>Invoice: <?php echo $invoice['invoice_number']; ?></div>
        <div>Date: <?php echo date('d/m/Y h:i A', strtotime($invoice['created_at'])); ?></div>
        <div>Customer: <?php echo $invoice['customer_name']; ?></div>
        <div>Mobile: <?php echo $invoice['mobile']; ?></div>
    </div>
    
    <div style="border-top: 1px dashed #000; padding: 5px 0;">
        <div style="display: flex; justify-content: space-between; font-weight: bold;">
            <span>ITEM</span>
            <span>AMOUNT</span>
        </div>
    </div>
    
    <div class="items">
        <?php while ($item = $items->fetch_assoc()): ?>
        <div class="item">
            <div class="item-name">
                <?php echo $item['service_name']; ?>
                <?php if ($item['employee_name']): ?>
                <br><small>By: <?php echo $item['employee_name']; ?></small>
                <?php endif; ?>
                <?php if ($item['quantity'] > 1): ?>
                <br><small><?php echo $item['quantity']; ?> x Rs.<?php echo number_format($item['price'], 2); ?></small>
                <?php endif; ?>
            </div>
            <div><?php echo number_format($item['subtotal'], 2); ?></div>
        </div>
        <?php endwhile; ?>
    </div>
    
    <div class="totals">
        <div class="total-row grand-total">
            <span>TOTAL</span>
            <span>Rs. <?php echo number_format($invoice['total_amount'], 2); ?></span>
        </div>
        <div class="total-row">
            <span>Payment</span>
            <span><?php echo ucfirst($invoice['payment_method']); ?></span>
        </div>
    </div>
    
    <div class="footer">
        <div>Thank You! Visit Again!</div>
        <div style="margin-top: 5px;">Powered by Salon Booking System</div>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>
    
    <script>
        // Auto print on load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

<?php else: ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?php echo $invoice['invoice_number']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-header h1 {
            color: #667eea;
            font-size: 32px;
        }
        .invoice-details {
            text-align: right;
        }
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .info-section h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .info-section p {
            margin: 5px 0;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        .text-right { text-align: right; }
        .totals {
            margin-top: 30px;
            text-align: right;
        }
        .totals table {
            margin-left: auto;
            width: 300px;
        }
        .totals table td {
            padding: 8px;
        }
        .grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #667eea;
        }
        .invoice-footer {
            margin-top: 50px;
            text-align: center;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        @media print {
            body { padding: 0; background: white; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div>
                <h1>INVOICE</h1>
                <p><?php echo SITE_NAME; ?></p>
            </div>
            <div class="invoice-details">
                <h2><?php echo $invoice['invoice_number']; ?></h2>
                <p>Date: <?php echo format_date($invoice['created_at']); ?></p>
                <p>Time: <?php echo date('h:i A', strtotime($invoice['created_at'])); ?></p>
            </div>
        </div>
        
        <div class="invoice-info">
            <div class="info-section">
                <h3>Billed To</h3>
                <p><strong><?php echo $invoice['customer_name']; ?></strong></p>
                <p>Mobile: <?php echo $invoice['mobile']; ?></p>
                <?php if ($invoice['city']): ?>
                <p>City: <?php echo $invoice['city']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="info-section">
                <h3>Payment Details</h3>
                <p>Status: <strong><?php echo ucfirst($invoice['payment_status']); ?></strong></p>
                <p>Method: <?php echo ucfirst($invoice['payment_method']); ?></p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Employee</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $items->data_seek(0);
                while ($item = $items->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $item['service_name']; ?></td>
                    <td><?php echo $item['employee_name'] ?: 'N/A'; ?></td>
                    <td class="text-right"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo format_currency($item['price']); ?></td>
                    <td class="text-right"><?php echo format_currency($item['subtotal']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr class="grand-total">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right"><strong><?php echo format_currency($invoice['total_amount']); ?></strong></td>
                </tr>
            </table>
        </div>
        
        <div class="invoice-footer">
            <p>Thank you for your business!</p>
            <p style="margin-top: 10px;">This is a computer-generated invoice.</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="padding: 12px 30px; font-size: 16px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">Print Invoice</button>
            <button onclick="window.location.href='invoices.php'" style="padding: 12px 30px; font-size: 16px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Back to Invoices</button>
        </div>
    </div>
</body>
</html>
<?php endif; ?>