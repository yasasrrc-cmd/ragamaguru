<?php
require_once 'config.php';

// Get active services
$services = $conn->query("SELECT * FROM services WHERE active = 1 ORDER BY service_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Ragamaguru</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 48px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        /* Step Indicator */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .step.active .step-circle {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }
        
        .step.completed .step-circle {
            background: #28a745;
            color: white;
        }
        
        .step.completed .step-circle::after {
            content: '✓';
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
        }
        
        .step.active .step-label {
            color: #667eea;
            font-weight: bold;
        }
        
        /* Form Sections */
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        .form-section h2 {
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        small {
            color: #666;
            font-size: 12px;
        }
        
        /* Category Selection */
        .category-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .category-card {
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .category-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .category-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .category-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: inherit;
        }
        
        .category-card p {
            font-size: 14px;
            color: inherit;
            opacity: 0.8;
        }
        
        .selected-category-display {
            background: #f0f3ff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        /* Service Grid */
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .service-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .service-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .service-card.selected {
            border-color: #667eea;
            background: #f0f3ff;
        }
        
        .service-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .service-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            min-height: 40px;
        }
        
        .service-price {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .service-duration {
            font-size: 12px;
            color: #999;
        }
        
        /* Calendar */
        .calendar-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-navigation button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
        }
        
        .calendar-navigation span {
            font-size: 18px;
            font-weight: bold;
        }
        
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        
        .calendar-header {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            color: #666;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            background: white;
        }
        
        .calendar-day:hover:not(.disabled):not(.blocked) {
            border-color: #667eea;
            background: #f0f3ff;
            transform: scale(1.05);
        }
        
        .calendar-day.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .calendar-day.disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
        }
        
        .calendar-day.blocked {
            background: #ffe5e5;
            color: #999;
            cursor: not-allowed;
            position: relative;
        }
        
        .calendar-day.blocked::after {
            content: '×';
            font-size: 24px;
            color: #dc3545;
        }
        
        .calendar-day.today {
            border-color: #28a745;
            font-weight: bold;
        }
        
        /* Time Slots */
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        
        .time-slot {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            background: white;
        }
        
        .time-slot:hover:not(.disabled) {
            border-color: #667eea;
            background: #f0f3ff;
            transform: translateY(-2px);
        }
        
        .time-slot.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .time-slot.disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        /* OTP Input */
        .otp-input {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .otp-digit {
            width: 60px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .otp-digit:focus {
            border-color: #667eea;
            background: #f0f3ff;
        }
        
        /* Buttons */
        .btn {
            padding: 15px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .button-group .btn {
            flex: 1;
        }
        
        /* Summary Box */
        .summary-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .summary-box h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        
        .summary-item span {
            color: #666;
        }
        
        .summary-item strong {
            color: #333;
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Loading Spinner */
        .loading {
            display: none;
            text-align: center;
            padding: 30px;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Links */
        a {
            color: #667eea;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .booking-card {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 36px;
            }
            
            .service-grid {
                grid-template-columns: 1fr;
            }
            
            .calendar {
                gap: 5px;
            }
            
            .time-slots {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            
            .otp-digit {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
            
            .step-label {
                font-size: 10px;
            }
            
            .category-selection {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ragamaguru</h1>
            <p>Book Your Spa Appointment</p>
        </div>
        
        <div class="booking-card">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Your Info</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Service</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Date & Time</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Verify OTP</div>
                </div>
                <div class="step" data-step="5">
                    <div class="step-circle">5</div>
                    <div class="step-label">Confirm</div>
                </div>
            </div>
            
            <div id="alertBox"></div>
            
            <!-- Step 1: Customer Info -->
            <div class="form-section active" id="step1">
                <h2>Your Information</h2>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="customerName" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" id="customerMobile" placeholder="947XXXXXXXX" required>
                    <small>Format: 947XXXXXXXX</small>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="customerDOB">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea id="customerAddress" rows="3"></textarea>
                </div>
                <div class="button-group">
                    <button class="btn" onclick="goToStep(2)">Next →</button>
                </div>
            </div>
            
            <!-- Step 2: Select Category -->
            <div class="form-section" id="step2">
                <h2>Select Appointment Type</h2>
                <div class="category-selection" id="categorySelection">
                    <div class="category-card" data-category="visit" onclick="selectCategory('visit')">
                        <div class="category-icon">🏥</div>
                        <h3>Visit Appointments</h3>
                        <p>In-person appointments at our location</p>
                    </div>
                    <div class="category-card" data-category="online_local" onclick="selectCategory('online_local')">
                        <div class="category-icon">💻</div>
                        <h3>Online - Local</h3>
                        <p>Online consultations for Sri Lankan customers</p>
                    </div>
                    <div class="category-card" data-category="online_foreign" onclick="selectCategory('online_foreign')">
                        <div class="category-icon">🌍</div>
                        <h3>Online - Foreign</h3>
                        <p>Online consultations for international customers</p>
                    </div>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(1)">← Back</button>
                </div>
            </div>
            
            <!-- Step 3: Select Service -->
            <div class="form-section" id="step3">
                <h2>Select a Service</h2>
                <div class="selected-category-display" id="selectedCategoryDisplay"></div>
                <div class="service-grid" id="serviceGrid">
                    <p style="text-align: center; color: #999;">Loading services...</p>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(2)">← Back</button>
                    <button class="btn" onclick="goToStep(4)">Next →</button>
                </div>
            </div>
            
            <!-- Step 4: Select Date & Time -->
            <div class="form-section" id="step4">
                <h2>Select Date & Time</h2>
                
                <h3 style="margin-top: 20px;">Select Date</h3>
                <div class="calendar-navigation">
                    <button onclick="changeMonth(-1)">←</button>
                    <span id="currentMonth"></span>
                    <button onclick="changeMonth(1)">→</button>
                </div>
                <div class="calendar" id="calendar"></div>
                
                <div id="timeSlotsSection" style="display: none;">
                    <h3 style="margin-top: 30px;">Available Time Slots</h3>
                    <div class="time-slots" id="timeSlots"></div>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(2)">← Back</button>
                    <button class="btn" onclick="proceedToVerification()">Next →</button>
                </div>
            </div>
            
            <!-- Step 4: OTP Verification -->
            <div class="form-section" id="step5">
                <h2>Verify Your Mobile</h2>
                <p style="text-align: center; margin: 20px 0;">
                    We've sent a 6-digit verification code to<br>
                    <strong id="displayMobile"></strong>
                </p>
                
                <div class="otp-input">
                    <input type="text" maxlength="1" class="otp-digit" id="otp1" onkeyup="moveToNext(this, 'otp2')" onpaste="handlePaste(event)">
                    <input type="text" maxlength="1" class="otp-digit" id="otp2" onkeyup="moveToNext(this, 'otp3')">
                    <input type="text" maxlength="1" class="otp-digit" id="otp3" onkeyup="moveToNext(this, 'otp4')">
                    <input type="text" maxlength="1" class="otp-digit" id="otp4" onkeyup="moveToNext(this, 'otp5')">
                    <input type="text" maxlength="1" class="otp-digit" id="otp5" onkeyup="moveToNext(this, 'otp6')">
                    <input type="text" maxlength="1" class="otp-digit" id="otp6" onkeyup="moveToNext(this, null)">
                </div>
                
                <p style="text-align: center; margin-top: 20px;">
                    Didn't receive code? <a href="#" onclick="resendOTP(); return false;">Resend Code</a>
                </p>
                
                <div class="loading" id="verifyLoading">
                    <div class="spinner"></div>
                    <p>Verifying...</p>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(4)">← Back</button>
                    <button class="btn" onclick="verifyOTP()">Verify & Continue →</button>
                </div>
            </div>
            
            <!-- Step 5: Confirmation -->
            <div class="form-section" id="step6">
                <h2>Confirm Your Booking</h2>
                
                <div class="summary-box">
                    <div class="summary-item">
                        <span>Name:</span>
                        <strong id="summaryName"></strong>
                    </div>
                    <div class="summary-item">
                        <span>Mobile:</span>
                        <strong id="summaryMobile"></strong>
                    </div>
                    <div class="summary-item">
                        <span>Service:</span>
                        <strong id="summaryService"></strong>
                    </div>
                    <div class="summary-item">
                        <span>Date:</span>
                        <strong id="summaryDate"></strong>
                    </div>
                    <div class="summary-item">
                        <span>Time:</span>
                        <strong id="summaryTime"></strong>
                    </div>
                    <div class="summary-item">
                        <span>Amount:</span>
                        <strong id="summaryPrice"></strong>
                    </div>
                </div>
                
                <div class="loading" id="bookingLoading">
                    <div class="spinner"></div>
                    <p>Confirming your booking...</p>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(3)">← Back</button>
                    <button class="btn" onclick="confirmBooking()">Confirm Booking</button>
                </div>
            </div>
            
            <!-- Success Message -->
            <div class="form-section" id="stepSuccess">
                <div style="text-align: center;">
                    <div style="font-size: 72px; color: #28a745; margin-bottom: 20px;">✓</div>
                    <h2 style="color: #28a745;">Booking Confirmed!</h2>
                    <p style="margin: 20px 0; font-size: 18px;">
                        Your appointment has been successfully booked.<br>
                        We've sent a confirmation SMS to your mobile number.
                    </p>
                    <div class="summary-box" style="text-align: left;">
                        <h3>Booking Details</h3>
                        <div class="summary-item">
                            <span>Booking ID:</span>
                            <strong id="bookingId"></strong>
                        </div>
                        <div class="summary-item">
                            <span>Name:</span>
                            <strong id="successName"></strong>
                        </div>
                        <div class="summary-item">
                            <span>Service:</span>
                            <strong id="successService"></strong>
                        </div>
                        <div class="summary-item">
                            <span>Date & Time:</span>
                            <strong id="successDateTime"></strong>
                        </div>
                        <div class="summary-item">
                            <span>Amount:</span>
                            <strong id="successAmount"></strong>
                        </div>
                    </div>
                    <p style="margin-top: 20px; color: #666;">
                        Please arrive 10 minutes before your appointment time.
                    </p>
                    <button class="btn" onclick="location.reload()">Book Another Appointment</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Booking State
        let bookingData = {
            name: '',
            mobile: '',
            dob: '',
            address: '',
            categoryType: '',
            serviceId: null,
            serviceName: '',
            servicePrice: 0,
            serviceDuration: 0,
            date: '',
            time: '',
            customerId: null
        };

        let currentStep = 1;
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let blockedDates = [];
        let blockedSlots = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadBlockedDates();
        });

        // Category Selection
        function selectCategory(categoryType) {
            // Remove previous selection
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection
            event.currentTarget.classList.add('selected');
            
            bookingData.categoryType = categoryType;
            
            // Load services for this category
            loadServicesByCategory(categoryType);
            
            // Move to service selection after a brief delay
            setTimeout(() => {
                goToStep(3);
            }, 500);
        }

        // Load Services by Category
        function loadServicesByCategory(categoryType) {
            const serviceGrid = document.getElementById('serviceGrid');
            const categoryDisplay = document.getElementById('selectedCategoryDisplay');
            
            // Update category display
            const categoryNames = {
                'visit': '🏥 Visit Appointments',
                'online_local': '💻 Online - Local',
                'online_foreign': '🌍 Online - Foreign'
            };
            
            categoryDisplay.innerHTML = `<strong>Selected:</strong> ${categoryNames[categoryType]}`;
            
            // Show loading
            serviceGrid.innerHTML = '<p style="text-align: center; color: #999;">Loading services...</p>';
            
            // Fetch services
            fetch(`api_services_by_category.php?category=${categoryType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.services.length > 0) {
                        let html = '';
                        data.services.forEach(service => {
                            html += `
                                <div class="service-card" 
                                     data-id="${service.id}"
                                     data-name="${service.service_name}"
                                     data-price="${service.price}"
                                     data-duration="${service.duration}"
                                     onclick="selectService(this)">
                                    <h3>${service.service_name}</h3>
                                    <p>${service.description || ''}</p>
                                    <div class="service-price">Rs. ${parseFloat(service.price).toFixed(2)}</div>
                                    <div class="service-duration">${service.duration} minutes</div>
                                </div>
                            `;
                        });
                        serviceGrid.innerHTML = html;
                    } else {
                        serviceGrid.innerHTML = '<p style="text-align: center; color: #999;">No services available for this category. Please contact us.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    serviceGrid.innerHTML = '<p style="text-align: center; color: #dc3545;">Error loading services. Please try again.</p>';
                });
        }

        // Service Selection
        function selectService(element) {
            // Remove previous selection
            document.querySelectorAll('.service-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection
            element.classList.add('selected');
            
            bookingData.serviceId = element.dataset.id;
            bookingData.serviceName = element.dataset.name;
            bookingData.servicePrice = parseFloat(element.dataset.price);
            bookingData.serviceDuration = parseInt(element.dataset.duration);
        }

        // Step Navigation
        function goToStep(step) {
            const stepNum = parseFloat(step);
            
            // Validate current step before moving forward
            if (stepNum > currentStep && stepNum !== 3) {
                if (!validateStep(currentStep)) {
                    return;
                }
            }
            
            // Hide all sections
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            const stepId = 'step' + Math.floor(stepNum);
            const targetSection = document.getElementById(stepId);
            
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Update step indicator
            document.querySelectorAll('.step').forEach(s => {
                const sNum = parseInt(s.dataset.step);
                s.classList.remove('active', 'completed');
                if (sNum === Math.floor(stepNum)) {
                    s.classList.add('active');
                } else if (sNum < Math.floor(stepNum)) {
                    s.classList.add('completed');
                }
            });
            
            currentStep = Math.floor(stepNum);
            
            // Special actions for certain steps
            if (stepNum === 4) {
                generateCalendar();
            }
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Validate Step
        function validateStep(step) {
            if (step === 1) {
                const name = document.getElementById('customerName').value.trim();
                const mobile = document.getElementById('customerMobile').value.trim();
                
                if (!name) {
                    showAlert('Please enter your name', 'danger');
                    return false;
                }
                
                if (!mobile || !mobile.match(/^947\d{8}$/)) {
                    showAlert('Please enter a valid mobile number (947XXXXXXXX)', 'danger');
                    return false;
                }
                
                bookingData.name = name;
                bookingData.mobile = mobile;
                bookingData.dob = document.getElementById('customerDOB').value;
                bookingData.address = document.getElementById('customerAddress').value;
                return true;
            }
            
            if (step === 2) {
                if (!bookingData.categoryType) {
                    showAlert('Please select an appointment type', 'danger');
                    return false;
                }
                return true;
            }
            
            if (step === 3) {
                if (!bookingData.serviceId) {
                    showAlert('Please select a service', 'danger');
                    return false;
                }
                return true;
            }
            
            if (step === 4) {
                if (!bookingData.date) {
                    showAlert('Please select a date', 'danger');
                    return false;
                }
                if (!bookingData.time) {
                    showAlert('Please select a time slot', 'danger');
                    return false;
                }
                return true;
            }
            
            return true;
        }

        // Load Blocked Dates
        function loadBlockedDates() {
            fetch('api_blocked_dates.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        blockedDates = data.blocked_dates;
                        blockedSlots = data.blocked_slots;
                    }
                })
                .catch(error => console.error('Error loading blocked dates:', error));
        }

        // Calendar Functions
        function generateCalendar() {
            const calendar = document.getElementById('calendar');
            const monthDisplay = document.getElementById('currentMonth');
            
            calendar.innerHTML = '';
            
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                'July', 'August', 'September', 'October', 'November', 'December'];
            
            monthDisplay.textContent = monthNames[currentMonth] + ' ' + currentYear;
            
            // Day headers
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(day => {
                const header = document.createElement('div');
                header.className = 'calendar-header';
                header.textContent = day;
                calendar.appendChild(header);
            });
            
            // Get first day of month and number of days
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Empty cells before month starts
            for (let i = 0; i < firstDay; i++) {
                const emptyDay = document.createElement('div');
                calendar.appendChild(emptyDay);
            }
            
            // Days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.textContent = day;
                
                const dateObj = new Date(currentYear, currentMonth, day);
                const dateStr = dateObj.toISOString().split('T')[0];
                
                // Check if today
                if (dateObj.getTime() === today.getTime()) {
                    dayElement.classList.add('today');
                }
                
                // Disable past dates
                if (dateObj < today) {
                    dayElement.classList.add('disabled');
                }
                // Check if date is blocked
                else if (blockedDates.includes(dateStr)) {
                    dayElement.classList.add('blocked');
                    dayElement.title = 'Not available';
                }
                // Check if it's Sunday (day 0) - default closed
                else if (dateObj.getDay() === 0) {
                    dayElement.classList.add('blocked');
                    dayElement.title = 'Closed on Sundays';
                }
                else {
                    dayElement.onclick = function() {
                        selectDate(dateStr, this);
                    };
                }
                
                calendar.appendChild(dayElement);
            }
        }

        function changeMonth(direction) {
            currentMonth += direction;
            
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            } else if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            
            // Don't allow going to past months
            const today = new Date();
            if (currentYear < today.getFullYear() || 
                (currentYear === today.getFullYear() && currentMonth < today.getMonth())) {
                currentMonth = today.getMonth();
                currentYear = today.getFullYear();
                showAlert('Cannot select past dates', 'danger');
                return;
            }
            
            generateCalendar();
        }

        // Date Selection
        function selectDate(date, element) {
            // Remove previous selection
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.classList.remove('selected');
            });
            
            // Add selection
            element.classList.add('selected');
            bookingData.date = date;
            
            // Show time slots section and load slots
            document.getElementById('timeSlotsSection').style.display = 'block';
            loadTimeSlots(date);
        }

        // Load Time Slots
        function loadTimeSlots(date) {
            const timeSlotsContainer = document.getElementById('timeSlots');
            timeSlotsContainer.innerHTML = '<p style="text-align: center; color: #666;">Loading available slots...</p>';
            
            fetch(`api_time_slots.php?date=${date}&service_id=${bookingData.serviceId}`)
                .then(response => response.json())
                .then(data => {
                    timeSlotsContainer.innerHTML = '';
                    
                    if (data.success && data.slots && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const timeSlot = document.createElement('div');
                            timeSlot.className = 'time-slot';
                            timeSlot.textContent = slot.time;
                            
                            if (!slot.available) {
                                timeSlot.classList.add('disabled');
                                timeSlot.title = 'Not available';
                            } else {
                                timeSlot.onclick = function() {
                                    selectTime(slot.time, this);
                                };
                            }
                            
                            timeSlotsContainer.appendChild(timeSlot);
                        });
                    } else {
                        timeSlotsContainer.innerHTML = '<p style="text-align: center; color: #999;">No available slots for this date. Please select another date.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    timeSlotsContainer.innerHTML = '<p style="text-align: center; color: #dc3545;">Error loading time slots. Please try again.</p>';
                });
        }

        // Time Selection
        function selectTime(time, element) {
            // Remove previous selection
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Add selection
            element.classList.add('selected');
            bookingData.time = time;
        }

        // Proceed to Verification (Send OTP)
        function proceedToVerification() {
            if (!validateStep(4)) {
                return;
            }
            
            showAlert('Sending verification code...', 'info');
            
            fetch('api_send_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: bookingData.name,
                    mobile: bookingData.mobile,
                    dob: bookingData.dob,
                    address: bookingData.address
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bookingData.customerId = data.customer_id;
                    document.getElementById('displayMobile').textContent = bookingData.mobile;
                    goToStep(5);
                    showAlert('Verification code sent to your mobile!', 'success');
                    // Clear OTP inputs
                    for (let i = 1; i <= 6; i++) {
                        document.getElementById('otp' + i).value = '';
                    }
                    document.getElementById('otp1').focus();
                } else {
                    showAlert(data.message || 'Error sending OTP. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error sending OTP. Please try again.', 'danger');
            });
        }

        // Resend OTP
        function resendOTP() {
            showAlert('Resending verification code...', 'info');
            
            fetch('api_send_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: bookingData.name,
                    mobile: bookingData.mobile,
                    dob: bookingData.dob,
                    address: bookingData.address,
                    resend: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Verification code resent!', 'success');
                    // Clear and focus first input
                    for (let i = 1; i <= 6; i++) {
                        document.getElementById('otp' + i).value = '';
                    }
                    document.getElementById('otp1').focus();
                } else {
                    showAlert(data.message || 'Error resending OTP', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error resending OTP', 'danger');
            });
        }

        // OTP Input Navigation
        function moveToNext(current, nextId) {
            if (current.value.length === 1) {
                if (nextId) {
                    document.getElementById(nextId).focus();
                } else {
                    // Last digit, auto-verify
                    verifyOTP();
                }
            }
        }

        // Handle OTP Paste
        function handlePaste(e) {
            e.preventDefault();
            const paste = e.clipboardData.getData('text');
            if (paste.match(/^\d{6}$/)) {
                for (let i = 0; i < 6; i++) {
                    document.getElementById('otp' + (i + 1)).value = paste[i];
                }
                document.getElementById('otp6').focus();
            }
        }

        // Verify OTP
        function verifyOTP() {
            const otp = document.getElementById('otp1').value +
                        document.getElementById('otp2').value +
                        document.getElementById('otp3').value +
                        document.getElementById('otp4').value +
                        document.getElementById('otp5').value +
                        document.getElementById('otp6').value;
            
            if (otp.length !== 6) {
                showAlert('Please enter the complete 6-digit code', 'danger');
                return;
            }
            
            document.getElementById('verifyLoading').classList.add('active');
            
            fetch('api_verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mobile: bookingData.mobile,
                    otp: otp
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('verifyLoading').classList.remove('active');
                
                if (data.success) {
                    showAlert('Mobile verified successfully!', 'success');
                    setTimeout(() => {
                        goToStep(6);
                        updateSummary();
                    }, 1000);
                } else {
                    showAlert(data.message || 'Invalid verification code', 'danger');
                    // Clear OTP inputs
                    for (let i = 1; i <= 6; i++) {
                        document.getElementById('otp' + i).value = '';
                    }
                    document.getElementById('otp1').focus();
                }
            })
            .catch(error => {
                document.getElementById('verifyLoading').classList.remove('active');
                console.error('Error:', error);
                showAlert('Error verifying code. Please try again.', 'danger');
            });
        }

        // Update Summary
        function updateSummary() {
            document.getElementById('summaryName').textContent = bookingData.name;
            document.getElementById('summaryMobile').textContent = bookingData.mobile;
            document.getElementById('summaryService').textContent = bookingData.serviceName;
            document.getElementById('summaryDate').textContent = formatDate(bookingData.date);
            document.getElementById('summaryTime').textContent = formatTime(bookingData.time);
            document.getElementById('summaryPrice').textContent = 'Rs. ' + bookingData.servicePrice.toFixed(2);
        }

        // Confirm Booking
        function confirmBooking() {
            document.getElementById('bookingLoading').classList.add('active');
            
            fetch('api_confirm_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    customer_id: bookingData.customerId,
                    service_id: bookingData.serviceId,
                    appointment_date: bookingData.date,
                    appointment_time: bookingData.time
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('bookingLoading').classList.remove('active');
                
                if (data.success) {
                    // Show success page
                    document.getElementById('bookingId').textContent = '#' + data.appointment_id;
                    document.getElementById('successName').textContent = bookingData.name;
                    document.getElementById('successService').textContent = bookingData.serviceName;
                    document.getElementById('successDateTime').textContent = formatDate(bookingData.date) + ' at ' + formatTime(bookingData.time);
                    document.getElementById('successAmount').textContent = 'Rs. ' + bookingData.servicePrice.toFixed(2);
                    
                    // Hide all sections
                    document.querySelectorAll('.form-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // Show success section
                    document.getElementById('stepSuccess').classList.add('active');
                    
                    // Mark all steps as completed
                    document.querySelectorAll('.step').forEach(s => {
                        s.classList.remove('active');
                        s.classList.add('completed');
                    });
                } else {
                    showAlert(data.message || 'Error confirming booking. Please try again.', 'danger');
                }
            })
            .catch(error => {
                document.getElementById('bookingLoading').classList.remove('active');
                console.error('Error:', error);
                showAlert('Error confirming booking. Please try again.', 'danger');
            });
        }

        // Alert System
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            
            setTimeout(() => {
                alertBox.innerHTML = '';
            }, 5000);
        }

        // Format Date
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        // Format Time
        function formatTime(timeStr) {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }
    </script>
</body>
</html>