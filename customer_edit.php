<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get customer details
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $mobile = cleanInput($_POST['mobile']);
    $dob = cleanInput($_POST['dob']);
    $address = cleanInput($_POST['address']);
    $mobile_verified = isset($_POST['mobile_verified']) ? 1 : 0;
    
    // Check if mobile is taken by another customer
    $check = $conn->prepare("SELECT id FROM customers WHERE mobile = ? AND id != ?");
    $check->bind_param("si", $mobile, $customer_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "Mobile number already registered to another customer!";
    } else {
        $stmt = $conn->prepare("UPDATE customers SET name = ?, mobile = ?, dob = ?, address = ?, mobile_verified = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $name, $mobile, $dob, $address, $mobile_verified, $customer_id);
        
        if ($stmt->execute()) {
            $success = "Customer updated successfully!";
            header("refresh:2;url=customer_view.php?id=$customer_id");
        } else {
            $error = "Error updating customer: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Edit Customer</h1>
        
        <div class="section">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <input type="tel" name="mobile" value="<?php echo htmlspecialchars($customer['mobile']); ?>" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo $customer['dob']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="mobile_verified" <?php echo $customer['mobile_verified'] ? 'checked' : ''; ?>>
                            Mobile Verified
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Update Customer</button>
                    <a href="customer_view.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>