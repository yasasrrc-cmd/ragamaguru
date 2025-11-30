<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo SITE_NAME; ?> - Book Appointment</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>💅 Salon Booking</h1>
            <p>Book your appointment in minutes</p>
        </header>

        <!-- Step Indicator -->
        <div class="steps">
            <div class="step active" id="step1-indicator">
                <div class="step-number">1</div>
                <div class="step-label">Details</div>
            </div>
            <div class="step" id="step2-indicator">
                <div class="step-number">2</div>
                <div class="step-label">Date & Time</div>
            </div>
            <div class="step" id="step3-indicator">
                <div class="step-number">3</div>
                <div class="step-label">Service</div>
            </div>
            <div class="step" id="step4-indicator">
                <div class="step-number">4</div>
                <div class="step-label">Verify</div>
            </div>
        </div>

        <!-- Step 1: Customer Details -->
        <div class="booking-step active" id="step1">
            <h2>📝 Your Details</h2>
            <form id="customerForm">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="07XXXXXXXX" required pattern="[0-9]{10}">
                </div>
                
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>City</label>
                    <input type="text" id="city" name="city" class="form-control">
                </div>
                
                <button type="button" class="btn btn-primary btn-block" onclick="nextStep(2)">Next →</button>
            </form>
        </div>

        <!-- Step 2: Date & Time Selection -->
        <div class="booking-step" id="step2">
            <h2>📅 Select Date & Time</h2>
            
            <div class="form-group">
                <label>Select Date *</label>
                <input type="date" id="appointment_date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Available Time Slots</label>
                <div id="timeSlots" class="time-slots"></div>
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="prevStep(1)">← Back</button>
                <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next →</button>
            </div>
        </div>

        <!-- Step 3: Service Selection -->
        <div class="booking-step" id="step3">
            <h2>💆 Select Service</h2>
            <div id="servicesList" class="services-grid"></div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="prevStep(2)">← Back</button>
                <button type="button" class="btn btn-primary" onclick="nextStep(4)">Next →</button>
            </div>
        </div>

        <!-- Step 4: OTP Verification -->
        <div class="booking-step" id="step4">
            <h2>🔐 Verify Mobile</h2>
            
            <div class="verification-info">
                <p>We've sent a 6-digit OTP to <strong id="displayMobile"></strong></p>
            </div>
            
            <div class="form-group">
                <label>Enter OTP *</label>
                <input type="text" id="otp" class="form-control otp-input" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            
            <button type="button" class="btn btn-link" onclick="resendOTP()">Resend OTP</button>
            
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="prevStep(3)">← Back</button>
                <button type="button" class="btn btn-success" onclick="confirmBooking()">Confirm Booking</button>
            </div>
        </div>

        <!-- Success Message -->
        <div class="booking-step" id="stepSuccess" style="display: none;">
            <div class="success-message">
                <div class="success-icon">✓</div>
                <h2>Booking Confirmed!</h2>
                <p>Your appointment has been successfully booked.</p>
                <div class="booking-details" id="bookingDetails"></div>
                <button type="button" class="btn btn-primary btn-block" onclick="location.reload()">Book Another Appointment</button>
            </div>
        </div>
    </div>

    <div id="loading" class="loading" style="display: none;">
        <div class="spinner"></div>
    </div>

    <script src="js/booking.js"></script>
</body>
</html>