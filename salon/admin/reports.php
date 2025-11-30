<?php
require_once '../config.php';
check_admin_login();

// Filter parameters
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get customer info if customer_id is set
$customer_info = null;
if ($customer_id) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer_info = $stmt->get_result()->fetch_assoc();
}

// Statistics
$where = "WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'";
if ($customer_id) {
    $where .= " AND a.customer_id = $customer_id";
}

// Total appointments
$stmt = $conn->query("SELECT COUNT(*) as count FROM appointments a $where");
$total_appointments = $stmt->fetch_assoc()['count'];

// Completed appointments
$stmt = $conn->query("SELECT COUNT(*) as count FROM appointments a $where AND a.status = 'completed'");
$completed_appointments = $stmt->fetch_assoc()['count'];

// Total revenue
$stmt = $conn->query("
    SELECT SUM(s.price) as total 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    $where AND a.status = 'completed'
");
$total_revenue = $stmt->fetch_assoc()['total'] ?? 0;

// Most popular services
$popular_services = $conn->query("
    SELECT s.name, COUNT(*) as booking_count, SUM(s.price) as revenue
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    $where AND a.status = 'completed'
    GROUP BY s.id
    ORDER BY booking_count DESC
    LIMIT 5
");

// Recent appointments
$appointments = $conn->query("
    SELECT a.*, c.name as customer_name, c.mobile, s.name as service_name, s.price
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    $where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 50
");

// Get all customers for dropdown
$customers = $conn->query("SELECT id, name, mobile FROM customers ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & History - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>📈 Reports & Customer History</h1>
            </div>
            
            <?php if ($customer_info): ?>
            <div class="dashboard-section">
                <h2>Customer Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 20px; background: #f8f9ff; border-radius: 8px;">
                    <div>
                        <strong>Name:</strong> <?php echo $customer_info['name']; ?>
                    </div>
                    <div>
                        <strong>Mobile:</strong> <?php echo $customer_info['mobile']; ?>
                    </div>
                    <div>
                        <strong>City:</strong> <?php echo $customer_info['city'] ?: 'N/A'; ?>
                    </div>
                    <div>
                        <strong>Registered:</strong> <?php echo format_date($customer_info['created_at']); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="dashboard-section">
                <h2>Filters</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Customer</label>
                            <select name="customer_id" class="form-control">
                                <option value="">All Customers</option>
                                <?php while ($customer = $customers->fetch_assoc()): ?>
                                    <option value="<?php echo $customer['id']; ?>" <?php echo $customer_id == $customer['id'] ? 'selected' : ''; ?>>
                                        <?php echo $customer['name']; ?> (<?php echo $customer['mobile']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-details">
                        <h3><?php echo $total_appointments; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-details">
                        <h3><?php echo $completed_appointments; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-details">
                        <h3>Rs. <?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-details">
                        <h3><?php echo $completed_appointments > 0 ? number_format($total_revenue / $completed_appointments, 2) : '0.00'; ?></h3>
                        <p>Avg. Per Booking</p>
                    </div>
                </div>
            </div>
            
            <!-- Popular Services -->
            <?php if ($popular_services->num_rows > 0): ?>
            <div class="dashboard-section">
                <h2>Popular Services</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($service = $popular_services->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $service['name']; ?></strong></td>
                                <td><?php echo $service['booking_count']; ?></td>
                                <td><?php echo format_currency($service['revenue']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Appointments History -->
            <div class="dashboard-section">
                <h2>Appointment History</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments->num_rows > 0): ?>
                                <?php while ($apt = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $apt['id']; ?></td>
                                    <td><?php echo $apt['customer_name']; ?></td>
                                    <td><?php echo $apt['service_name']; ?></td>
                                    <td><?php echo format_date($apt['appointment_date']); ?></td>
                                    <td><?php echo format_time($apt['appointment_time']); ?></td>
                                    <td><?php echo format_currency($apt['price']); ?></td>
                                    <td><span class="badge badge-<?php echo $apt['status']; ?>"><?php echo ucfirst($apt['status']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">No appointments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>