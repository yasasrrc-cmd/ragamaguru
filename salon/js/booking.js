let currentStep = 1;
let bookingData = {
    customer: {},
    appointment: {},
    service: {}
};
let selectedTimeSlot = null;
let selectedService = null;
let appointmentId = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadServices();
    setupDateInput();
});

function setupDateInput() {
    const dateInput = document.getElementById('appointment_date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;
    
    dateInput.addEventListener('change', function() {
        loadTimeSlots(this.value);
    });
}

function nextStep(step) {
    if (step === 2 && !validateCustomerForm()) {
        return;
    }
    
    if (step === 3 && !validateDateTime()) {
        return;
    }
    
    if (step === 4 && !validateService()) {
        return;
    }
    
    if (step === 4) {
        sendOTP();
    }
    
    document.querySelectorAll('.booking-step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
    
    document.getElementById('step' + step).classList.add('active');
    document.getElementById('step' + step + '-indicator').classList.add('active');
    
    currentStep = step;
    window.scrollTo(0, 0);
}

function prevStep(step) {
    document.querySelectorAll('.booking-step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
    
    document.getElementById('step' + step).classList.add('active');
    document.getElementById('step' + step + '-indicator').classList.add('active');
    
    currentStep = step;
    window.scrollTo(0, 0);
}

function validateCustomerForm() {
    const name = document.getElementById('name').value.trim();
    const mobile = document.getElementById('mobile').value.trim();
    
    if (!name) {
        alert('Please enter your name');
        return false;
    }
    
    if (!mobile || !/^[0-9]{10}$/.test(mobile)) {
        alert('Please enter a valid 10-digit mobile number');
        return false;
    }
    
    bookingData.customer = {
        name: name,
        mobile: mobile,
        dob: document.getElementById('dob').value,
        city: document.getElementById('city').value
    };
    
    return true;
}

function validateDateTime() {
    const date = document.getElementById('appointment_date').value;
    
    if (!date) {
        alert('Please select a date');
        return false;
    }
    
    if (!selectedTimeSlot) {
        alert('Please select a time slot');
        return false;
    }
    
    bookingData.appointment = {
        date: date,
        time: selectedTimeSlot
    };
    
    return true;
}

function validateService() {
    if (!selectedService) {
        alert('Please select a service');
        return false;
    }
    
    bookingData.service = selectedService;
    return true;
}

function loadTimeSlots(date) {
    showLoading();
    
    fetch('api/get_time_slots.php?date=' + date)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            const container = document.getElementById('timeSlots');
            container.innerHTML = '';
            
            if (data.success && data.slots.length > 0) {
                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'time-slot';
                    btn.textContent = formatTime(slot);
                    btn.onclick = () => selectTimeSlot(btn, slot);
                    container.appendChild(btn);
                });
            } else {
                container.innerHTML = '<p class="no-slots">No available time slots for this date</p>';
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Failed to load time slots');
        });
}

function selectTimeSlot(button, time) {
    document.querySelectorAll('.time-slot').forEach(btn => btn.classList.remove('selected'));
    button.classList.add('selected');
    selectedTimeSlot = time;
}

function loadServices() {
    fetch('api/get_services.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('servicesList');
            container.innerHTML = '';
            
            if (data.success) {
                data.services.forEach(service => {
                    const card = document.createElement('div');
                    card.className = 'service-card';
                    card.onclick = () => selectService(card, service);
                    
                    card.innerHTML = `
                        <h3>${service.name}</h3>
                        <p class="service-description">${service.description}</p>
                        <div class="service-details">
                            <span class="service-duration">⏱️ ${service.duration} min</span>
                            <span class="service-price">Rs. ${parseFloat(service.price).toFixed(2)}</span>
                        </div>
                    `;
                    
                    container.appendChild(card);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load services');
        });
}

function selectService(card, service) {
    document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    selectedService = service;
}

function sendOTP() {
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'create_appointment');
    formData.append('customer_data', JSON.stringify(bookingData.customer));
    formData.append('appointment_data', JSON.stringify(bookingData.appointment));
    formData.append('service_id', bookingData.service.id);
    
    fetch('api/booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            appointmentId = data.appointment_id;
            document.getElementById('displayMobile').textContent = bookingData.customer.mobile;
            alert('OTP sent to your mobile number');
        } else {
            alert(data.message || 'Failed to send OTP');
            prevStep(3);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Failed to send OTP');
        prevStep(3);
    });
}

function resendOTP() {
    sendOTP();
}

function confirmBooking() {
    const otp = document.getElementById('otp').value.trim();
    
    if (!otp || otp.length !== 6) {
        alert('Please enter a valid 6-digit OTP');
        return;
    }
    
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'verify_otp');
    formData.append('appointment_id', appointmentId);
    formData.append('otp', otp);
    
    fetch('api/booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showSuccessMessage(data.appointment);
        } else {
            alert(data.message || 'Invalid OTP. Please try again.');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Verification failed. Please try again.');
    });
}

function showSuccessMessage(appointment) {
    document.querySelectorAll('.booking-step').forEach(el => el.style.display = 'none');
    document.getElementById('stepSuccess').style.display = 'block';
    
    const details = `
        <div class="detail-row">
            <span class="detail-label">Service:</span>
            <span class="detail-value">${appointment.service_name}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date:</span>
            <span class="detail-value">${formatDate(appointment.appointment_date)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Time:</span>
            <span class="detail-value">${formatTime(appointment.appointment_time)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Price:</span>
            <span class="detail-value">Rs. ${parseFloat(appointment.price).toFixed(2)}</span>
        </div>
    `;
    
    document.getElementById('bookingDetails').innerHTML = details;
}

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function formatDate(date) {
    const d = new Date(date);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function showLoading() {
    document.getElementById('loading').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loading').style.display = 'none';
}