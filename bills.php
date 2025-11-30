<?php
require_once 'config.php';
requireLogin();

// Get search parameter
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Get bills
if ($search) {
    $bills = $conn->query("
        SELECT b.*, c.name as customer_name, c.mobile 
        FROM bills b
        LEFT JOIN customers c ON b.customer_id = c.id
        WHERE b.bill_number LIKE '%$search%' 
        OR c.name LIKE '%$search%' 
        OR c.mobile LIKE '%$search%'
        ORDER BY b.bill_date DESC, b.created_at DESC
    ");
} else {
    $bills = $conn->query("
        SELECT b.*, c.name as customer_name, c.mobile 
        FROM bills b
        LEFT JOIN customers c ON b.customer_id = c.id
        ORDER BY b.bill_date DESC, b.created_at DESC
        LIMIT 100
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bills - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .free-visits-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Bills Management</h1>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="section">
            <!-- Search -->
            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="search" placeholder="Search by bill number, customer name, or mobile..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                    <?php if ($search): ?>
                        <a href="bills.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($bills->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Bill #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Free Visits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bill = $bills->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $bill['bill_number']; ?></strong></td>
                                <td><?php echo formatDate($bill['bill_date']); ?></td>
                                <td><?php echo $bill['customer_name'] ?: 'Walk-in'; ?></td>
                                <td><?php echo $bill['mobile'] ?: '-'; ?></td>
                                <td>Rs. <?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td>Rs. <?php echo number_format($bill['paid_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $bill['payment_status']; ?>">
                                        <?php echo ucfirst($bill['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($bill['free_visits_granted'] > 0): ?>
                                        <span class="free-visits-badge">
                                            🎁 <?php echo $bill['free_visits_granted']; ?> Free
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="bill_view.php?id=<?php echo $bill['id']; ?>" class="btn btn-sm">View</a>
                                        <a href="bill_print.php?id=<?php echo $bill['id']; ?>" target="_blank" class="btn btn-sm btn-success">Print</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No bills found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Free Visits Summary -->
        <div class="section">
            <h2>Free Visits Summary</h2>
            <?php
            $free_visits_summary = $conn->query("
                SELECT c.name, c.mobile, s.service_name, 
                f.total_free_visits, f.used_visits, f.remaining_visits, f.expiry_date
                FROM free_visits f
                JOIN customers c ON f.customer_id = c.id
                JOIN services s ON f.service_id = s.id
                WHERE f.remaining_visits > 0 AND (f.expiry_date IS NULL OR f.expiry_date >= CURDATE())
                ORDER BY f.granted_date DESC
                LIMIT 20
            ");
            
            if ($free_visits_summary->num_rows > 0):
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Service</th>
                            <th>Total Granted</th>
                            <th>Used</th>
                            <th>Remaining</th>
                            <th>Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fv = $free_visits_summary->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $fv['name']; ?></td>
                                <td><?php echo $fv['mobile']; ?></td>
                                <td><?php echo $fv['service_name']; ?></td>
                                <td><?php echo $fv['total_free_visits']; ?></td>
                                <td><?php echo $fv['used_visits']; ?></td>
                                <td><strong style="color: #28a745;"><?php echo $fv['remaining_visits']; ?></strong></td>
                                <td><?php echo $fv['expiry_date'] ? formatDate($fv['expiry_date']) : 'No expiry'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No active free visits.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>