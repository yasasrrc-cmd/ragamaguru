<?php
require_once 'config.php';
requireLogin();

$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Search customers
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE mobile LIKE ? OR name LIKE ? ORDER BY created_at DESC");
    $searchTerm = "%$search%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $customers = $stmt->get_result();
} else {
    $customers = $conn->query("SELECT * FROM customers ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Customers</h1>
            <a href="customer_add.php" class="btn">+ Add Customer</a>
        </div>
        
        <div class="section">
            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="search" placeholder="Search by mobile number or name..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                    <?php if ($search): ?>
                        <a href="customers.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($customers->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>DOB</th>
                            <th>Verified</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($customer = $customers->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $customer['id']; ?></td>
                                <td><?php echo $customer['name']; ?></td>
                                <td><?php echo $customer['mobile']; ?></td>
                                <td><?php echo $customer['dob'] ? formatDate($customer['dob']) : '-'; ?></td>
                                <td>
                                    <?php if ($customer['mobile_verified']): ?>
                                        <span class="status-badge status-completed">✓ Verified</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Not Verified</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($customer['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm">View</a>
                                        <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-success">Edit</a>
                                        <a href="customer_delete.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this customer?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No customers found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>