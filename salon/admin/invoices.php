<?php
require_once '../config.php';
check_admin_login();

// Get invoices
$invoices = $conn->query("
    SELECT i.*, c.name as customer_name, c.mobile
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    ORDER BY i.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>📄 Invoices</h1>
                <a href="billing.php" class="btn btn-primary">+ Create New Bill</a>
            </div>
            
            <div class="dashboard-section">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($invoices->num_rows > 0): ?>
                                <?php while ($invoice = $invoices->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $invoice['invoice_number']; ?></strong></td>
                                    <td><?php echo $invoice['customer_name']; ?></td>
                                    <td><?php echo $invoice['mobile']; ?></td>
                                    <td><?php echo format_currency($invoice['total_amount']); ?></td>
                                    <td><?php echo ucfirst($invoice['payment_method']); ?></td>
                                    <td><span class="badge badge-<?php echo $invoice['payment_status']; ?>"><?php echo ucfirst($invoice['payment_status']); ?></span></td>
                                    <td><?php echo format_date($invoice['created_at']); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-primary btn-sm" onclick="printInvoice(<?php echo $invoice['id']; ?>, 'normal')">Print</button>
                                        <button class="btn btn-secondary btn-sm" onclick="printInvoice(<?php echo $invoice['id']; ?>, 'thermal')">Thermal</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">No invoices found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function printInvoice(id, type) {
            window.open('print_invoice.php?id=' + id + '&type=' + type, '_blank', 'width=800,height=600');
        }
    </script>
</body>
</html>