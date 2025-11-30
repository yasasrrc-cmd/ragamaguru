// Global variables
let currentAppointmentId = null;
let currentUserData = null;
let adminData = null;

// Page navigation
function showPage(pageId) {
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    document.getElementById(pageId).classList.add('active');
    
    if (pageId === 'homePage') {
        document.getElementById('mainNav').style.display = 'none';
    } else {
        if (pageId === 'userDashboard' || pageId === 'adminDashboard') {
            document.getElementById('mainNav').style.display = 'block';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadServiceCategories();
    setupEventListeners();
    
    // Set minimum date to today
    const dateInput = document.getElementById('appointmentDate');
    if (dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
});

// Setup event listeners
function setupEventListeners() {
    // Booking form
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', handleBookingSubmit);
    }
    
    // Service category change
    const categorySelect = document.getElementById('serviceCategory');
    if (categorySelect) {
        categorySelect.addEventListener('change', loadServicesByCategory);
    }
    
    // Date selection
    const dateInput = document.getElementById('appointmentDate');
    if (dateInput) {
        dateInput.addEventListener('change', checkDateAvailability);
    }
    
    // User login form
    const userLoginForm = document.getElementById('userLoginForm');
    if (userLoginForm) {
        userLoginForm.addEventListener('submit', handleUserLogin);
    }
    
    // Admin login form
    const adminLoginForm = document.getElementById('adminLoginForm');
    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', handleAdminLogin);
    }
}

// API call helper
async function apiCall(action, data = {}, method = 'POST') {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (method === 'POST' && Object.keys(data).length > 0) {
            options.body = JSON.stringify(data);
        }
        
        const url = method === 'GET' ? 
            `api.php?action=${action}&${new URLSearchParams(data)}` : 
            `api.php?action=${action}`;
        
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

// Load service categories
async function loadServiceCategories() {
    const result = await apiCall('get_service_categories', {}, 'GET');
    const select = document.getElementById('serviceCategory');
    
    if (result.success && select) {
        select.innerHTML = '<option value="">Select Category</option>';
        result.data.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = `${category.name} (${category.type})`;
            select.appendChild(option);
        });
    }
}

// Load services by category
async function loadServicesByCategory(event) {
    const categoryId = event.target.value;
    const serviceSelect = document.getElementById('serviceSelect');
    
    if (!categoryId) {
        serviceSelect.disabled = true;
        serviceSelect.innerHTML = '<option value="">Select category first</option>';
        return;
    }
    
    const result = await apiCall('get_services_by_category', { category_id: categoryId }, 'GET');
    
    if (result.success) {
        serviceSelect.disabled = false;
        serviceSelect.innerHTML = '<option value="">Select Service</option>';
        result.data.forEach(service => {
            const option = document.createElement('option');
            option.value = service.id;
            option.textContent = `${service.name} - Rs. ${service.price}`;
            serviceSelect.appendChild(option);
        });
    }
}

// Check date availability
async function checkDateAvailability(event) {
    const date = event.target.value;
    const result = await apiCall('check_date_availability', { date: date }, 'GET');
    
    if (!result.data.available) {
        alert('This date is not available. Please select another date.');
        event.target.value = '';
    }
}

// Handle booking submission
async function handleBookingSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const result = await apiCall('book_appointment', data);
    
    if (result.success) {
        currentAppointmentId = result.data.appointment_id;
        document.getElementById('appointmentIdHidden').value = result.data.appointment_id;
        
        // Show OTP modal
        const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
        otpModal.show();
    } else {
        alert(result.message);
    }
}

// Verify OTP
async function verifyOTP() {
    const otp = document.getElementById('otpInput').value;
    const appointmentId = document.getElementById('appointmentIdHidden').value;
    
    if (otp.length !== 6) {
        alert('Please enter a valid 6-digit OTP');
        return;
    }
    
    const result = await apiCall('verify_appointment_otp', {
        appointment_id: appointmentId,
        otp: otp
    });
    
    if (result.success) {
        alert('Appointment confirmed successfully!');
        bootstrap.Modal.getInstance(document.getElementById('otpModal')).hide();
        document.getElementById('bookingForm').reset();
        showPage('homePage');
    } else {
        alert(result.message);
    }
}

