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
        $description = clean_input($_POST['description']);
        $duration = intval($_POST['duration']);
        $price = floatval($_POST['price']);
        $status = clean_input($_POST['status']);
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO services (name, description, duration, price, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssids", $name, $description, $duration, $price, $status);
            if ($stmt->execute()) {
                $message = 'Service added successfully!';
            } else {
                $error = 'Failed to add service.';
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, duration = ?, price = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssidsi", $name, $description, $duration, $price, $status, $id);
            if ($stmt->execute()) {
                $message = 'Service updated successfully!';
            } else {
                $error = 'Failed to update service.';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Service deleted successfully!';
        } else {
            $error = 'Failed to delete service. It may have associated appointments.';
        }
    }
}

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>💆 Services Management</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Add Service</button>
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
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($service = $services->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $service['name']; ?></strong></td>
                                <td><?php echo substr($service['description'], 0, 50) . '...'; ?></td>
                                <td><?php echo $service['duration']; ?> min</td>
                                <td><?php echo format_currency($service['price']); ?></td>
                                <td><span class="badge badge-<?php echo $service['status']; ?>"><?php echo ucfirst($service['status']); ?></span></td>
                                <td class="actions">
                                    <button class="btn btn-primary btn-sm" onclick='editService(<?php echo json_encode($service); ?>)'>Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteService(<?php echo $service['id']; ?>)">Delete</button>
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
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Service</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="serviceForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="serviceId">
                
                <div class="form-group">
                    <label>Service Name *</label>
                    <input type="text" name="name" id="serviceName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="serviceDescription" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Duration (minutes) *</label>
                        <input type="number" name="duration" id="serviceDuration" class="form-control" required min="15" step="15">
                    </div>
                    
                    <div class="form-group">
                        <label>Price (Rs.) *</label>
                        <input type="number" name="price" id="servicePrice" class="form-control" required min="0" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="serviceStatus" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Service</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('serviceModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Add Service';
            document.getElementById('formAction').value = 'add';
            document.getElementById('serviceForm').reset();
        }
        
        function closeModal() {
            document.getElementById('serviceModal').classList.remove('active');
        }
        
        function editService(service) {
            document.getElementById('serviceModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceName').value = service.name;
            document.getElementById('serviceDescription').value = service.description;
            document.getElementById('serviceDuration').value = service.duration;
            document.getElementById('servicePrice').value = service.price;
            document.getElementById('serviceStatus').value = service.status;
        }
        
        function deleteService(id) {
            if (confirm('Are you sure you want to delete this service?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>