<?php
require_once '../config.php';
check_admin_login();

// Filter parameters
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get all employees for dropdown
$all_employees = $conn->query("SELECT id, name, position FROM employees ORDER BY name ASC");

// Get employee info if employee_id is set
$employee_info = null;
if ($employee_id) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $employee_info = $stmt->get_result()->fetch_assoc();
}

// Statistics for specific employee
if ($employee_id) {
    // Total services done
    $stmt = $conn->query("
        SELECT COUNT(*) as count 
        FROM invoice_items 
        WHERE employee_id = $employee_id 
        AND invoice_id IN (
            SELECT id FROM invoices 
            WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        )
    ");
    $total_services = $stmt->fetch_assoc()['count'];
    
    // Total revenue generated
    $stmt = $conn->query("
        SELECT SUM(subtotal) as total 
        FROM invoice_items 
        WHERE employee_id = $employee_id 
        AND invoice_id IN (
            SELECT id FROM invoices 
            WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        )
    ");
    $total_revenue = $stmt->fetch_assoc()['total'] ?? 0;
    
    // Total commission earned
    $commission = 0;
    if ($employee_info) {
        $commission = ($total_revenue * $employee_info['commission_rate']) / 100;
    }
    
    // Services breakdown
    $services_breakdown = $conn->query("
        SELECT s.name, COUNT(*) as count, SUM(ii.subtotal) as revenue
        FROM invoice_items ii
        JOIN services s ON ii.service_id = s.id
        WHERE ii.employee_id = $employee_id
        AND ii.invoice_id IN (
            SELECT id FROM invoices 
            WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        )
        GROUP BY s.id
        ORDER BY count DESC
    ");
    
    // Recent transactions
    $transactions = $conn->query("
        SELECT i.invoice_number, i.created_at, c.name as customer_name, 
               s.name as service_name, ii.quantity, ii.subtotal
        FROM invoice_items ii
        JOIN invoices i ON ii.invoice_id = i.id
        JOIN customers c ON i.customer_id = c.id
        JOIN services s ON ii.service_id = s.id
        WHERE ii.employee_id = $employee_id
        AND i.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        ORDER BY i.created_at DESC
        LIMIT 50
    ");
} else {
    // All employees summary
    $employees_summary = $conn->query("
        SELECT e.id, e.name, e.position, e.commission_rate,
               COUNT(ii.id) as total_services,
               COALESCE(SUM(ii.subtotal), 0) as total_revenue
        FROM employees e
        LEFT JOIN invoice_items ii ON e.id = ii.employee_id
        LEFT JOIN invoices i ON ii.invoice_id = i.id 
            AND i.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        WHERE e.status = 'active'
        GROUP BY e.id
        ORDER BY total_revenue DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Reports - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>📊 Employee Performance Reports</h1>
                <a href="employees.php" class="btn btn-secondary">← Back to Employees</a>
            </div>
            
            <!-- Filters -->
            <div class="dashboard-section">
                <h2>Filters</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Employee</label>
                            <select name="employee_id" class="form-control">
                                <option value="">All Employees Summary</option>
                                <?php while ($emp = $all_employees->fetch_assoc()): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo $emp['name']; ?> (<?php echo $emp['position']; ?>)
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
            
            <?php if ($employee_info): ?>
                <!-- Employee Details -->
                <div class="dashboard-section">
                    <h2>Employee Information</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 20px; background: #f8f9ff; border-radius: 8px;">
                        <div>
                            <strong>Name:</strong> <?php echo $employee_info['name']; ?>
                        </div>
                        <div>
                            <strong>Position:</strong> <?php echo $employee_info['position']; ?>
                        </div>
                        <div>
                            <strong>Mobile:</strong> <?php echo $employee_info['mobile']; ?>
                        </div>
                        <div>
                            <strong>Commission Rate:</strong> <?php echo $employee_info['commission_rate']; ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💼</div>
                        <div class="stat-details">
                            <h3><?php echo $total_services; ?></h3>
                            <p>Services Completed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-details">
                            <h3>Rs. <?php echo number_format($total_revenue, 2); ?></h3>
                            <p>Total Revenue Generated</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-details">
                            <h3>Rs. <?php echo number_format($commission, 2); ?></h3>
                            <p>Commission Earned</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-details">
                            <h3>Rs. <?php echo $total_services > 0 ? number_format($total_revenue / $total_services, 2) : '0.00'; ?></h3>
                            <p>Average Per Service</p>
                        </div>
                    </div>
                </div>
                
                <!-- Services Breakdown -->
                <?php if ($services_breakdown->num_rows > 0): ?>
                <div class="dashboard-section">
                    <h2>Services Breakdown</h2>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Count</th>
                                    <th>Revenue</th>
                                    <th>Commission (<?php echo $employee_info['commission_rate']; ?>%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($service = $services_breakdown->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $service['name']; ?></strong></td>
                                    <td><?php echo $service['count']; ?></td>
                                    <td><?php echo format_currency($service['revenue']); ?></td>
                                    <td><?php echo format_currency(($service['revenue'] * $employee_info['commission_rate']) / 100); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Recent Transactions -->
                <div class="dashboard-section">
                    <h2>Recent Transactions</h2>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Qty</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($transactions->num_rows > 0): ?>
                                    <?php while ($trans = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $trans['invoice_number']; ?></td>
                                        <td><?php echo format_date($trans['created_at']); ?></td>
                                        <td><?php echo $trans['customer_name']; ?></td>
                                        <td><?php echo $trans['service_name']; ?></td>
                                        <td><?php echo $trans['quantity']; ?></td>
                                        <td><?php echo format_currency($trans['subtotal']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">No transactions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- All Employees Summary -->
                <div class="dashboard-section">
                    <h2>All Employees Performance Summary</h2>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Position</th>
                                    <th>Services Done</th>
                                    <th>Revenue Generated</th>
                                    <th>Commission Rate</th>
                                    <th>Commission Earned</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($employees_summary->num_rows > 0): ?>
                                    <?php while ($emp = $employees_summary->fetch_assoc()): 
                                        $emp_commission = ($emp['total_revenue'] * $emp['commission_rate']) / 100;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $emp['name']; ?></strong></td>
                                        <td><?php echo $emp['position']; ?></td>
                                        <td><?php echo $emp['total_services']; ?></td>
                                        <td><?php echo format_currency($emp['total_revenue']); ?></td>
                                        <td><?php echo $emp['commission_rate']; ?>%</td>
                                        <td><?php echo format_currency($emp_commission); ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="window.location.href='?employee_id=<?php echo $emp['id']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>'">View Details</button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">No employees found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>