// User login
async function handleUserLogin(event) {
    event.preventDefault();
    
    const mobile = document.getElementById('loginMobile').value;
    const formData = new FormData();
    formData.append('mobile', mobile);
    
    const response = await fetch('api.php?action=send_login_otp', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        document.getElementById('otpVerificationDiv').style.display = 'block';
        alert('OTP sent to your mobile');
    } else {
        alert(result.message);
    }
}

// Verify login OTP
async function verifyLoginOTP() {
    const mobile = document.getElementById('loginMobile').value;
    const otp = document.getElementById('loginOtpInput').value;
    
    const formData = new FormData();
    formData.append('mobile', mobile);
    formData.append('otp', otp);
    
    const response = await fetch('api.php?action=verify_login_otp', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        currentUserData = result.data;
        document.getElementById('userDisplayName').textContent = `Welcome, ${result.data.name}`;
        showPage('userDashboard');
        loadUserDashboard();
    } else {
        alert(result.message);
    }
}

// Load user dashboard
async function loadUserDashboard() {
    const result = await apiCall('get_user_dashboard', {}, 'GET');
    
    if (result.success) {
        const data = result.data;
        
        // Update stats
        document.getElementById('freeVisitsCount').textContent = data.free_visits?.remaining_free_visits || 0;
        document.getElementById('appointmentsCount').textContent = data.appointments.length;
        document.getElementById('treatmentsCount').textContent = data.treatments.length;
        document.getElementById('billsCount').textContent = data.bills.length;
        
        // Load appointments
        renderUserAppointments(data.appointments);
        renderUserTreatments(data.treatments);
        renderUserBills(data.bills);
    }
}

