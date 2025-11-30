<?php
require_once '../config.php';
check_admin_login();

$message = '';
$error = '';

// Handle bill creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bill'])) {
    $customer_id = intval($_POST['customer_id']);
    $services = $_POST['services'] ?? [];
    $employees = $_POST['employees'] ?? [];
    $payment_method = clean_input($_POST['payment_method']);
    
    if (empty($services)) {
        $error = 'Please select at least one service.';
    } else {
        $conn->begin_transaction();
        
        try {
            // Calculate total
            $total = 0;
            $service_prices = [];
            
            foreach ($services as $service_id => $quantity) {
                $sid = intval($service_id);
                $stmt = $conn->prepare("SELECT price FROM services WHERE id = ?");
                $stmt->bind_param("i", $sid);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $service_prices[$sid] = floatval($row['price']);
                    $total += floatval($row['price']) * intval($quantity);
                }
            }
            
            // Generate invoice number
            $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create invoice - using direct query for simplicity
            $invoice_number_safe = $conn->real_escape_string($invoice_number);
            $payment_method_safe = $conn->real_escape_string($payment_method);
            
            $sql = "INSERT INTO invoices (invoice_number, customer_id, total_amount, payment_status, payment_method) 
                    VALUES ('$invoice_number_safe', $customer_id, $total, 'paid', '$payment_method_safe')";
            
            if (!$conn->query($sql)) {
                throw new Exception('Failed to create invoice: ' . $conn->error);
            }
            
            $invoice_id = $conn->insert_id;
            
            // Add invoice items
            foreach ($services as $service_id => $quantity) {
                $sid = intval($service_id);
                $qty = intval($quantity);
                $price = floatval($service_prices[$sid]);
                $subtotal = $price * $qty;
                
                // Check if employee is assigned
                $emp_id = null;
                if (isset($employees[$service_id]) && !empty($employees[$service_id])) {
                    $emp_id = intval($employees[$service_id]);
                }
                
                // Use direct query with proper escaping
                if ($emp_id !== null) {
                    $sql = "INSERT INTO invoice_items (invoice_id, service_id, employee_id, quantity, price, subtotal) 
                            VALUES ($invoice_id, $sid, $emp_id, $qty, $price, $subtotal)";
                } else {
                    $sql = "INSERT INTO invoice_items (invoice_id, service_id, quantity, price, subtotal) 
                            VALUES ($invoice_id, $sid, $qty, $price, $subtotal)";
                }
                
                if (!$conn->query($sql)) {
                    throw new Exception('Failed to add invoice item: ' . $conn->error);
                }
            }
            
            $conn->commit();
            
            // Send SMS
            $stmt = $conn->prepare("SELECT mobile FROM customers WHERE id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $customer = $stmt->get_result()->fetch_assoc();
            
            $sms_message = "Invoice: $invoice_number\nTotal: Rs. " . number_format($total, 2) . "\nThank you for your visit!";
            send_sms($customer['mobile'], $sms_message);
            
            header("Location: print_invoice.php?id=$invoice_id&type=thermal");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Failed to create bill: ' . $e->getMessage();
        }
    }
}

// Get customers for dropdown
$customers = $conn->query("SELECT id, name, mobile FROM customers ORDER BY name ASC");

// Get active services
$services = $conn->query("SELECT id, name, price, duration FROM services WHERE status = 'active' ORDER BY name ASC");

