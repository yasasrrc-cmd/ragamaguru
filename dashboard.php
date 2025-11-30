<?php
require_once 'config.php';
requireLogin();

// Get statistics
$today = date('Y-m-d');

$totalCustomers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$totalAppointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today'")->fetch_assoc()['count'];
$totalServices = $conn->query("SELECT COUNT(*) as count FROM services WHERE active = 1")->fetch_assoc()['count'];
$todayRevenue = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE invoice_date = '$today'")->fetch_assoc()['total'] ?? 0;

// Get today's appointments
$todayAppointments = $conn->query("
    SELECT a.*, c.name, c.mobile, s.service_name, s.duration 
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.appointment_date = '$today'
    ORDER BY a.appointment_time ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo $totalCustomers; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <h3><?php echo $totalAppointments; ?></h3>
                    <p>Today's Appointments</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💆</div>
                <div class="stat-content">
                    <h3><?php echo $totalServices; ?></h3>
                    <p>Active Services</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3>Rs. <?php echo number_format($todayRevenue, 2); ?></h3>
                    <p>Today's Revenue</p>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Today's Schedule</h2>
            <?php if ($todayAppointments->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Service</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($apt = $todayAppointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatTime($apt['appointment_time']); ?></td>
                                <td><?php echo $apt['name']; ?></td>
                                <td><?php echo $apt['mobile']; ?></td>
                                <td><?php echo $apt['service_name']; ?></td>
                                <td><?php echo $apt['duration']; ?> mins</td>
                                <td>
                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="appointment_view.php?id=<?php echo $apt['id']; ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No appointments scheduled for today.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>