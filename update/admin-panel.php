<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 20px;
        }
        .menu-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.3s;
            display: block;
            color: white;
            text-decoration: none;
        }
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
            color: #555;
        }
        input, select, textarea {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        textarea { min-height: 80px; resize: vertical; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 13px;
        }
        td { font-size: 13px; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
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
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .close-btn {
            cursor: pointer;
            font-size: 24px;
            color: #999;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-card h4 { font-size: 32px; margin-bottom: 5px; }
        .stat-card p { opacity: 0.9; font-size: 14px; }
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .thermal-print {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 56mm;
            padding: 10mm;
            background: white;
            border: 1px dashed #ccc;
        }
        .thermal-print hr {
            border: none;
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        @media print {
            body * { visibility: hidden; }
            .thermal-print, .thermal-print * { visibility: visible; }
            .thermal-print {
                position: absolute;
                left: 0;
                top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <h2>🔐 Admin Panel</h2>
            <a href="#dashboard" class="menu-item active" onclick="showSection('dashboard')">📊 Dashboard</a>
            <a href="#customers" class="menu-item" onclick="showSection('customers')">👥 Customers</a>
            <a href="#appointments" class="menu-item" onclick="showSection('appointments')">📅 Appointments</a>
            <a href="#services" class="menu-item" onclick="showSection('services')">🔧 Services</a>
            <a href="#products" class="menu-item" onclick="showSection('products')">📦 Products</a>
            <a href="#treatments" class="menu-item" onclick="showSection('treatments')">💊 Treatments</a>
            <a href="#billing" class="menu-item" onclick="showSection('billing')">💳 Billing</a>
            <a href="#dates" class="menu-item" onclick="showSection('dates')">🚫 Block Dates</a>
            <a href="#logout" class="menu-item" onclick="logout()">🚪 Logout</a>
        </div>
        
        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Welcome, Admin</h1>
                    <p style="color: #666; margin-top: 5px;">Manage your clinic operations</p>
                </div>
                <span id="currentDate"></span>
            </div>
            
            <div id="message"></div>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4 id="totalCustomers">0</h4>
                        <p>Total Customers</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h4 id="totalAppointments">0</h4>
                        <p>Total Appointments</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h4 id="todayAppointments">0</h4>
                        <p>Today's Appointments</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h4 id="pendingBills">0</h4>
                        <p>Pending Bills</p>
                    </div>
                </div>
            </div>
            
            <!-- Customers Section -->
            <div id="customers-section" class="section" style="display:none;">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>👥 Customer Management</h3>
                        <button class="btn btn-primary" onclick="showModal('addCustomerModal')">+ Add Customer</button>
                    </div>
                    <div style="overflow-x: auto;">
                        <table id="customersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>City</th>
                                    <th>Free Visits</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Appointments Section -->
            <div id="appointments-section" class="section" style="display:none;">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>📅 Appointment Management</h3>
                        <button class="btn btn-primary" onclick="showModal('addAppointmentModal')">+ Add Appointment</button>
                    </div>
                    <div style="overflow-x: auto;">
                        <table id="appointmentsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Mobile</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Services Section -->
            <div id="services-section" class="section" style="display:none;">
                <div class="card">
                    <h3>🔧 Service Categories</h3>
                    <form id="addCategoryForm" class="form-grid">
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </div>
                    </form>
                    <table id="categoriesTable">
                        <thead>
                            <tr><th>ID</th><th>Name</th><th>Actions</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <div class="card">
                    <h3>🛠️ Services</h3>
                    <form id="addServiceForm" class="form-grid">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" id="serviceCategorySelect" required></select>
                        </div>
                        <div class="form-group">
                            <label>Service Name</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Price (Rs.)</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">Add Service</button>
                        </div>
                    </form>
                    <table id="servicesTable">
                        <thead>
                            <tr><th>ID</th><th>Category</th><th>Service</th><th>Price</th><th>Actions</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Products Section -->
            <div id="products-section" class="section" style="display:none;">
                <div class="card">
                    <h3>📦 Product Management</h3>
                    <form id="addProductForm" class="form-grid">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Price (Rs.)</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock" required>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">Add Product</button>
                        </div>
                    </form>
                    <table id="productsTable">
                        <thead>
                            <tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Treatments Section -->
            <div id="treatments-section" class="section" style="display:none;">
                <div class="card">
                    <h3>💊 Treatment Records</h3>
                    <button class="btn btn-primary" style="margin-bottom: 15px;" onclick="showModal('addTreatmentModal')">+ Add Treatment</button>
                    <table id="treatmentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Appointment Date</th>
                                <th>Treatment Details</th>
                                <th>Ready for Billing</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Billing Section -->
            <div id="billing-section" class="section" style="display:none;">
                <div class="card">
                    <h3>💳 Billing & Invoicing</h3>
                    <button class="btn btn-primary" style="margin-bottom: 15px;" onclick="showModal('createBillModal')">+ Create Bill</button>
                    <table id="billsTable">
                        <thead>
                            <tr>
                                <th>Bill #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Block Dates Section -->
            <div id="dates-section" class="section" style="display:none;">
                <div class="card">
                    <h3>🚫 Block Appointment Dates</h3>
                    <form id="blockDateForm" class="form-grid">
                        <div class="form-group">
                            <label>Date to Block</label>
                            <input type="date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <input type="text" name="reason" placeholder="Holiday, Unavailable, etc.">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-danger">Block Date</button>
                        </div>
                    </form>
                    <table id="blockedDatesTable">
                        <thead>
                            <tr><th>Date</th><th>Reason</th><th>Actions</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <div id="addCustomerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Customer</h3>
                <span class="close-btn" onclick="closeModal('addCustomerModal')">&times;</span>
            </div>
            <form id="addCustomerFormModal">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="tel" name="mobile" pattern="[0-9]{10}" required>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" required>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
                <div class="form-group">
                    <label>Free Visits</label>
                    <input type="number" name="free_visits" value="0">
                </div>
                <button type="submit" class="btn btn-primary">Add Customer</button>
            </form>
        </div>
    </div>
    
    <div id="addAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Appointment</h3>
                <span class="close-btn" onclick="closeModal('addAppointmentModal')">&times;</span>
            </div>
            <form id="addAppointmentFormModal">
                <div class="form-group">
                    <label>Customer Mobile</label>
                    <input type="tel" name="customer_mobile" pattern="[0-9]{10}" required placeholder="Search by mobile">
                </div>
                <div class="form-group">
                    <label>Service Category</label>
                    <select name="service_category" id="aptCategorySelect" required>
                        <option value="">Select category</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service</label>
                    <select name="service_id" id="aptServiceSelect" required>
                        <option value="">Select service</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Appointment Date</label>
                    <input type="date" name="appointment_date" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Appointment</button>
            </form>
        </div>
    </div>
    
    <div id="addTreatmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Treatment Record</h3>
                <span class="close-btn" onclick="closeModal('addTreatmentModal')">&times;</span>
            </div>
            <form id="addTreatmentFormModal">
                <div class="form-group">
                    <label>Select Appointment</label>
                    <select name="appointment_id" id="treatmentAppointmentSelect" required>
                        <option value="">Select appointment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Treatment Details</label>
                    <textarea name="treatment_details" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Doctor Notes</label>
                    <textarea name="doctor_notes" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ready_for_billing" value="1"> Ready for Billing
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Add Treatment</button>
            </form>
        </div>
    </div>
    
    <div id="createBillModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Create Bill/Invoice</h3>
                <span class="close-btn" onclick="closeModal('createBillModal')">&times;</span>
            </div>
            <form id="createBillFormModal">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Customer Mobile</label>
                        <input type="tel" name="customer_mobile" id="billCustomerMobile" pattern="[0-9]{10}" required>
                        <small id="billCustomerName" style="color: #666;"></small>
                    </div>
                    <div class="form-group">
                        <label>Bill Type</label>
                        <select name="bill_type" required>
                            <option value="invoice">Invoice (A4)</option>
                            <option value="thermal">Thermal Print (56mm)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Link to Appointment (Optional)</label>
                    <select name="appointment_id" id="billAppointmentSelect">
                        <option value="">No appointment link</option>
                    </select>
                </div>
                
                <h4 style="margin: 20px 0 10px; color: #333;">Add Items</h4>
                <div id="billItems">
                    <div class="bill-item" style="display: grid; grid-template-columns: 150px 2fr 1fr 1fr 50px; gap: 10px; margin-bottom: 10px; align-items: end;">
                        <div class="form-group" style="margin: 0;">
                            <label>Type</label>
                            <select class="item-type" required>
                                <option value="service">Service</option>
                                <option value="product">Product</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Item</label>
                            <select class="item-select" required>
                                <option value="">Select item</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Quantity</label>
                            <input type="number" class="item-qty" value="1" min="1" required>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Price</label>
                            <input type="number" class="item-price" step="0.01" required readonly>
                        </div>
                        <button type="button" class="btn btn-danger btn-small" onclick="removeItem(this)" style="margin-top: 20px;">×</button>
                    </div>
                </div>
                <button type="button" class="btn btn-success" onclick="addBillItem()">+ Add Item</button>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 20px 0;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Total Amount</label>
                            <input type="number" name="total_amount" id="billTotal" step="0.01" readonly required>
                        </div>
                        <div class="form-group">
                            <label>Advance Paid</label>
                            <input type="number" name="advance_paid" id="billAdvance" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Balance</label>
                            <input type="number" id="billBalance" step="0.01" readonly>
                        </div>
                        <div class="form-group">
                            <label>Free Visits to Use</label>
                            <input type="number" name="free_visits_used" value="0" min="0">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Bill</button>
            </form>
        </div>
    </div>
    
    <div id="updateFreeVisitsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Free Visits</h3>
                <span class="close-btn" onclick="closeModal('updateFreeVisitsModal')">&times;</span>
            </div>
            <form id="updateFreeVisitsForm">
                <input type="hidden" name="customer_id" id="freeVisitsCustomerId">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" id="freeVisitsCustomerName" readonly>
                </div>
                <div class="form-group">
                    <label>Free Visits</label>
                    <input type="number" name="free_visits" id="freeVisitsInput" required>
                </div>
                <button type="submit" class="btn btn-success">Update</button>
            </form>
        </div>
    </div>
    
    <div id="editCustomerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Customer</h3>
                <span class="close-btn" onclick="closeModal('editCustomerModal')">&times;</span>
            </div>
            <form id="editCustomerFormModal">
                <input type="hidden" name="customer_id" id="editCustomerId">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="editCustomerName" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="tel" name="mobile" id="editCustomerMobile" pattern="[0-9]{10}" required>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" id="editCustomerDob" required>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" id="editCustomerCity" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Customer</button>
            </form>
        </div>
    </div>
    
    <div id="viewBillModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Bill Details</h3>
                <span class="close-btn" onclick="closeModal('viewBillModal')">&times;</span>
            </div>
            <div id="billDetailsContent"></div>
            <div style="margin-top: 20px; text-align: center;">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
                <button class="btn btn-success" onclick="closeModal('viewBillModal')">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize
        document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', { 
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
        });
        
        // Section navigation
        function showSection(section) {
            document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
            document.getElementById(section + '-section').style.display = 'block';
            event.target.classList.add('active');
            
            // Load data for section
            switch(section) {
                case 'dashboard':
                    loadDashboard();
                    break;
                case 'customers':
                    loadCustomers();
                    break;
                case 'appointments':
                    loadAppointments();
                    break;
                case 'services':
                    loadServices();
                    break;
                case 'products':
                    loadProducts();
                    break;
                case 'treatments':
                    loadTreatments();
                    break;
                case 'billing':
                    loadBills();
                    break;
                case 'dates':
                    loadBlockedDates();
                    break;
            }
        }
        
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Message display
        function showMessage(msg, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${msg}</div>`;
            setTimeout(() => messageDiv.innerHTML = '', 5000);
        }
        
        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'api.php?action=logout';
            }
        }
        
        // Load dashboard stats
        function loadDashboard() {
            fetch('api.php?action=get_dashboard_stats')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalCustomers').textContent = data.stats.total_customers || 0;
                        document.getElementById('totalAppointments').textContent = data.stats.total_appointments || 0;
                        document.getElementById('todayAppointments').textContent = data.stats.today_appointments || 0;
                        document.getElementById('pendingBills').textContent = data.stats.pending_bills || 0;
                    }
                });
        }
        
        // Load appointments
        function loadAppointments() {
            fetch('api.php?action=get_appointments')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#appointmentsTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.appointments.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#666;">No appointments found</td></tr>';
                            return;
                        }
                        
                        data.appointments.forEach(apt => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${apt.id}</td>
                                    <td>${apt.customer_name}</td>
                                    <td>${apt.mobile}</td>
                                    <td>${apt.service_name}</td>
                                    <td>${new Date(apt.appointment_date).toLocaleDateString()}</td>
                                    <td><span class="badge badge-success">${apt.status}</span></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-small btn-primary" onclick="editAppointment(${apt.id})">Edit</button>
                                        <button class="btn btn-small btn-danger" onclick="deleteAppointment(${apt.id})">Delete</button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(err => {
                    console.error('Error loading appointments:', err);
                    showMessage('Error loading appointments', 'error');
                });
        }
        
        function editAppointment(id) {
            alert('Edit appointment - Feature coming soon!');
        }
        
        function deleteAppointment(id) {
            if (!confirm('Are you sure you want to delete this appointment?')) return;
            alert('Delete appointment - Feature coming soon!');
        }
        
        // Load services
        function loadServices() {
            // Load categories
            fetch('api.php?action=get_categories')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#categoriesTable tbody');
                        tbody.innerHTML = '';
                        
                        // Populate category select dropdown
                        const categorySelect = document.getElementById('serviceCategorySelect');
                        categorySelect.innerHTML = '<option value="">Select Category</option>';
                        
                        if (data.categories.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#666;">No categories found</td></tr>';
                        } else {
                            data.categories.forEach(cat => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${cat.id}</td>
                                        <td>${cat.name}</td>
                                        <td class="action-buttons">
                                            <button class="btn btn-small btn-danger" onclick="deleteCategory(${cat.id})">Delete</button>
                                        </td>
                                    </tr>
                                `;
                                
                                categorySelect.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading categories:', err);
                    showMessage('Error loading categories', 'error');
                });
            
            // Load services
            fetch('api.php?action=get_services')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#servicesTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.services.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#666;">No services found. Add a service above.</td></tr>';
                        } else {
                            data.services.forEach(service => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${service.id}</td>
                                        <td>${service.category_name}</td>
                                        <td>${service.name}</td>
                                        <td>Rs. ${parseFloat(service.price).toFixed(2)}</td>
                                        <td class="action-buttons">
                                            <button class="btn btn-small btn-danger" onclick="deleteService(${service.id})">Delete</button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading services:', err);
                    showMessage('Error loading services', 'error');
                });
        }
        
        // Load products
        function loadProducts() {
            fetch('api.php?action=get_products')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#productsTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.products.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#666;">No products found</td></tr>';
                        } else {
                            data.products.forEach(product => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${product.id}</td>
                                        <td>${product.name}</td>
                                        <td>Rs. ${parseFloat(product.price).toFixed(2)}</td>
                                        <td>${product.stock}</td>
                                        <td class="action-buttons">
                                            <button class="btn btn-small btn-danger" onclick="deleteProduct(${product.id})">Delete</button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading products:', err);
                    showMessage('Error loading products', 'error');
                });
        }
        
        // Load treatments
        function loadTreatments() {
            fetch('api.php?action=get_treatments')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#treatmentsTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.treatments.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#666;">No treatment records found</td></tr>';
                        } else {
                            data.treatments.forEach(treatment => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${treatment.id}</td>
                                        <td>${treatment.customer_name}</td>
                                        <td>${new Date(treatment.appointment_date).toLocaleDateString()}</td>
                                        <td>${treatment.treatment_details ? treatment.treatment_details.substring(0, 50) + '...' : 'N/A'}</td>
                                        <td><span class="badge ${treatment.ready_for_billing ? 'badge-success' : 'badge-warning'}">${treatment.ready_for_billing ? 'Yes' : 'No'}</span></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-small btn-primary" onclick="viewTreatment(${treatment.id})">View</button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading treatments:', err);
                    showMessage('Error loading treatments', 'error');
                });
        }
        
        function viewTreatment(id) {
            alert('View treatment details - Feature coming soon!');
        }
        
        // Load bills
        function loadBills() {
            fetch('api.php?action=get_bills')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#billsTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.bills.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#666;">No bills found</td></tr>';
                        } else {
                            data.bills.forEach(bill => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${bill.bill_number}</td>
                                        <td>${bill.customer_name}</td>
                                        <td>${new Date(bill.bill_date).toLocaleDateString()}</td>
                                        <td>Rs. ${parseFloat(bill.total_amount).toFixed(2)}</td>
                                        <td>Rs. ${parseFloat(bill.advance_paid).toFixed(2)}</td>
                                        <td>Rs. ${parseFloat(bill.balance).toFixed(2)}</td>
                                        <td class="action-buttons">
                                            <button class="btn btn-small btn-primary" onclick="printBill(${bill.id})">Print</button>
                                            <button class="btn btn-small btn-warning" onclick="editBill(${bill.id})">Edit</button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading bills:', err);
                    showMessage('Error loading bills', 'error');
                });
        }
        
        function printBill(id) {
            alert('Print bill - Feature coming soon!');
        }
        
        function editBill(id) {
            alert('Edit bill - Feature coming soon!');
        }
        
        // Load blocked dates
        function loadBlockedDates() {
            fetch('api.php?action=get_blocked_dates_admin')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#blockedDatesTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.dates.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#666;">No blocked dates</td></tr>';
                        } else {
                            data.dates.forEach(date => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${new Date(date.blocked_date).toLocaleDateString()}</td>
                                        <td>${date.reason || 'N/A'}</td>
                                        <td class="action-buttons">
                                            <button class="btn btn-small btn-danger" onclick="deleteBlockedDate(${date.id})">Unblock</button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading blocked dates:', err);
                    showMessage('Error loading blocked dates', 'error');
                });
        }
        
        // Delete functions
        function deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('api.php?action=delete_category', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) loadServices();
            });
        }
        
        function deleteService(id) {
            if (!confirm('Are you sure you want to delete this service?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('api.php?action=delete_service', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) loadServices();
            });
        }
        
        function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('api.php?action=delete_product', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) loadProducts();
            });
        }
        
        function deleteBlockedDate(id) {
            if (!confirm('Are you sure you want to unblock this date?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('api.php?action=delete_blocked_date', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) loadBlockedDates();
            });
        }
        
        // Load customers
        function loadCustomers() {
            fetch('api.php?action=get_customers')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#customersTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.customers.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#666;">No customers found</td></tr>';
                            return;
                        }
                        
                        data.customers.forEach(customer => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${customer.id}</td>
                                    <td>${customer.name}</td>
                                    <td>${customer.mobile}</td>
                                    <td>${customer.city}</td>
                                    <td><span class="badge badge-success">${customer.free_visits}</span></td>
                                    <td>${new Date(customer.created_at).toLocaleDateString()}</td>
                                    <td class="action-buttons">
                                        <button class="btn btn-small btn-warning" onclick="updateFreeVisits(${customer.id}, '${customer.name.replace(/'/g, "\\'")}', ${customer.free_visits})">Free Visits</button>
                                        <button class="btn btn-small btn-primary" onclick="viewCustomer(${customer.id})">View</button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(err => {
                    console.error('Error loading customers:', err);
                    showMessage('Error loading customers', 'error');
                });
        }
        
        function viewCustomer(id) {
            alert('View customer details - Feature coming soon!');
        }
        
        function updateFreeVisits(customerId, customerName, currentVisits) {
            document.getElementById('freeVisitsCustomerId').value = customerId;
            document.getElementById('freeVisitsCustomerName').value = customerName;
            document.getElementById('freeVisitsInput').value = currentVisits;
            showModal('updateFreeVisitsModal');
        }
        
        // Form submissions
        document.getElementById('updateFreeVisitsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=update_free_visits', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('updateFreeVisitsModal');
                    loadCustomers();
                }
            });
        });
        
        document.getElementById('addCustomerFormModal').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=add_customer', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('addCustomerModal');
                    this.reset();
                    loadCustomers();
                }
            });
        });
        
        document.getElementById('addAppointmentFormModal').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=add_appointment_admin', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('addAppointmentModal');
                    this.reset();
                    loadAppointments();
                }
            });
        });
        
        document.getElementById('addTreatmentFormModal').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=add_treatment', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('addTreatmentModal');
                    this.reset();
                    loadTreatments();
                }
            });
        });
        
        document.getElementById('createBillFormModal').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect bill items
            const items = [];
            document.querySelectorAll('.bill-item').forEach(item => {
                const itemSelect = item.querySelector('.item-select');
                const selectedOption = itemSelect.options[itemSelect.selectedIndex];
                
                items.push({
                    type: item.querySelector('.item-type').value,
                    id: itemSelect.value,
                    name: selectedOption.text,
                    quantity: item.querySelector('.item-qty').value,
                    price: item.querySelector('.item-price').value
                });
            });
            
            const formData = new FormData(this);
            formData.append('items', JSON.stringify(items));
            
            fetch('api.php?action=create_bill', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('createBillModal');
                    this.reset();
                    document.getElementById('billItems').innerHTML = '';
                    addBillItem(); // Add one default item
                    loadBills();
                }
            });
        });
        
        document.getElementById('editCustomerFormModal').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=update_customer', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('editCustomerModal');
                    loadCustomers();
                }
            });
        });
        
        // Billing functions
        let allServices = [];
        let allProducts = [];
        
        function loadBillingData() {
            // Load services
            fetch('api.php?action=get_services')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allServices = data.services;
                    }
                });
            
            // Load products
            fetch('api.php?action=get_products')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allProducts = data.products;
                    }
                });
            
            // Load categories for appointment modal
            fetch('api.php?action=get_categories')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('aptCategorySelect');
                        select.innerHTML = '<option value="">Select category</option>';
                        data.categories.forEach(cat => {
                            select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                        });
                    }
                });
        }
        
        // Category change for appointment modal
        document.getElementById('aptCategorySelect').addEventListener('change', function() {
            const categoryId = this.value;
            const serviceSelect = document.getElementById('aptServiceSelect');
            
            fetch('api.php?action=get_services')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        serviceSelect.innerHTML = '<option value="">Select service</option>';
                        const filtered = data.services.filter(s => s.category_id == categoryId);
                        filtered.forEach(service => {
                            serviceSelect.innerHTML += `<option value="${service.id}">${service.name}</option>`;
                        });
                    }
                });
        });
        
        function addBillItem() {
            const container = document.getElementById('billItems');
            const itemHtml = `
                <div class="bill-item" style="display: grid; grid-template-columns: 150px 2fr 1fr 1fr 50px; gap: 10px; margin-bottom: 10px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <select class="item-type" onchange="updateItemDropdown(this)" required>
                            <option value="service">Service</option>
                            <option value="product">Product</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <select class="item-select" onchange="updateItemPrice(this)" required>
                            <option value="">Select item</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <input type="number" class="item-qty" value="1" min="1" onchange="calculateBillTotal()" required>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <input type="number" class="item-price" step="0.01" readonly required>
                    </div>
                    <button type="button" class="btn btn-danger btn-small" onclick="removeItem(this)">×</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', itemHtml);
            
            // Initialize the dropdown
            const newItem = container.lastElementChild;
            updateItemDropdown(newItem.querySelector('.item-type'));
        }
        
        function updateItemDropdown(select) {
            const billItem = select.closest('.bill-item');
            const itemSelect = billItem.querySelector('.item-select');
            const type = select.value;
            
            itemSelect.innerHTML = '<option value="">Select item</option>';
            
            if (type === 'service') {
                allServices.forEach(service => {
                    itemSelect.innerHTML += `<option value="${service.id}" data-price="${service.price}">${service.name}</option>`;
                });
            } else {
                allProducts.forEach(product => {
                    itemSelect.innerHTML += `<option value="${product.id}" data-price="${product.price}">${product.name}</option>`;
                });
            }
        }
        
        function updateItemPrice(select) {
            const billItem = select.closest('.bill-item');
            const priceInput = billItem.querySelector('.item-price');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price') || 0;
            priceInput.value = price;
            calculateBillTotal();
        }
        
        function removeItem(btn) {
            btn.closest('.bill-item').remove();
            calculateBillTotal();
        }
        
        function calculateBillTotal() {
            let total = 0;
            document.querySelectorAll('.bill-item').forEach(item => {
                const qty = parseFloat(item.querySelector('.item-qty').value) || 0;
                const price = parseFloat(item.querySelector('.item-price').value) || 0;
                total += qty * price;
            });
            
            document.getElementById('billTotal').value = total.toFixed(2);
            updateBalance();
        }
        
        document.getElementById('billAdvance').addEventListener('input', updateBalance);
        
        function updateBalance() {
            const total = parseFloat(document.getElementById('billTotal').value) || 0;
            const advance = parseFloat(document.getElementById('billAdvance').value) || 0;
            const balance = total - advance;
            document.getElementById('billBalance').value = balance.toFixed(2);
        }
        
        // Customer lookup for billing
        document.getElementById('billCustomerMobile').addEventListener('blur', function() {
            const mobile = this.value;
            if (mobile.length === 10) {
                fetch(`api.php?action=get_customer_by_mobile&mobile=${mobile}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('billCustomerName').textContent = data.customer.name;
                            
                            // Load customer appointments
                            fetch(`api.php?action=get_customer_appointments&customer_id=${data.customer.id}`)
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        const select = document.getElementById('billAppointmentSelect');
                                        select.innerHTML = '<option value="">No appointment link</option>';
                                        data.appointments.forEach(apt => {
                                            select.innerHTML += `<option value="${apt.id}">${new Date(apt.appointment_date).toLocaleDateString()} - ${apt.service_name}</option>`;
                                        });
                                    }
                                });
                        } else {
                            document.getElementById('billCustomerName').textContent = 'Customer not found';
                        }
                    });
            }
        });
        
        // Load appointments for treatment modal
        function loadTreatmentAppointments() {
            fetch('api.php?action=get_appointments')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('treatmentAppointmentSelect');
                        select.innerHTML = '<option value="">Select appointment</option>';
                        data.appointments.forEach(apt => {
                            select.innerHTML += `<option value="${apt.id}" data-customer="${apt.customer_id}">${apt.customer_name} - ${new Date(apt.appointment_date).toLocaleDateString()}</option>`;
                        });
                    }
                });
        }
        
        // Initialize on modal open
        document.querySelectorAll('[onclick*="showModal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const modalId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
                if (modalId === 'createBillModal') {
                    loadBillingData();
                    if (document.getElementById('billItems').children.length === 0) {
                        addBillItem();
                    }
                } else if (modalId === 'addTreatmentModal') {
                    loadTreatmentAppointments();
                }
            });
        });
        
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=add_service_category', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.reset();
                    loadServices();
                }
            });
        });
        
        document.getElementById('addServiceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=add_service', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.reset();
                    loadServices();
                }
            });
        });
        
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=add_product', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.reset();
                    loadProducts();
                }
            });
        });
        
        document.getElementById('blockDateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php?action=block_date', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.reset();
                    loadBlockedDates();
                }
            });
        });
        
        // Initial load
        loadDashboard();
    </script>
</body>
</html>