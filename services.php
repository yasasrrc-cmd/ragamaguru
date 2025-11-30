<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// Handle Add/Edit Service
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $service_name = cleanInput($_POST['service_name']);
    $category_type = cleanInput($_POST['category_type']);
    $description = cleanInput($_POST['description']);
    $duration = intval($_POST['duration']);
    $price = floatval($_POST['price']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    if ($action == 'add') {
        $stmt = $conn->prepare("INSERT INTO services (category_type, service_name, description, duration, price, active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssidi", $category_type, $service_name, $description, $duration, $price, $active);
        
        if ($stmt->execute()) {
            $success = "Service added successfully!";
        } else {
            $error = "Error adding service: " . $conn->error;
        }
    } elseif ($action == 'edit') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE services SET category_type = ?, service_name = ?, description = ?, duration = ?, price = ?, active = ? WHERE id = ?");
        $stmt->bind_param("sssidii", $category_type, $service_name, $description, $duration, $price, $active, $id);
        
        if ($stmt->execute()) {
            $success = "Service updated successfully!";
        } else {
            $error = "Error updating service: " . $conn->error;
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM services WHERE id = $id")) {
        $success = "Service deleted successfully!";
    } else {
        $error = "Error deleting service: " . $conn->error;
    }
}

// Get filter
$filter_category = isset($_GET['category']) ? cleanInput($_GET['category']) : 'all';

// Get all services
if ($filter_category == 'all') {
    $services = $conn->query("SELECT * FROM services ORDER BY category_type, service_name ASC");
} else {
    $services = $conn->query("SELECT * FROM services WHERE category_type = '$filter_category' ORDER BY service_name ASC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .category-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-visit {
            background: #d4edda;
            color: #155724;
        }
        .badge-online-local {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-online-foreign {
            background: #fff3cd;
            color: #856404;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 10px 20px;
            background: #f5f5f5;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: all 0.3s;
        }
        .filter-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Services Management</h1>
            <button onclick="openAddModal()" class="btn">+ Add Service</button>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Category Filters -->
        <div class="filter-tabs">
            <a href="?category=all" class="filter-tab <?php echo $filter_category == 'all' ? 'active' : ''; ?>">
                All Services
            </a>
            <a href="?category=visit" class="filter-tab <?php echo $filter_category == 'visit' ? 'active' : ''; ?>">
                🏥 Visit Appointments
            </a>
            <a href="?category=online_local" class="filter-tab <?php echo $filter_category == 'online_local' ? 'active' : ''; ?>">
                💻 Online - Local
            </a>
            <a href="?category=online_foreign" class="filter-tab <?php echo $filter_category == 'online_foreign' ? 'active' : ''; ?>">
                🌍 Online - Foreign
            </a>
			<a href="?category=guru_services" class="filter-tab <?php echo $filter_category == 'guru_services' ? 'active' : ''; ?>">
                🏥 Guru Services
            </a>
        </div>
        
        <div class="section">
            <?php if ($services->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Service Name</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $services->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td>
                                    <?php
                                    $badge_class = '';
                                    $badge_text = '';
                                    switch($service['category_type']) {
                                        case 'visit':
                                            $badge_class = 'badge-visit';
                                            $badge_text = '🏥 Visit';
                                            break;
                                        case 'online_local':
                                            $badge_class = 'badge-online-local';
                                            $badge_text = '💻 Online Local';
                                            break;
                                        case 'online_foreign':
                                            $badge_class = 'badge-online-foreign';
                                            $badge_text = '🌍 Online Foreign';
                                            break;
									    case 'guru_services':
                                            $badge_class = 'badge-visit';
                                            $badge_text = '🏥 Guru';
                                            break;
                                    }
                                    ?>
                                    <span class="category-badge <?php echo $badge_class; ?>">
                                        <?php echo $badge_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $service['service_name']; ?></strong><br>
                                    <small><?php echo $service['description']; ?></small>
                                </td>
                                <td><?php echo $service['duration']; ?> mins</td>
                                <td>Rs. <?php echo number_format($service['price'], 2); ?></td>
                                <td>
                                    <?php if ($service['active']): ?>
                                        <span class="status-badge status-completed">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='openEditModal(<?php echo json_encode($service); ?>)' 
                                                class="btn btn-sm btn-success">Edit</button>
                                        <a href="?delete=<?php echo $service['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this service?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No services found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Service</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="serviceId">
                
                <div class="form-group">
                    <label>Service Category *</label>
                    <select name="category_type" id="categoryType" required>
                        <option value="">Select Category</option>
                        <option value="visit">🏥 Visit Appointments</option>
                        <option value="online_local">💻 Online Consultations - Local</option>
                        <option value="online_foreign">🌍 Online Consultations - Foreign</option>
						<option value="guru_services">Guru Services</option>
                    </select>
                    <small>Choose the type of appointment for this service</small>
                </div>
                
                <div class="form-group">
                    <label>Service Name *</label>
                    <input type="text" name="service_name" id="serviceName" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="serviceDescription" rows="3"></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Duration (minutes) *</label>
                        <input type="number" name="duration" id="serviceDuration" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>Price (Rs.) *</label>
                        <input type="number" name="price" id="servicePrice" required min="0" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" id="serviceActive" checked>
                        Active
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Save</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Service';
            document.getElementById('formAction').value = 'add';
            document.getElementById('serviceId').value = '';
            document.getElementById('categoryType').value = '';
            document.getElementById('serviceName').value = '';
            document.getElementById('serviceDescription').value = '';
            document.getElementById('serviceDuration').value = '';
            document.getElementById('servicePrice').value = '';
            document.getElementById('serviceActive').checked = true;
            document.getElementById('serviceModal').style.display = 'block';
        }
        
        function openEditModal(service) {
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('categoryType').value = service.category_type;
            document.getElementById('serviceName').value = service.service_name;
            document.getElementById('serviceDescription').value = service.description || '';
            document.getElementById('serviceDuration').value = service.duration;
            document.getElementById('servicePrice').value = service.price;
            document.getElementById('serviceActive').checked = service.active == 1;
            document.getElementById('serviceModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('serviceModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('serviceModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>