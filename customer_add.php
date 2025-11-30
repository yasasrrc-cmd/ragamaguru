<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $mobile = cleanInput($_POST['mobile']);
    $dob = cleanInput($_POST['dob']);
    $address = cleanInput($_POST['address']);
    $send_verification = isset($_POST['send_verification']);
    
    // Check if mobile already exists
    $check = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
    $check->bind_param("s", $mobile);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Mobile number already registered!";
    } else {
        // Generate verification code
        $verificationCode = rand(100000, 999999);
        
        $stmt = $conn->prepare("INSERT INTO customers (name, mobile, dob, address, verification_code, mobile_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $name, $mobile, $dob, $address, $verificationCode);
        
        if ($stmt->execute()) {
            $customerId = $conn->insert_id;
            
            // Send verification SMS if requested
            if ($send_verification) {
                $message = "Welcome to Ragamaguru! Your verification code is: $verificationCode";
                if (sendSMS($mobile, $message)) {
                    $success = "Customer added successfully! Verification SMS sent.";
                } else {
                    $success = "Customer added successfully! (SMS sending failed)";
                }
            } else {
                $success = "Customer added successfully!";
            }
            
            // Redirect after 2 seconds
            header("refresh:2;url=customer_view.php?id=$customerId");
        } else {
            $error = "Error adding customer: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Add New Customer</h1>
        
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
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <input type="tel" name="mobile" placeholder="947xxxxxxxx" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="send_verification" checked>
                            Send Verification SMS
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Add Customer</button>
                    <a href="customers.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>