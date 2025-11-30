<?php
require_once '../config.php';
check_admin_login();

$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_sms') {
        $api_key = clean_input($_POST['api_key']);
        $sender_mask = clean_input($_POST['sender_mask']);
        
        $stmt = $conn->query("SELECT id FROM sms_settings LIMIT 1");
        
        if ($stmt->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE sms_settings SET api_key = ?, sender_mask = ? WHERE id = 1");
        } else {
            $stmt = $conn->prepare("INSERT INTO sms_settings (api_key, sender_mask) VALUES (?, ?)");
        }
        
        $stmt->bind_param("ss", $api_key, $sender_mask);
        
        if ($stmt->execute()) {
            $message = 'SMS settings updated successfully!';
        } else {
            $error = 'Failed to update SMS settings.';
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!password_verify($current_password, $result['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $_SESSION['admin_id']);
                
                if ($stmt->execute()) {
                    $message = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password.';
                }
            }
        }
    }
}

// Get SMS settings
$sms_settings = $conn->query("SELECT * FROM sms_settings LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>⚙️ Settings</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- SMS Settings -->
            <div class="dashboard-section">
                <h2>SMS API Configuration</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_sms">
                    
                    <div class="form-group">
                        <label>API Key *</label>
                        <input type="text" name="api_key" class="form-control" value="<?php echo $sms_settings['api_key'] ?? ''; ?>" required>
                        <small style="color: #666; display: block; margin-top: 5px;">Your Richmo.lk API Key</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Sender Mask *</label>
                        <input type="text" name="sender_mask" class="form-control" value="<?php echo $sms_settings['sender_mask'] ?? ''; ?>" required>
                        <small style="color: #666; display: block; margin-top: 5px;">Sender name that appears in SMS (e.g., SalonSMS)</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> Make sure your Richmo.lk API is active and has sufficient balance. 
                        Test the SMS functionality after updating these settings.
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save SMS Settings</button>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="dashboard-section" style="margin-top: 30px;">
                <h2>Change Password</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small style="color: #666; display: block; margin-top: 5px;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
            
            <!-- System Information -->
            <div class="dashboard-section" style="margin-top: 30px;">
                <h2>System Information</h2>
                <table class="data-table">
                    <tr>
                        <td><strong>System Name</strong></td>
                        <td><?php echo SITE_NAME; ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version</strong></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>MySQL Version</strong></td>
                        <td><?php echo $conn->server_info; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Server Time</strong></td>
                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Timezone</strong></td>
                        <td><?php echo TIMEZONE; ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Quick Links -->
            <div class="dashboard-section" style="margin-top: 30px;">
                <h2>Quick Links</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <a href="../index.php" target="_blank" class="btn btn-secondary btn-block">View User Booking Page</a>
                    <a href="availability.php" class="btn btn-secondary btn-block">Manage Availability</a>
                    <a href="services.php" class="btn btn-secondary btn-block">Manage Services</a>
                    <a href="reports.php" class="btn btn-secondary btn-block">View Reports</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>