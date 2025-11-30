<?php
session_start();

// Installation steps
$steps = ['requirements', 'database', 'admin', 'complete'];
$current_step = isset($_GET['step']) ? $_GET['step'] : 'requirements';

// Check if already installed
if (file_exists('config.php') && $current_step !== 'complete') {
    header('Location: index.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($current_step === 'database') {
        $_SESSION['db_host'] = $_POST['db_host'];
        $_SESSION['db_name'] = $_POST['db_name'];
        $_SESSION['db_user'] = $_POST['db_user'];
        $_SESSION['db_pass'] = $_POST['db_pass'];
        
        // Test database connection
        try {
            $conn = new PDO(
                "mysql:host={$_SESSION['db_host']}", 
                $_SESSION['db_user'], 
                $_SESSION['db_pass']
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $conn->exec("CREATE DATABASE IF NOT EXISTS `{$_SESSION['db_name']}`");
            $conn->exec("USE `{$_SESSION['db_name']}`");
            
            // Create tables
            $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'cashier') DEFAULT 'cashier',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                barcode VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(200) NOT NULL,
                category_id INT,
                price DECIMAL(10,2) NOT NULL,
                cost DECIMAL(10,2) NOT NULL,
                stock INT DEFAULT 0,
                min_stock INT DEFAULT 10,
                image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS sales (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_no VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                payment_method ENUM('cash', 'card', 'other') DEFAULT 'cash',
                amount_paid DECIMAL(10,2) NOT NULL,
                change_amount DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );

            CREATE TABLE IF NOT EXISTS sale_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sale_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            );

            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cash_register (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                opening_balance DECIMAL(10,2) NOT NULL,
                closing_balance DECIMAL(10,2),
                expected_balance DECIMAL(10,2),
                difference DECIMAL(10,2),
                notes TEXT,
                opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                closed_at TIMESTAMP NULL,
                status ENUM('open', 'closed') DEFAULT 'open',
                FOREIGN KEY (user_id) REFERENCES users(id)
            );

            CREATE TABLE IF NOT EXISTS expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                category VARCHAR(100) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                description TEXT,
                expense_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );

            INSERT INTO settings (setting_key, setting_value) VALUES
            ('store_name', 'My Store'),
            ('store_address', ''),
            ('store_phone', ''),
            ('tax_rate', '0'),
            ('currency', 'Rs');
            ";
            
            $conn->exec($sql);
            
            header('Location: installer.php?step=admin');
            exit;
        } catch(PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
    
    if ($current_step === 'admin') {
        $_SESSION['admin_user'] = $_POST['admin_user'];
        $_SESSION['admin_pass'] = $_POST['admin_pass'];
        $_SESSION['admin_name'] = $_POST['admin_name'];
        
        try {
            $conn = new PDO(
                "mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']}", 
                $_SESSION['db_user'], 
                $_SESSION['db_pass']
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Insert admin user
            $hashed_password = password_hash($_SESSION['admin_pass'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$_SESSION['admin_user'], $hashed_password, $_SESSION['admin_name']]);
            
            // Create config file
            $config_content = "<?php
define('DB_HOST', '{$_SESSION['db_host']}');
define('DB_NAME', '{$_SESSION['db_name']}');
define('DB_USER', '{$_SESSION['db_user']}');
define('DB_PASS', '{$_SESSION['db_pass']}');

try {
    \$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}
?>";
            
            file_put_contents('config.php', $config_content);
            
            // Clear session
            session_destroy();
            
            header('Location: installer.php?step=complete');
            exit;
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .installer { background: white; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 600px; width: 100%; overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .progress { display: flex; justify-content: space-between; padding: 20px 30px; background: #f8f9fa; }
        .progress-step { flex: 1; text-align: center; position: relative; }
        .progress-step::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 2px; background: #dee2e6; z-index: 0; }
        .progress-step:first-child::before { left: 50%; }
        .progress-step:last-child::before { right: 50%; }
        .progress-step span { display: inline-block; width: 30px; height: 30px; background: #dee2e6; border-radius: 50%; line-height: 30px; color: #6c757d; font-weight: bold; position: relative; z-index: 1; }
        .progress-step.active span { background: #667eea; color: white; }
        .progress-step.completed span { background: #28a745; color: white; }
        .progress-step p { margin-top: 10px; font-size: 12px; color: #6c757d; }
        .content { padding: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .requirements-list { list-style: none; }
        .requirements-list li { padding: 12px; margin-bottom: 10px; border-radius: 8px; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; }
        .requirements-list .badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .text-center { text-align: center; }
        .success-icon { font-size: 80px; color: #28a745; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>🏪 POS System Installer</h1>
            <p>Professional Point of Sale System</p>
        </div>
        
        <div class="progress">
            <div class="progress-step <?= $current_step === 'requirements' ? 'active' : 'completed' ?>">
                <span>1</span>
                <p>Requirements</p>
            </div>
            <div class="progress-step <?= in_array($current_step, ['database', 'admin', 'complete']) ? ($current_step === 'database' ? 'active' : 'completed') : '' ?>">
                <span>2</span>
                <p>Database</p>
            </div>
            <div class="progress-step <?= in_array($current_step, ['admin', 'complete']) ? ($current_step === 'admin' ? 'active' : 'completed') : '' ?>">
                <span>3</span>
                <p>Admin User</p>
            </div>
            <div class="progress-step <?= $current_step === 'complete' ? 'active' : '' ?>">
                <span>4</span>
                <p>Complete</p>
            </div>
        </div>
        
        <div class="content">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($current_step === 'requirements'): ?>
                <h2>System Requirements Check</h2>
                <p style="margin: 20px 0; color: #666;">Please ensure all requirements are met before proceeding.</p>
                
                <ul class="requirements-list">
                    <li>
                        <span>PHP Version (>= 7.4)</span>
                        <span class="badge <?= version_compare(PHP_VERSION, '7.4.0') >= 0 ? 'badge-success' : 'badge-danger' ?>">
                            <?= version_compare(PHP_VERSION, '7.4.0') >= 0 ? 'OK' : 'FAILED' ?> (<?= PHP_VERSION ?>)
                        </span>
                    </li>
                    <li>
                        <span>PDO Extension</span>
                        <span class="badge <?= extension_loaded('pdo') ? 'badge-success' : 'badge-danger' ?>">
                            <?= extension_loaded('pdo') ? 'OK' : 'FAILED' ?>
                        </span>
                    </li>
                    <li>
                        <span>PDO MySQL Extension</span>
                        <span class="badge <?= extension_loaded('pdo_mysql') ? 'badge-success' : 'badge-danger' ?>">
                            <?= extension_loaded('pdo_mysql') ? 'OK' : 'FAILED' ?>
                        </span>
                    </li>
                    <li>
                        <span>File Write Permission</span>
                        <span class="badge <?= is_writable('.') ? 'badge-success' : 'badge-danger' ?>">
                            <?= is_writable('.') ? 'OK' : 'FAILED' ?>
                        </span>
                    </li>
                </ul>
                
                <div style="margin-top: 30px;">
                    <a href="installer.php?step=database" class="btn">Continue to Database Setup</a>
                </div>
                
            <?php elseif ($current_step === 'database'): ?>
                <h2>Database Configuration</h2>
                <p style="margin: 20px 0; color: #666;">Enter your MySQL database details. The installer will create the database if it doesn't exist.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" value="pos_system" required>
                    </div>
                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass" value="">
                    </div>
                    <button type="submit" class="btn">Install Database</button>
                </form>
                
            <?php elseif ($current_step === 'admin'): ?>
                <h2>Create Admin Account</h2>
                <p style="margin: 20px 0; color: #666;">Create your administrator account to manage the POS system.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="admin_name" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="admin_user" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="admin_pass" required>
                    </div>
                    <button type="submit" class="btn">Complete Installation</button>
                </form>
                
            <?php elseif ($current_step === 'complete'): ?>
                <div class="text-center">
                    <div class="success-icon">✓</div>
                    <h2>Installation Complete!</h2>
                    <p style="margin: 20px 0; color: #666;">Your POS system has been successfully installed and is ready to use.</p>
                    
                    <div class="alert alert-info" style="text-align: left; margin: 30px 0;">
                        <strong>Important Security Note:</strong><br>
                        Please delete the <code>installer.php</code> file from your server for security reasons.
                    </div>
                    
                    <a href="index.php" class="btn">Go to Login Page</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>