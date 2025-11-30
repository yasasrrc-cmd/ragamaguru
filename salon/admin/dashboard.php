<?php
require_once '../config.php';
check_admin_login();

// Get statistics
$today = date('Y-m-d');

// Today's appointments
$stmt = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today' AND status != 'cancelled'");
$today_appointments = $stmt->fetch_assoc()['count'];

// Total customers
$stmt = $conn->query("SELECT COUNT(*) as count FROM customers");
$total_customers = $stmt->fetch_assoc()['count'];

// This month's revenue
$month_start = date('Y-m-01');
$stmt = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE created_at >= '$month_start' AND payment_status = 'paid'");
$month_revenue = $stmt->fetch_assoc()['total'] ?? 0;

// Pending appointments
$stmt = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending' AND appointment_date >= '$today'");
$pending_appointments = $stmt->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>📊 Dashboard</h1>
                <p>Welcome back, <?php echo $_SESSION['admin_name']; ?>!</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-details">
                        <h3><?php echo $today_appointments; ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-details">
                        <h3><?php echo $total_customers; ?></h3>
                        <p>Total Customers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-details">
                        <h3>Rs. <?php echo number_format($month_revenue, 2); ?></h3>
                        <p>This Month's Revenue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-details">
                        <h3><?php echo $pending_appointments; ?></h3>
                        <p>Pending Appointments</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-section">
                    <h2>📋 Recent Appointments</h2>
                    <?php
                    $stmt = $conn->query("
                        SELECT a.*, c.name as customer_name, c.mobile, s.name as service_name
                        FROM appointments a
                        JOIN customers c ON a.customer_id = c.id
                        JOIN services s ON a.service_id = s.id
                        WHERE a.appointment_date >= '$today'
                        ORDER BY a.appointment_date ASC, a.appointment_time ASC
                        LIMIT 10
                    ");
                    ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $stmt->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['customer_name']; ?></td>
                                    <td><?php echo $row['service_name']; ?></td>
                                    <td><?php echo format_date($row['appointment_date']); ?></td>
                                    <td><?php echo format_time($row['appointment_time']); ?></td>
                                    <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="dashboard-section">
                    <h2>⚡ Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="billing.php" class="action-tile">
                            <div class="tile-icon">💳</div>
                            <div class="tile-label">Create Bill</div>
                        </a>
                        <a href="appointments.php?action=add" class="action-tile">
                            <div class="tile-icon">➕</div>
                            <div class="tile-label">New Appointment</div>
                        </a>
                        <a href="customers.php?action=add" class="action-tile">
                            <div class="tile-icon">👤</div>
                            <div class="tile-label">Add Customer</div>
                        </a>
                        <a href="availability.php" class="action-tile">
                            <div class="tile-icon">⏰</div>
                            <div class="tile-label">Manage Availability</div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>