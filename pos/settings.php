<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'store_name' => $_POST['store_name'],
        'store_address' => $_POST['store_address'],
        'store_phone' => $_POST['store_phone'],
        'tax_rate' => $_POST['tax_rate'],
        'currency' => $_POST['currency']
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    
    $success = "Settings updated successfully";
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>System Settings</h1>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Store Information</h2>
                </div>
                <form method="POST" style="padding: 30px;">
                    <div class="form-group">
                        <label>Store Name *</label>
                        <input type="text" name="store_name" value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Store Address</label>
                        <textarea name="store_address" rows="3"><?= htmlspecialchars($settings['store_address'] ?? '') ?></textarea>
                        <small style="color: #666;">This will appear on invoices</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Store Phone</label>
                        <input type="text" name="store_phone" value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tax Rate (%)</label>
                            <input type="number" name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate'] ?? '0') ?>" step="0.01" min="0">
                            <small style="color: #666;">0 for no tax</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Currency Symbol</label>
                            <input type="text" name="currency" value="<?= htmlspecialchars($settings['currency'] ?? '$') ?>" maxlength="3">
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary btn-lg">💾 Save Settings</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>System Information</h2>
                </div>
                <div style="padding: 30px;">
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 15px; background: #f8f9fa; margin-bottom: 10px; border-radius: 8px;">
                        <strong>PHP Version:</strong>
                        <span><?= PHP_VERSION ?></span>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 15px; background: #f8f9fa; margin-bottom: 10px; border-radius: 8px;">
                        <strong>Database:</strong>
                        <span>MySQL (<?= DB_NAME ?>)</span>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 15px; background: #f8f9fa; margin-bottom: 10px; border-radius: 8px;">
                        <strong>POS Version:</strong>
                        <span>1.0.0</span>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 15px; background: #f8f9fa; margin-bottom: 10px; border-radius: 8px;">
                        <strong>Installation Date:</strong>
                        <span><?= date('F d, Y', filemtime('config.php')) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header" style="background: #fff3cd;">
                    <h2>⚠️ Database Backup</h2>
                </div>
                <div style="padding: 30px;">
                    <p style="margin-bottom: 20px; color: #666;">
                        It's recommended to regularly backup your database. Use Laragon's built-in tools or phpMyAdmin to export your database.
                    </p>
                    <a href="http://localhost/phpmyadmin" target="_blank" class="btn btn-warning">
                        Open phpMyAdmin
                    </a>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>