<?php
// index.php - Public Appointment Booking Page
require_once 'config.php';

$conn = getDBConnection();

// Get categories, services, referral sources
$categories_result = $conn->query("SELECT * FROM service_categories WHERE is_active = 1");
$categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

$referral_result = $conn->query("SELECT * FROM referral_sources WHERE is_active = 1");
$referral_sources = $referral_result ? $referral_result->fetch_all(MYSQLI_ASSOC) : [];

$services_result = $conn->query("SELECT s.*, sc.name as category_name FROM services s JOIN service_categories sc ON s.category_id = sc.id WHERE s.is_active = 1");
$all_services = $services_result ? $services_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
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
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        
        .otp-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
            margin: 20px 0;
        }
        
        .links {
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 15px;
            font-size: 14px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 Book Your Appointment</h1>
        <p class="subtitle">Fill in your details to schedule your visit</p>
        
        <div id="message"></div>
        
        <form id="bookingForm">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label>Mobile Number *</label>
                <input type="tel" name="mobile" required placeholder="07XXXXXXXX" pattern="[0-9]{10}">
            </div>
            
            <div class="form-group">
                <label>Date of Birth *</label>
                <input type="date" name="dob" required>
            </div>
            
            <div class="form-group">
                <label>City *</label>
                <input type="text" name="city" required placeholder="Enter your city">
            </div>
            
            <div class="form-group">
                <label>How did you hear about us? *</label>
                <select name="referral_source" required>
                    <option value="">Select an option</option>
                    <?php foreach($referral_sources as $source): ?>
                        <option value="<?= htmlspecialchars($source['source_name']) ?>"><?= htmlspecialchars($source['source_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Service Category *</label>
                <select name="service_category" id="categorySelect" required>
                    <option value="">Select category</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="serviceGroup" style="display: none;">
                <label>Service *</label>
                <select name="service_id" id="serviceSelect" required>
                    <option value="">Select service</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Appointment Date *</label>
                <input type="date" name="appointment_date" id="appointmentDate" required>
            </div>
            
            <button type="submit" class="btn">📅 Book Appointment</button>
        </form>
        
        <div class="links">
            <a href="user-panel.php">👤 User Login</a>
            <a href="admin-panel.php">🔐 Admin Login</a>
        </div>
    </div>
    
    <!-- OTP Modal -->
    <div class="modal" id="otpModal">
        <div class="modal-content">
            <h2>Verify OTP</h2>
            <p>Enter the 4-digit code sent to your mobile</p>
            <input type="text" class="otp-input" id="otpInput" maxlength="4" pattern="[0-9]{4}" placeholder="••••">
            <button class="btn" onclick="verifyOTP()">Verify & Confirm</button>
        </div>
    </div>
    
    <script>
        const services = <?= json_encode($all_services) ?>;
        let pendingBooking = null;
        
        // Debug: Check if services are loaded
        console.log('Total services loaded:', services.length);
        console.log('Services data:', services);
        
        // Filter services by category
        document.getElementById('categorySelect').addEventListener('change', function() {
            const categoryId = this.value;
            const serviceSelect = document.getElementById('serviceSelect');
            const serviceGroup = document.getElementById('serviceGroup');
            
            console.log('Category selected:', categoryId);
            
            serviceSelect.innerHTML = '<option value="">Select service</option>';
            
            if (categoryId) {
                const filteredServices = services.filter(s => s.category_id == categoryId);
                
                console.log('Filtered services:', filteredServices);
                
                if (filteredServices.length > 0) {
                    filteredServices.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.name + (service.price > 0 ? ' - Rs. ' + parseFloat(service.price).toFixed(2) : '');
                        serviceSelect.appendChild(option);
                    });
                    serviceGroup.style.display = 'block';
                } else {
                    serviceSelect.innerHTML = '<option value="">No services available for this category</option>';
                    serviceGroup.style.display = 'block';
                    showMessage('No services found for this category. Please add services in admin panel.', 'error');
                }
            } else {
                serviceGroup.style.display = 'none';
            }
        });
        
        // Get blocked dates and set min date
        fetch('api.php?action=get_blocked_dates')
            .then(r => r.json())
            .then(data => {
                const dateInput = document.getElementById('appointmentDate');
                const today = new Date().toISOString().split('T')[0];
                dateInput.min = today;
                
                dateInput.addEventListener('input', function() {
                    const selectedDate = this.value;
                    if (data.blocked_dates.includes(selectedDate)) {
                        showMessage('This date is not available. Please select another date.', 'error');
                        this.value = '';
                    }
                });
            });
        
        // Handle form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            pendingBooking = Object.fromEntries(formData);
            
            fetch('api.php?action=send_booking_otp', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('otpModal').classList.add('active');
                    document.getElementById('otpInput').focus();
                } else {
                    showMessage(data.message, 'error');
                }
            });
        });
        
        function verifyOTP() {
            const otp = document.getElementById('otpInput').value;
            
            if (otp.length !== 4) {
                showMessage('Please enter 4-digit OTP', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('otp', otp);
            formData.append('mobile', pendingBooking.mobile);
            Object.keys(pendingBooking).forEach(key => {
                formData.append(key, pendingBooking[key]);
            });
            
            fetch('api.php?action=confirm_booking', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('otpModal').classList.remove('active');
                    showMessage('Appointment booked successfully! Confirmation sent to your mobile.', 'success');
                    document.getElementById('bookingForm').reset();
                    setTimeout(() => {
                        window.location.href = 'user-panel.php';
                    }, 2000);
                } else {
                    showMessage(data.message, 'error');
                }
            });
        }
        
        function showMessage(msg, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${msg}</div>`;
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }
        
        // Allow Enter key on OTP input
        document.getElementById('otpInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verifyOTP();
            }
        });
    </script>
</body>
</html>