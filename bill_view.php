<?php
require_once 'config.php';
requireLogin();

$bill_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get bill details
$bill = $conn->query("
    SELECT b.*, c.name as customer_name, c.mobile, c.address,
    a.full_name as created_by_name
    FROM bills b
    LEFT JOIN customers c ON b.customer_id = c.id
    LEFT JOIN admins a ON b.created_by = a.id
    WHERE b.id = $bill_id
")->fetch_assoc();

if (!$bill) {
    header("Location: bills.php");
    exit();
}

// Get bill items
$items = $conn->query("SELECT * FROM bill_items WHERE bill_id = $bill_id");

// Get treatment details if appointment exists
$treatment = null;
if ($bill['appointment_id']) {
    $treatment = $conn->query("
        SELECT t.*, s.service_name 
        FROM treatment_records t
        JOIN appointments a ON t.appointment_id = a.id
        JOIN services s ON a.service_id = s.id
        WHERE t.appointment_id = {$bill['appointment_id']}
    ")->fetch_assoc();
}

// Get free visits info
$free_visits_info = null;
if ($bill['free_visits_granted'] > 0) {
    $free_visits_info = $conn->query("
        SELECT * FROM free_visits 
        WHERE customer_id = {$bill['customer_id']} 
        AND DATE(granted_date) = '{$bill['bill_date']}'
        ORDER BY id DESC LIMIT 1
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill - <?php echo $bill['bill_number']; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .treatment-box {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .free-visits-box {
            background: #d4edda;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Bill Details</h1>
            <div>
                <a href="bill_print.php?id=<?php echo $bill_id; ?>" target="_blank" class="btn">🖨️ Print Bill</a>
                <a href="bills.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
        
        <!-- Bill Info -->
        <div class="section">
            <h2>Bill Information</h2>
            <div class="form-grid">
                <div>
                    <strong>Bill Number:</strong> <?php echo $bill['bill_number']; ?><br>
                    <strong>Date:</strong> <?php echo formatDate($bill['bill_date']); ?><br>
                    <strong>Created By:</strong> <?php echo $bill['created_by_name']; ?>
                </div>
                <div>
                    <strong>Customer:</strong> <?php echo $bill['customer_name'] ?: 'Walk-in Customer'; ?><br>
                    <?php if ($bill['mobile']): ?>
                        <strong>Mobile:</strong> <?php echo $bill['mobile']; ?><br>
                    <?php endif; ?>
                    <strong>Payment Status:</strong> 
                    <span class="status-badge status-<?php echo $bill['payment_status']; ?>">
                        <?php echo ucfirst($bill['payment_status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Treatment Summary (if exists) -->
        <?php if ($treatment): ?>
        <div class="treatment-box">
            <h3>📋 Treatment Summary</h3>
            <p><strong>Service:</strong> <?php echo $treatment['service_name']; ?></p>
            <p><strong>Treatment Date:</strong> <?php echo formatDate($treatment['treatment_date']); ?></p>
            
            <?php if ($treatment['diagnosis']): ?>
                <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($treatment['diagnosis']); ?></p>
            <?php endif; ?>
            
            <p><strong>Treatment Given:</strong><br>
            <?php echo nl2br(htmlspecialchars($treatment['treatment_given'])); ?></p>
            
            <?php if ($treatment['products_used']): ?>
                <p><strong>Products Used:</strong><br>
                <?php echo nl2br(htmlspecialchars($treatment['products_used'])); ?></p>
            <?php endif; ?>
            
            <?php if ($treatment['medicines_prescribed']): ?>
                <p><strong>Medicines Prescribed:</strong><br>
                <?php echo nl2br(htmlspecialchars($treatment['medicines_prescribed'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Bill Items -->
        <div class="section">
            <h2>Bill Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $row_num = 1;
                    while ($item = $items->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $row_num++; ?></td>
                            <td>
                                <?php echo $item['item_name']; ?>
                                <?php if ($item['is_free_visit']): ?>
                                    <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">FREE VISIT</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo ucfirst($item['item_type']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                            <td><strong>Rs. <?php echo number_format($item['total'], 2); ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Payment Summary -->
        <div class="section">
            <h2>Payment Summary</h2>
            <div style="max-width: 400px; margin-left: auto;">
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                    <span>Subtotal:</span>
                    <span>Rs. <?php echo number_format($bill['subtotal'], 2); ?></span>
                </div>
                
                <?php if ($bill['discount_amount'] > 0): ?>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                    <span>Discount <?php echo $bill['discount_percent'] > 0 ? '(' . $bill['discount_percent'] . '%)' : ''; ?>:</span>
                    <span>- Rs. <?php echo number_format($bill['discount_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; padding: 15px 0; font-size: 20px; font-weight: bold; color: #667eea;">
                    <span>Total:</span>
                    <span>Rs. <?php echo number_format($bill['total_amount'], 2); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-top: 1px solid #e0e0e0;">
                    <span>Amount Paid:</span>
                    <span>Rs. <?php echo number_format($bill['paid_amount'], 2); ?></span>
                </div>
                
                <?php if ($bill['total_amount'] - $bill['paid_amount'] > 0): ?>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; color: #dc3545; font-weight: bold;">
                    <span>Balance Due:</span>
                    <span>Rs. <?php echo number_format($bill['total_amount'] - $bill['paid_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                    <span>Payment Method:</span>
                    <span><strong><?php echo ucfirst(str_replace('_', ' ', $bill['payment_method'])); ?></strong></span>
                </div>
            </div>
        </div>
        
        <!-- Free Visits Granted -->
        <?php if ($bill['free_visits_granted'] > 0 && $free_visits_info): ?>
        <div class="free-visits-box">
            <h3>🎁 Free Visits Granted</h3>
            <p><strong><?php echo $bill['free_visits_granted']; ?> FREE VISITS</strong> have been granted to this customer!</p>
            <p><strong>Valid Until:</strong> <?php echo formatDate($free_visits_info['expiry_date']); ?></p>
            <p><strong>Remaining:</strong> <?php echo $free_visits_info['remaining_visits']; ?> visits</p>
            <p style="margin-top: 10px; font-size: 14px; color: #666;">
                These free visits can be used for the same service within the validity period.
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>