// Render user appointments
function renderUserAppointments(appointments) {
    const container = document.getElementById('userAppointmentsList');
    
    if (appointments.length === 0) {
        container.innerHTML = '<p class="text-muted">No appointments found</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Date</th><th>Time</th><th>Service</th><th>Status</th></tr></thead><tbody>';
    
    appointments.forEach(apt => {
        html += `<tr>
            <td>${apt.appointment_date}</td>
            <td>${apt.appointment_time}</td>
            <td>${apt.service_name}</td>
            <td><span class="badge bg-${getStatusColor(apt.status)}">${apt.status}</span></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// Render user treatments
function renderUserTreatments(treatments) {
    const container = document.getElementById('userTreatmentsList');
    
    if (treatments.length === 0) {
        container.innerHTML = '<p class="text-muted">No treatments found</p>';
        return;
    }
    
    let html = '';
    treatments.forEach(treatment => {
        html += `<div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Treatment - ${new Date(treatment.created_at).toLocaleDateString()}</h5>
                <p><strong>Diagnosis:</strong> ${treatment.diagnosis || 'N/A'}</p>
                <p><strong>Treatment:</strong> ${treatment.treatment_details || 'N/A'}</p>
                <p><strong>Prescription:</strong> ${treatment.prescription || 'N/A'}</p>
                ${treatment.notes ? `<p><strong>Notes:</strong> ${treatment.notes}</p>` : ''}
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
}

// Render user bills
function renderUserBills(bills) {
    const container = document.getElementById('userBillsList');
    
    if (bills.length === 0) {
        container.innerHTML = '<p class="text-muted">No bills found</p>';
        return;
    }
    
    let html = '';
    bills.forEach(bill => {
        html += `<div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title">Invoice: ${bill.invoice_number}</h5>
                        <p class="mb-1">Date: ${new Date(bill.created_at).toLocaleDateString()}</p>
                        <p class="mb-1">Total: Rs. ${bill.total_amount}</p>
                        <p class="mb-1">Paid: Rs. ${bill.paid_amount}</p>
                        <p class="mb-1">Remaining: Rs. ${bill.remaining_amount}</p>
                        <span class="badge bg-${bill.payment_status === 'paid' ? 'success' : bill.payment_status === 'partial' ? 'warning' : 'danger'}">${bill.payment_status}</span>
                    </div>
                    <div>
                        ${bill.payment_status !== 'paid' ? `<button class="btn btn-sm btn-primary" onclick="showPaymentUpload(${bill.id})">Upload Payment</button>` : ''}
                    </div>
                </div>
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
}

// Show payment upload
function showPaymentUpload(billId) {
    const html = `
        <div class="modal fade" id="paymentUploadModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Payment Slip</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="paymentUploadForm">
                            <input type="hidden" name="bill_id" value="${billId}">
                            <div class="mb-3">
                                <label class="form-label">Amount Paid</label>
                                <input type="number" class="form-control" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" name="payment_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Slip</label>
                                <input type="file" class="form-control" name="payment_slip" accept="image/*" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="uploadPaymentSlip()">Upload</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    const modal = new bootstrap.Modal(document.getElementById('paymentUploadModal'));
    modal.show();
}

// Upload payment slip
async function uploadPaymentSlip() {
    const form = document.getElementById('paymentUploadForm');
    const formData = new FormData(form);
    
    const response = await fetch('api.php?action=upload_payment_slip', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Payment slip uploaded successfully');
        bootstrap.Modal.getInstance(document.getElementById('paymentUploadModal')).hide();
        loadUserDashboard();
    } else {
        alert(result.message);
    }
}

// Admin login
async function handleAdminLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    const response = await fetch('api.php?action=admin_login', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        adminData = result.data;
        document.getElementById('userDisplayName').textContent = `Admin: ${result.data.name}`;
        showPage('adminDashboard');
        showAdminSection('appointments');
    } else {
        alert(result.message);
    }
}

// Show admin section
async function showAdminSection(section) {
    const content = document.getElementById('adminContent');
    
    // Update active menu
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.classList.remove('active');
    });
    event.target.classList.add('active');
    
    switch(section) {
        case 'appointments':
            await loadAdminAppointments();
            break;
        case 'customers':
            await loadAdminCustomers();
            break;
        case 'services':
            await loadAdminServices();
            break;
        case 'products':
            await loadAdminProducts();
            break;
        case 'treatments':
            await loadAdminTreatments();
            break;
        case 'billing':
            await loadAdminBilling();
            break;
        case 'dates':
            await loadBlockedDates();
            break;
    }
}

// Load admin appointments
async function loadAdminAppointments() {
    const result = await apiCall('get_all_appointments', {}, 'GET');
    const content = document.getElementById('adminContent');
    
    if (result.success) {
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Appointments</h3>
                <button class="btn btn-primary" onclick="showAddAppointmentForm()">Add New</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        result.data.forEach(apt => {
            html += `<tr>
                <td>${apt.id}</td>
                <td>${apt.customer_name}</td>
                <td>${apt.mobile}</td>
                <td>${apt.service_name}</td>
                <td>${apt.appointment_date}</td>
                <td>${apt.appointment_time}</td>
                <td>
                    <select class="form-select form-select-sm" onchange="updateAppointmentStatus(${apt.id}, this.value)">
                        <option value="pending" ${apt.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="confirmed" ${apt.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                        <option value="completed" ${apt.status === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="cancelled" ${apt.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="addTreatment(${apt.id}, ${apt.customer_id})">Add Treatment</button>
                </td>
            </tr>`;
        });
        
        html += '</tbody></table></div>';
        content.innerHTML = html;
    }
}

// Update appointment status
async function updateAppointmentStatus(id, status) {
    const result = await apiCall('update_appointment_status', { id, status });
    if (result.success) {
        alert('Status updated successfully');
    }
}

// Add treatment form
function addTreatment(appointmentId, customerId) {
    const html = `
        <div class="modal fade" id="treatmentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Treatment Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="treatmentForm">
                            <input type="hidden" name="appointment_id" value="${appointmentId}">
                            <input type="hidden" name="customer_id" value="${customerId}">
                            <div class="mb-3">
                                <label class="form-label">Diagnosis</label>
                                <textarea class="form-control" name="diagnosis" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Treatment Details</label>
                                <textarea class="form-control" name="treatment_details" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prescription</label>
                                <textarea class="form-control" name="prescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="ready_for_billing" value="1">
                                <label class="form-check-label">Ready for Billing</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveTreatment()">Save Treatment</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    const modal = new bootstrap.Modal(document.getElementById('treatmentModal'));
    modal.show();
}

// Save treatment
async function saveTreatment() {
    const form = document.getElementById('treatmentForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    data.ready_for_billing = formData.get('ready_for_billing') ? true : false;
    
    const result = await apiCall('add_treatment', data);
    
    if (result.success) {
        alert('Treatment added successfully');
        bootstrap.Modal.getInstance(document.getElementById('treatmentModal')).hide();
        loadAdminAppointments();
    } else {
        alert(result.message);
    }
}

// Load admin billing
async function loadAdminBilling() {
    const result = await apiCall('get_all_bills', {}, 'GET');
    const treatmentsResult = await apiCall('get_treatments_for_billing', {}, 'GET');
    const content = document.getElementById('adminContent');
    
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Billing</h3>
            <button class="btn btn-primary" onclick="showCreateBillForm()">Create New Bill</button>
        </div>
    `;
    
    if (treatmentsResult.success && treatmentsResult.data.length > 0) {
        html += `
            <div class="alert alert-info">
                <strong>${treatmentsResult.data.length} treatment(s) ready for billing</strong>
            </div>
        `;
    }
    
    if (result.success) {
        html += `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        result.data.forEach(bill => {
            html += `<tr>
                <td>${bill.invoice_number}</td>
                <td>${bill.customer_name}</td>
                <td>Rs. ${bill.total_amount}</td>
                <td>Rs. ${bill.paid_amount}</td>
                <td>Rs. ${bill.remaining_amount}</td>
                <td><span class="badge bg-${bill.payment_status === 'paid' ? 'success' : bill.payment_status === 'partial' ? 'warning' : 'danger'}">${bill.payment_status}</span></td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewBill(${bill.id})">View</button>
                    <button class="btn btn-sm btn-success" onclick="updatePayment(${bill.id}, ${bill.total_amount})">Update Payment</button>
                    <button class="btn btn-sm btn-primary" onclick="printBill(${bill.id}, 'thermal')">Print (56mm)</button>
                </td>
            </tr>`;
        });
        
        html += '</tbody></table></div>';
    }
    
    content.innerHTML = html;
}

// Helper functions
function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'confirmed': 'info',
        'completed': 'success',
        'cancelled': 'danger',
        'paid': 'success',
        'partial': 'warning',
        'unpaid': 'danger'
    };
    return colors[status] || 'secondary';
}

// Logout
async function logout() {
    await apiCall('logout', {}, 'GET');
    location.reload();
}

// Additional placeholder functions for complete functionality
async function loadAdminCustomers() {
    const result = await apiCall('get_all_customers', {}, 'GET');
    const content = document.getElementById('adminContent');
    
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Customers</h3>
            <button class="btn btn-primary" onclick="showAddCustomerForm()">Add Customer</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Mobile</th><th>City</th><th>Free Visits</th><th>Actions</th></tr>
                </thead>
                <tbody>
    `;
    
    if (result.success) {
        result.data.forEach(customer => {
            html += `<tr>
                <td>${customer.id}</td>
                <td>${customer.name}</td>
                <td>${customer.mobile}</td>
                <td>${customer.city || 'N/A'}</td>
                <td>${customer.remaining_free_visits || 0}</td>
                <td><button class="btn btn-sm btn-info" onclick="viewCustomer(${customer.id})">View</button></td>
            </tr>`;
        });
    }
    
    html += '</tbody></table></div>';
    content.innerHTML = html;
}

async function loadAdminServices() {
    const content = document.getElementById('adminContent');
    content.innerHTML = '<h3>Services Management</h3><p>Services management interface - Add, Edit, Delete services and categories</p>';
}

async function loadAdminProducts() {
    const content = document.getElementById('adminContent');
    content.innerHTML = '<h3>Products Management</h3><p>Products management interface - Add, Edit, Delete products</p>';
}

async function loadAdminTreatments() {
    const content = document.getElementById('adminContent');
    content.innerHTML = '<h3>Treatments</h3><p>View and manage treatment details</p>';
}

async function loadBlockedDates() {
    const content = document.getElementById('adminContent');
    content.innerHTML = '<h3>Blocked Dates</h3><p>Manage blocked dates for appointments</p>';
}

function showAddCustomerForm() { alert('Add customer form'); }
function showAddAppointmentForm() { alert('Add appointment form'); }
function showCreateBillForm() { alert('Create bill form'); }
function viewBill(id) { alert('View bill ' + id); }
function viewCustomer(id) { alert('View customer ' + id); }
function updatePayment(billId, total) { alert('Update payment for bill ' + billId); }
function printBill(billId, type) { alert('Print bill ' + billId + ' as ' + type); }