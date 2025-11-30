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
        $dob = clean_input($_POST['dob']);
        $city = clean_input($_POST['city']);
        
        if ($action === 'add') {
            // Check if mobile already exists
            $stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
            $stmt->bind_param("s", $mobile);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Customer with this mobile number already exists.';
            } else {
                $stmt = $conn->prepare("INSERT INTO customers (name, mobile, dob, city) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $mobile, $dob, $city);
                if ($stmt->execute()) {
                    $message = 'Customer added successfully!';
                } else {
                    $error = 'Failed to add customer.';
                }
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE customers SET name = ?, mobile = ?, dob = ?, city = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $mobile, $dob, $city, $id);
            if ($stmt->execute()) {
                $message = 'Customer updated successfully!';
            } else {
                $error = 'Failed to update customer.';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Customer deleted successfully!';
        } else {
            $error = 'Failed to delete customer. Customer may have associated appointments.';
        }
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$where = '';
if ($search) {
    $search = clean_input($search);
    $where = "WHERE name LIKE '%$search%' OR mobile LIKE '%$search%' OR city LIKE '%$search%'";
}

// Get customers
$customers = $conn->query("SELECT * FROM customers $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>👥 Customers Management</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Add Customer</button>
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
                        <div class="form-group" style="flex: 1;">
                            <input type="text" name="search" class="form-control" placeholder="Search by name, mobile, or city..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search): ?>
                            <a href="customers.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Date of Birth</th>
                                <th>City</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($customers->num_rows > 0): ?>
                                <?php while ($customer = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $customer['name']; ?></strong></td>
                                    <td><?php echo $customer['mobile']; ?></td>
                                    <td><?php echo $customer['dob'] ? format_date($customer['dob']) : 'N/A'; ?></td>
                                    <td><?php echo $customer['city'] ?: 'N/A'; ?></td>
                                    <td><?php echo format_date($customer['created_at']); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-primary btn-sm" onclick="viewHistory(<?php echo $customer['id']; ?>)">History</button>
                                        <button class="btn btn-primary btn-sm" onclick='editCustomer(<?php echo json_encode($customer); ?>)'>Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteCustomer(<?php echo $customer['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">No customers found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Customer</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="customerForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="customerId">
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="customerName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" name="mobile" id="customerMobile" class="form-control" required pattern="[0-9]{10}">
                </div>
                
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" id="customerDob" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" id="customerCity" class="form-control">
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('customerModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Add Customer';
            document.getElementById('formAction').value = 'add';
            document.getElementById('customerForm').reset();
        }
        
        function closeModal() {
            document.getElementById('customerModal').classList.remove('active');
        }
        
        function editCustomer(customer) {
            document.getElementById('customerModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Edit Customer';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('customerId').value = customer.id;
            document.getElementById('customerName').value = customer.name;
            document.getElementById('customerMobile').value = customer.mobile;
            document.getElementById('customerDob').value = customer.dob;
            document.getElementById('customerCity').value = customer.city;
        }
        
        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewHistory(id) {
            window.location.href = 'reports.php?customer_id=' + id;
        }
    </script>
</body>
</html>