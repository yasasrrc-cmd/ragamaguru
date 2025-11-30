<?php
require_once 'config.php';
requireLogin();

$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get customer details
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Get past appointments
$appointments = $conn->query("
    SELECT a.*, s.service_name, s.price 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.customer_id = $customerId
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");

// Get treatment history
$treatments = $conn->query("
    SELECT t.*, s.service_name 
    FROM treatments t
    JOIN appointments a ON t.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    WHERE t.customer_id = $customerId
    ORDER BY t.treatment_date DESC
");

// Get invoices
$invoices = $conn->query("
    SELECT * FROM invoices 
    WHERE customer_id = $customerId 
    ORDER BY invoice_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Customer Details</h1>
            <div>
                <a href="customer_edit.php?id=<?php echo $customerId; ?>" class="btn btn-success">Edit</a>
                <a href="appointment_add.php?customer_id=<?php echo $customerId; ?>" class="btn">Book Appointment</a>
            </div>
        </div>
        
        <!-- Customer Info -->
        <div class="section">
            <h2>Personal Information</h2>
            <div class="form-grid">
                <div>
                    <strong>Name:</strong> <?php echo $customer['name']; ?>
                </div>
                <div>
                    <strong>Mobile:</strong> <?php echo $customer['mobile']; ?>
                    <?php if ($customer['mobile_verified']): ?>
                        <span class="status-badge status-completed">✓ Verified</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Date of Birth:</strong> <?php echo $customer['dob'] ? formatDate($customer['dob']) : '-'; ?>
                </div>
                <div>
                    <strong>Registered:</strong> <?php echo formatDate($customer['created_at']); ?>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <strong>Address:</strong><br>
                <?php echo nl2br($customer['address'] ?: 'Not provided'); ?>
            </div>
        </div>
        
        <!-- Appointment History -->
        <div class="section">
            <h2>Appointment History (<?php echo $appointments->num_rows; ?>)</h2>
            <?php if ($appointments->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($apt = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($apt['appointment_date']); ?></td>
                                <td><?php echo formatTime($apt['appointment_time']); ?></td>
                                <td><?php echo $apt['service_name']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>Rs. <?php echo number_format($apt['price'], 2); ?></td>
                                <td>
                                    <a href="appointment_view.php?id=<?php echo $apt['id']; ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No appointments yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Treatment History -->
        <div class="section">
            <h2>Treatment History (<?php echo $treatments->num_rows; ?>)</h2>
            <?php if ($treatments->num_rows > 0): ?>
                <?php while ($treatment = $treatments->fetch_assoc()): ?>
                    <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong><?php echo $treatment['service_name']; ?></strong>
                            <span><?php echo formatDate($treatment['treatment_date']); ?></span>
                        </div>
                        <div>
                            <strong>Treatment Details:</strong><br>
                            <?php echo nl2br($treatment['treatment_details']); ?>
                        </div>
                        <?php if ($treatment['products_used']): ?>
                            <div style="margin-top: 10px;">
                                <strong>Products Used:</strong><br>
                                <?php echo nl2br($treatment['products_used']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($treatment['therapist_notes']): ?>
                            <div style="margin-top: 10px;">
                                <strong>Therapist Notes:</strong><br>
                                <?php echo nl2br($treatment['therapist_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No treatment records yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Invoice History -->
        <div class="section">
            <h2>Invoice History (<?php echo $invoices->num_rows; ?>)</h2>
            <?php if ($invoices->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($invoice = $invoices->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $invoice['invoice_number']; ?></td>
                                <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                                <td>Rs. <?php echo number_format($invoice['total_amount'], 2); ?></td>
                                <td>Rs. <?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $invoice['payment_status']; ?>">
                                        <?php echo ucfirst($invoice['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No invoices yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>