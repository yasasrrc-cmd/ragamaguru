<?php
require_once '../config.php';
check_admin_login();

$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id']);
    
    if ($action === 'update_status') {
        $status = clean_input($_POST['status']);
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            $message = 'Appointment status updated successfully!';
            
            // Send SMS notification
            $stmt = $conn->prepare("
                SELECT c.mobile, c.name, s.name as service_name, a.appointment_date, a.appointment_time
                FROM appointments a
                JOIN customers c ON a.customer_id = c.id
                JOIN services s ON a.service_id = s.id
                WHERE a.id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();
            
            $sms_message = "Your appointment status: " . ucfirst($status) . "\n" .
                          "Service: " . $appointment['service_name'] . "\n" .
                          "Date: " . date('d M Y', strtotime($appointment['appointment_date'])) . "\n" .
                          "Time: " . date('h:i A', strtotime($appointment['appointment_time']));
            
            send_sms($appointment['mobile'], $sms_message);
        } else {
            $error = 'Failed to update status.';
        }
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Appointment deleted successfully!';
        } else {
            $error = 'Failed to delete appointment.';
        }
    }
}

// Filter options
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_customer = $_GET['customer'] ?? '';

$where = [];
if ($filter_date) {
    $where[] = "a.appointment_date = '" . clean_input($filter_date) . "'";
}
if ($filter_status) {
    $where[] = "a.status = '" . clean_input($filter_status) . "'";
}
if ($filter_customer) {
    $where[] = "(c.name LIKE '%" . clean_input($filter_customer) . "%' OR c.mobile LIKE '%" . clean_input($filter_customer) . "%')";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get appointments
$appointments = $conn->query("
    SELECT a.*, c.name as customer_name, c.mobile, s.name as service_name, s.price, s.duration,
           e.name as employee_name
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    LEFT JOIN employees e ON a.employee_id = e.id
    $where_clause
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>📅 Appointments Management</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-section">
                <form method="GET" style="margin-bottom: 20px;">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
                        </div>
                        <div class="form-group">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <input type="text" name="customer" class="form-control" placeholder="Search customer..." value="<?php echo htmlspecialchars($filter_customer); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="appointments.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Duration</th>
                                <th>Employee</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments->num_rows > 0): ?>
                                <?php while ($apt = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $apt['id']; ?></td>
                                    <td><strong><?php echo $apt['customer_name']; ?></strong></td>
                                    <td><?php echo $apt['mobile']; ?></td>
                                    <td><?php echo $apt['service_name']; ?></td>
                                    <td><?php echo format_date($apt['appointment_date']); ?></td>
                                    <td><?php echo format_time($apt['appointment_time']); ?></td>
                                    <td><?php echo $apt['duration']; ?> min</td>
                                    <td><?php echo $apt['employee_name'] ?: 'Not Assigned'; ?></td>
                                    <td><?php echo format_currency($apt['price']); ?></td>
                                    <td>
                                        <select onchange="updateStatus(<?php echo $apt['id']; ?>, this.value)" class="form-control">
                                            <option value="pending" <?php echo $apt['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $apt['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="completed" <?php echo $apt['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $apt['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </td>
                                    <td class="actions">
                                        <button class="btn btn-danger btn-sm" onclick="deleteAppointment(<?php echo $apt['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px;">No appointments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function updateStatus(id, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="status" value="${status}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function deleteAppointment(id) {
            if (confirm('Are you sure you want to delete this appointment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>