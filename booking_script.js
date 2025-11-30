// Booking State
let bookingData = {
    name: '',
    mobile: '',
    dob: '',
    address: '',
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

// Step Navigation
function goToStep(step) {
    // Validate current step before moving forward
    if (step > currentStep) {
        if (!validateStep(currentStep)) {
            return;
        }
    }
    
    // Hide all sections
    document.querySelectorAll('.form-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show target section
    document.getElementById('step' + step).classList.add('active');
    
    // Update step indicator
    document.querySelectorAll('.step').forEach(s => {
        const stepNum = parseInt(s.dataset.step);
        s.classList.remove('active', 'completed');
        if (stepNum === step) {
            s.classList.add('active');
        } else if (stepNum < step) {
            s.classList.add('completed');
        }
    });
    
    currentStep = step;
    
    // Special actions for certain steps
    if (step === 3) {
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
    if (!validateStep(3)) {
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
            goToStep(4);
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
                goToStep(5);
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