<?php
// user-panel.php - Customer Dashboard
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 400px;
                width: 100%;
            }
            h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
            .form-group { margin-bottom: 20px; }
            label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
            input {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                font-size: 16px;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            .btn {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                margin-top: 10px;
            }
            .btn:hover { transform: translateY(-2px); }
            .message {
                padding: 12px;
                border-radius: 10px;
                margin-bottom: 20px;
                font-size: 14px;
            }
            .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .back-link { text-align: center; margin-top: 20px; }
            .back-link a { color: #667eea; text-decoration: none; }
            .otp-input { font-size: 24px; text-align: center; letter-spacing: 10px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>👤 User Login</h1>
            <div id="message"></div>
            
            <div id="mobileForm">
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="tel" id="mobile" placeholder="07XXXXXXXX" pattern="[0-9]{10}" required>
                </div>
                <button class="btn" onclick="sendLoginOTP()">Send OTP</button>
            </div>
            
            <div id="otpForm" style="display:none;">
                <div class="form-group">
                    <label>Enter OTP</label>
                    <input type="text" id="otp" class="otp-input" maxlength="4" placeholder="••••">
                </div>
                <button class="btn" onclick="verifyLogin()">Verify & Login</button>
            </div>
            
            <div class="back-link">
                <a href="index.php">← Back to Booking</a>
            </div>
        </div>
        
        <script>
            let userMobile = '';
            
            function sendLoginOTP() {
                const mobile = document.getElementById('mobile').value;
                if (mobile.length !== 10) {
                    showMessage('Please enter valid 10-digit mobile number', 'error');
                    return;
                }
                
                userMobile = mobile;
                const formData = new FormData();
                formData.append('mobile', mobile);
                
                fetch('api.php?action=send_login_otp', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('mobileForm').style.display = 'none';
                        document.getElementById('otpForm').style.display = 'block';
                        document.getElementById('otp').focus();
                        showMessage('OTP sent to your mobile', 'success');
                    } else {
                        showMessage(data.message, 'error');
                    }
                });
            }
            
            function verifyLogin() {
                const otp = document.getElementById('otp').value;
                if (otp.length !== 4) {
                    showMessage('Please enter 4-digit OTP', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('mobile', userMobile);
                formData.append('otp', otp);
                
                fetch('api.php?action=verify_login', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        showMessage(data.message, 'error');
                    }
                });
            }
            
            function showMessage(msg, type) {
                document.getElementById('message').innerHTML = `<div class="message ${type}">${msg}</div>`;
            }
            
            document.getElementById('otp').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') verifyLogin();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// User is logged in - show dashboard
$conn = getDBConnection();
$customer_id = $_SESSION['customer_id'];

// Get customer details
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Get appointments
$stmt = $conn->prepare("SELECT a.*, s.name as service_name, sc.name as category_name 
                        FROM appointments a 
                        JOIN services s ON a.service_id = s.id 
                        JOIN service_categories sc ON s.category_id = sc.id
                        WHERE a.customer_id = ? 
                        ORDER BY a.appointment_date DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get treatments
$stmt = $conn->prepare("SELECT t.*, a.appointment_date 
                        FROM treatments t 
                        JOIN appointments a ON t.appointment_id = a.id
                        WHERE t.customer_id = ? 
                        ORDER BY t.created_at DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$treatments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get bills
$stmt = $conn->prepare("SELECT * FROM bills WHERE customer_id = ? ORDER BY bill_date DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 24px; }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .info-item label {
            font-size: 12px;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        .info-item value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 14px;
        }
        td { font-size: 14px; }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .upload-form {
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .upload-form input[type="file"] {
            margin: 10px 0;
        }
        @media (max-width: 768px) {
            table { font-size: 12px; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>👋 Welcome, <?= htmlspecialchars($customer['name']) ?></h1>
            <p style="opacity: 0.9; margin-top: 5px;">Mobile: <?= htmlspecialchars($customer['mobile']) ?></p>
        </div>
        <a href="api.php?action=logout" class="logout-btn">Logout</a>
    </div>
    
    <div class="info-card">
        <h2>📊 Your Account Summary</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Free Visits Available</label>
                <value style="color: #667eea; font-size: 24px;"><?= $customer['free_visits'] ?></value>
            </div>
            <div class="info-item">
                <label>Total Appointments</label>
                <value><?= count($appointments) ?></value>
            </div>
            <div class="info-item">
                <label>Total Bills</label>
                <value><?= count($bills) ?></value>
            </div>
            <div class="info-item">
                <label>Member Since</label>
                <value><?= date('M Y', strtotime($customer['created_at'])) ?></value>
            </div>
        </div>
    </div>
    
    <div class="info-card">
        <h2>📅 My Appointments</h2>
        <?php if (empty($appointments)): ?>
            <p style="color: #666; margin-top: 15px;">No appointments found. <a href="index.php">Book your first appointment</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($appointments as $apt): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($apt['appointment_date'])) ?></td>
                            <td><?= htmlspecialchars($apt['service_name']) ?></td>
                            <td><?= htmlspecialchars($apt['category_name']) ?></td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($apt['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="info-card">
        <h2>💊 Treatment History</h2>
        <?php if (empty($treatments)): ?>
            <p style="color: #666; margin-top: 15px;">No treatment records yet.</p>
        <?php else: ?>
            <?php foreach($treatments as $treatment): ?>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 10px; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong>Date: <?= date('d M Y', strtotime($treatment['appointment_date'])) ?></strong>
                        <?php if ($treatment['ready_for_billing']): ?>
                            <span class="badge badge-success">Ready for Billing</span>
                        <?php endif; ?>
                    </div>
                    <p style="color: #666; white-space: pre-line;"><?= htmlspecialchars($treatment['treatment_details']) ?></p>
                    <?php if ($treatment['doctor_notes']): ?>
                        <p style="margin-top: 10px; color: #667eea;"><strong>Notes:</strong> <?= htmlspecialchars($treatment['doctor_notes']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="info-card">
        <h2>💳 My Bills</h2>
        <?php if (empty($bills)): ?>
            <p style="color: #666; margin-top: 15px;">No bills yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bills as $bill): ?>
                        <tr>
                            <td><?= htmlspecialchars($bill['bill_number']) ?></td>
                            <td><?= date('d M Y', strtotime($bill['bill_date'])) ?></td>
                            <td>Rs. <?= number_format($bill['total_amount'], 2) ?></td>
                            <td>Rs. <?= number_format($bill['advance_paid'], 2) ?></td>
                            <td>
                                <?php if ($bill['balance'] > 0): ?>
                                    <span style="color: #dc3545;">Rs. <?= number_format($bill['balance'], 2) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bill['balance'] > 0): ?>
                                    <button class="btn btn-primary" onclick="showUploadForm(<?= $bill['id'] ?>)">Upload Payment</button>
                                    <div id="upload-<?= $bill['id'] ?>" class="upload-form" style="display:none;">
                                        <form onsubmit="uploadPayment(event, <?= $bill['id'] ?>)">
                                            <input type="file" name="payment_slip" accept="image/*" required>
                                            <input type="number" name="amount" placeholder="Amount" step="0.01" required>
                                            <button type="submit" class="btn btn-primary">Upload</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <script>
        function showUploadForm(billId) {
            document.getElementById('upload-' + billId).style.display = 'block';
        }
        
        function uploadPayment(e, billId) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('bill_id', billId);
            
            fetch('api.php?action=upload_payment', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>