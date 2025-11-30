<?php
require_once 'config.php';
requireLogin();

// Get all invoices
$invoices = $conn->query("
    SELECT i.*, c.name, c.mobile 
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    ORDER BY i.invoice_date DESC, i.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Invoices</h1>
            <a href="invoice_create.php" class="btn">+ Create Invoice</a>
        </div>
        
        <div class="section">
            <?php if ($invoices->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($invoice = $invoices->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $invoice['invoice_number']; ?></strong></td>
                                <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                                <td><?php echo $invoice['name']; ?></td>
                                <td><?php echo $invoice['mobile']; ?></td>
                                <td>Rs. <?php echo number_format($invoice['total_amount'], 2); ?></td>
                                <td>Rs. <?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                <td>Rs. <?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $invoice['payment_status']; ?>">
                                        <?php echo ucfirst($invoice['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm">View</a>
                                        <a href="invoice_edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-success">Edit</a>
                                        <a href="invoice_delete.php?id=<?php echo $invoice['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this invoice?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No invoices found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>