// Get active employees
$employees = $conn->query("SELECT id, name, position FROM employees WHERE status = 'active' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .billing-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
        }
        
        .service-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .service-tile {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .service-tile:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .service-tile.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .service-tile h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .service-tile .price {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .service-tile .duration {
            font-size: 12px;
            color: #999;
        }
        
        .service-tile .quantity-control {
            display: none;
            margin-top: 15px;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .service-tile .employee-select {
            display: none;
            margin-top: 10px;
        }
        
        .service-tile.selected .quantity-control {
            display: flex;
        }
        
        .service-tile.selected .employee-select {
            display: block !important;
        }
        
        .employee-dropdown {
            width: 100%;
            font-size: 13px !important;
            padding: 8px !important;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
        }
        
        .employee-dropdown:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            background: #667eea;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 5px;
        }
        
        .bill-summary {
            background: white;
            border-radius: 12px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }
        
        .bill-items {
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .bill-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .bill-total {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        @media (max-width: 1024px) {
            .billing-grid {
                grid-template-columns: 1fr;
            }
            
            .bill-summary {
                position: relative;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>💳 Create Bill</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="billingForm">
                <div class="billing-grid">
                    <div>
                        <div class="dashboard-section">
                            <h2>Select Customer</h2>
                            <select name="customer_id" id="customerId" class="form-control" required>
                                <option value="">-- Select Customer --</option>
                                <?php while ($customer = $customers->fetch_assoc()): ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo $customer['name']; ?> (<?php echo $customer['mobile']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="dashboard-section" style="margin-top: 20px;">
                            <h2>Select Services (Tap to Add)</h2>
                            <div class="service-tiles">
                                <?php while ($service = $services->fetch_assoc()): ?>
                                    <div class="service-tile" data-id="<?php echo $service['id']; ?>" data-name="<?php echo htmlspecialchars($service['name']); ?>" data-price="<?php echo $service['price']; ?>" onclick="toggleService(this)">
                                        <h3><?php echo $service['name']; ?></h3>
                                        <div class="price">Rs. <?php echo number_format($service['price'], 2); ?></div>
                                        <div class="duration">⏱️ <?php echo $service['duration']; ?> min</div>
                                        <div class="quantity-control">
                                            <button type="button" class="quantity-btn" onclick="event.stopPropagation(); changeQuantity(this, -1)">-</button>
                                            <input type="number" class="quantity-input" value="1" min="1" readonly>
                                            <button type="button" class="quantity-btn" onclick="event.stopPropagation(); changeQuantity(this, 1)">+</button>
                                        </div>
                                        <div class="employee-select">
                                            <label style="display: block; margin-bottom: 5px; font-size: 12px; font-weight: 600; color: #555;">Performed By:</label>
                                            <select class="employee-dropdown" onclick="event.stopPropagation();">
                                                <option value="">Select Employee</option>
                                                <?php 
                                                $employees->data_seek(0);
                                                while ($emp = $employees->fetch_assoc()): 
                                                ?>
                                                    <option value="<?php echo $emp['id']; ?>"><?php echo $emp['name']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bill-summary">
                        <h2>Bill Summary</h2>
                        <div class="bill-items" id="billItems">
                            <p style="text-align: center; color: #999; padding: 40px 0;">No services selected</p>
                        </div>
                        <div class="bill-total">
                            Total: Rs. <span id="totalAmount">0.00</span>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="create_bill" class="btn btn-success btn-block" style="margin-top: 20px;">Create Bill & Print</button>
                    </div>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        let selectedServices = {};
        
        function toggleService(tile) {
            const id = tile.dataset.id;
            const name = tile.dataset.name;
            const price = parseFloat(tile.dataset.price);
            
            if (tile.classList.contains('selected')) {
                tile.classList.remove('selected');
                delete selectedServices[id];
            } else {
                tile.classList.add('selected');
                const quantity = parseInt(tile.querySelector('.quantity-input').value);
                const employeeId = tile.querySelector('.employee-dropdown').value;
                selectedServices[id] = { name, price, quantity, employeeId };
            }
            
            updateBillSummary();
        }
        
        function changeQuantity(btn, change) {
            const tile = btn.closest('.service-tile');
            const input = tile.querySelector('.quantity-input');
            let value = parseInt(input.value) + change;
            
            if (value < 1) value = 1;
            input.value = value;
            
            const id = tile.dataset.id;
            if (selectedServices[id]) {
                selectedServices[id].quantity = value;
                updateBillSummary();
            }
        }
        
        // Update selected services when employee is changed
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('employee-dropdown')) {
                const tile = e.target.closest('.service-tile');
                const id = tile.dataset.id;
                if (selectedServices[id]) {
                    selectedServices[id].employeeId = e.target.value;
                    updateBillSummary();
                }
            }
        });
        
        function updateBillSummary() {
            const billItems = document.getElementById('billItems');
            const totalAmount = document.getElementById('totalAmount');
            
            if (Object.keys(selectedServices).length === 0) {
                billItems.innerHTML = '<p style="text-align: center; color: #999; padding: 40px 0;">No services selected</p>';
                totalAmount.textContent = '0.00';
                return;
            }
            
            let html = '';
            let total = 0;
            
            for (const [id, service] of Object.entries(selectedServices)) {
                const subtotal = service.price * service.quantity;
                total += subtotal;
                
                html += `
                    <div class="bill-item">
                        <div>
                            <strong>${service.name}</strong><br>
                            <small>${service.quantity} × Rs. ${service.price.toFixed(2)}</small>
                            <input type="hidden" name="services[${id}]" value="${service.quantity}">
                            <input type="hidden" name="employees[${id}]" value="${service.employeeId || ''}">
                        </div>
                        <div style="text-align: right;">
                            Rs. ${subtotal.toFixed(2)}
                        </div>
                    </div>
                `;
            }
            
            billItems.innerHTML = html;
            totalAmount.textContent = total.toFixed(2);
        }
        
        document.getElementById('billingForm').addEventListener('submit', function(e) {
            if (Object.keys(selectedServices).length === 0) {
                e.preventDefault();
                alert('Please select at least one service');
                return false;
            }
            
            if (!document.getElementById('customerId').value) {
                e.preventDefault();
                alert('Please select a customer');
                return false;
            }
        });
    </script>
</body>
</html>