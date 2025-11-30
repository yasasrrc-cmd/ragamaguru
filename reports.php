<?php
require_once 'config.php';
requireLogin();

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Daily Schedule Report
$daily_schedule = $conn->query("
    SELECT a.appointment_date, COUNT(*) as total_appointments,
           SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
           SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments a
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY a.appointment_date
    ORDER BY a.appointment_date ASC
");

// Revenue Report
$revenue_report = $conn->query("
    SELECT 
        DATE(invoice_date) as date,
        COUNT(*) as total_invoices,
        SUM(total_amount) as total_revenue,
        SUM(paid_amount) as total_paid,
        SUM(total_amount - paid_amount) as outstanding
    FROM invoices
    WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(invoice_date)
    ORDER BY date ASC
");

// Service Performance
$service_performance = $conn->query("
    SELECT s.service_name, COUNT(a.id) as bookings, SUM(s.price) as revenue
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY s.id
    ORDER BY bookings DESC
");

// Customer Stats
$new_customers = $conn->query("
    SELECT COUNT(*) as count FROM customers 
    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc()['count'];

$repeat_customers = $conn->query("
    SELECT COUNT(DISTINCT customer_id) as count 
    FROM appointments 
    WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
    AND customer_id IN (
        SELECT customer_id FROM appointments 
        WHERE appointment_date < '$start_date'
    )
")->fetch_assoc()['count'];

// Overall Stats
$total_appointments = $conn->query("
    SELECT COUNT(*) as count FROM appointments 
    WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc()['count'];

$total_revenue = $conn->query("
    SELECT SUM(total_amount) as total FROM invoices 
    WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc()['total'] ?? 0;

$total_paid = $conn->query("
    SELECT SUM(paid_amount) as total FROM invoices 
    WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="no-print" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <h1>Reports</h1>
            <button onclick="window.print()" class="btn">🖨️ Print Report</button>
        </div>
        
        <!-- Date Filter -->
        <div class="section no-print">
            <form method="GET" style="display: flex; gap: 10px; align-items: flex-end;">
                <div>
                    <label>From Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div>
                    <label>To Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" class="btn">Generate Report</button>
                <button type="button" onclick="setDateRange('today')" class="btn btn-secondary">Today</button>
                <button type="button" onclick="setDateRange('week')" class="btn btn-secondary">This Week</button>
                <button type="button" onclick="setDateRange('month')" class="btn btn-secondary">This Month</button>
            </form>
        </div>
        
        <!-- Summary Stats -->
        <h2>Summary (<?php echo formatDate($start_date); ?> to <?php echo formatDate($end_date); ?>)</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <h3><?php echo $total_appointments; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3>Rs. <?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3>Rs. <?php echo number_format($total_paid, 2); ?></h3>
                    <p>Amount Collected</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3>Rs. <?php echo number_format($total_revenue - $total_paid, 2); ?></h3>
                    <p>Outstanding</p>
                </div>
            </div>
        </div>
        
        <!-- Customer Stats -->
        <div class="section">
            <h2>Customer Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <strong>New Customers:</strong> <?php echo $new_customers; ?>
                </div>
                <div>
                    <strong>Repeat Customers:</strong> <?php echo $repeat_customers; ?>
                </div>
            </div>
        </div>
        
        <!-- Daily Schedule Report -->
        <div class="section">
            <h2>Daily Schedule Report</h2>
            <?php if ($daily_schedule->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Appointments</th>
                            <th>Completed</th>
                            <th>Cancelled</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($day = $daily_schedule->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($day['appointment_date']); ?></td>
                                <td><?php echo $day['total_appointments']; ?></td>
                                <td><?php echo $day['completed']; ?></td>
                                <td><?php echo $day['cancelled']; ?></td>
                                <td>
                                    <?php 
                                    $rate = $day['total_appointments'] > 0 ? 
                                           ($day['completed'] / $day['total_appointments']) * 100 : 0;
                                    echo number_format($rate, 1) . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No appointments in this period.</p>
            <?php endif; ?>
        </div>
        
        <!-- Revenue Report -->
        <div class="section">
            <h2>Daily Revenue Report</h2>
            <?php if ($revenue_report->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoices</th>
                            <th>Total Revenue</th>
                            <th>Amount Paid</th>
                            <th>Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($rev = $revenue_report->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($rev['date']); ?></td>
                                <td><?php echo $rev['total_invoices']; ?></td>
                                <td>Rs. <?php echo number_format($rev['total_revenue'], 2); ?></td>
                                <td>Rs. <?php echo number_format($rev['total_paid'], 2); ?></td>
                                <td>Rs. <?php echo number_format($rev['outstanding'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No revenue data for this period.</p>
            <?php endif; ?>
        </div>
        
        <!-- Service Performance -->
        <div class="section">
            <h2>Service Performance</h2>
            <?php if ($service_performance->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Total Bookings</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $service_performance->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $service['service_name']; ?></td>
                                <td><?php echo $service['bookings']; ?></td>
                                <td>Rs. <?php echo number_format($service['revenue'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No service data for this period.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function setDateRange(period) {
            const today = new Date();
            let startDate, endDate;
            
            endDate = today.toISOString().split('T')[0];
            
            if (period === 'today') {
                startDate = endDate;
            } else if (period === 'week') {
                const weekAgo = new Date(today);
                weekAgo.setDate(today.getDate() - 7);
                startDate = weekAgo.toISOString().split('T')[0];
            } else if (period === 'month') {
                const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                startDate = monthStart.toISOString().split('T')[0];
            }
            
            document.querySelector('input[name="start_date"]').value = startDate;
            document.querySelector('input[name="end_date"]').value = endDate;
        }
    </script>
</body>
</html>