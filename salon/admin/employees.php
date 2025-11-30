<?php
require_once '../config.php';
check_admin_login();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = clean_input($_POST['name']);
        $mobile = clean_input($_POST['mobile']);
        $email = clean_input($_POST['email']);
        $position = clean_input($_POST['position']);
        $commission_rate = floatval($_POST['commission_rate']);
        $status = clean_input($_POST['status']);
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO employees (name, mobile, email, position, commission_rate, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssds", $name, $mobile, $email, $position, $commission_rate, $status);
            if ($stmt->execute()) {
                $message = 'Employee added successfully!';
            } else {
                $error = 'Failed to add employee.';
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE employees SET name = ?, mobile = ?, email = ?, position = ?, commission_rate = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssdsi", $name, $mobile, $email, $position, $commission_rate, $status, $id);
            if ($stmt->execute()) {
                $message = 'Employee updated successfully!';
            } else {
                $error = 'Failed to update employee.';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Employee deleted successfully!';
        } else {
            $error = 'Failed to delete employee. Employee may have associated records.';
        }
    }
}

// Get all employees
$employees = $conn->query("SELECT * FROM employees ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>👨‍💼 Employees Management</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Add Employee</button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-section">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Commission %</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($employee = $employees->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $employee['name']; ?></strong></td>
                                <td><?php echo $employee['mobile']; ?></td>
                                <td><?php echo $employee['email'] ?: 'N/A'; ?></td>
                                <td><?php echo $employee['position']; ?></td>
                                <td><?php echo $employee['commission_rate']; ?>%</td>
                                <td><span class="badge badge-<?php echo $employee['status']; ?>"><?php echo ucfirst($employee['status']); ?></span></td>
                                <td class="actions">
                                    <button class="btn btn-primary btn-sm" onclick="viewPerformance(<?php echo $employee['id']; ?>)">Performance</button>
                                    <button class="btn btn-primary btn-sm" onclick='editEmployee(<?php echo json_encode($employee); ?>)'>Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Employee</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="employeeForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="employeeId">
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="employeeName" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <input type="tel" name="mobile" id="employeeMobile" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="employeeEmail" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Position *</label>
                        <input type="text" name="position" id="employeePosition" class="form-control" required placeholder="e.g., Hair Stylist">
                    </div>
                    
                    <div class="form-group">
                        <label>Commission Rate (%)</label>
                        <input type="number" name="commission_rate" id="employeeCommission" class="form-control" min="0" max="100" step="0.01" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="employeeStatus" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('employeeModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Add Employee';
            document.getElementById('formAction').value = 'add';
            document.getElementById('employeeForm').reset();
        }
        
        function closeModal() {
            document.getElementById('employeeModal').classList.remove('active');
        }
        
        function editEmployee(employee) {
            document.getElementById('employeeModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Edit Employee';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('employeeId').value = employee.id;
            document.getElementById('employeeName').value = employee.name;
            document.getElementById('employeeMobile').value = employee.mobile;
            document.getElementById('employeeEmail').value = employee.email;
            document.getElementById('employeePosition').value = employee.position;
            document.getElementById('employeeCommission').value = employee.commission_rate;
            document.getElementById('employeeStatus').value = employee.status;
        }
        
        function deleteEmployee(id) {
            if (confirm('Are you sure you want to delete this employee?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewPerformance(id) {
            window.location.href = 'employee_reports.php?employee_id=' + id;
        }
    </script>
</body>
</html>