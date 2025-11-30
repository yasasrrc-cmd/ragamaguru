<?php
require_once 'config.php';
requireLogin();

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

// Get appointment with treatment details
$appointment = $conn->query("
    SELECT a.*, c.name as customer_name, c.mobile, c.id as customer_id,
    s.service_name, s.price as service_price,
    t.treatment_given, t.products_used, t.medicines_prescribed, t.treatment_date
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    LEFT JOIN treatment_records t ON a.id = t.appointment_id
    WHERE a.id = $appointment_id
")->fetch_assoc();

if (!$appointment) {
    die("Appointment not found");
}

// Get customer's free visits
$free_visits = $conn->query("
    SELECT * FROM free_visits 
    WHERE customer_id = {$appointment['customer_id']} 
    AND service_id = {$appointment['service_id']}
    AND remaining_visits > 0 
    ORDER BY granted_date DESC 
    LIMIT 1
")->fetch_assoc();

$success = '';
$error = '';

if (isset($_POST['create_bill'])) {
    $customer_id = $appointment['customer_id'];
    $subtotal = floatval($_POST['subtotal']);
    $discount_percent = floatval($_POST['discount_percent']);
    $discount_amount = floatval($_POST['discount_amount']);
    $total_amount = floatval($_POST['total_amount']);
    $paid_amount = floatval($_POST['paid_amount']);
    $payment_method = cleanInput($_POST['payment_method']);
    $free_visits_granted = intval($_POST['free_visits_granted']);
    $use_free_visit = isset($_POST['use_free_visit']) ? 1 : 0;
    
    // Determine payment status
    if ($paid_amount >= $total_amount) {
        $payment_status = 'paid';
    } elseif ($paid_amount > 0) {
        $payment_status = 'partial';
    } else {
        $payment_status = 'unpaid';
    }
    
    // Generate bill number
    $bill_number = 'RG-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $bill_date = date('Y-m-d');
    $created_by = $_SESSION['admin_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert bill
        $stmt = $conn->prepare("INSERT INTO bills (bill_number, customer_id, appointment_id, subtotal, discount_amount, discount_percent, total_amount, paid_amount, payment_status, payment_method, bill_date, free_visits_granted, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiddddssssii", $bill_number, $customer_id, $appointment_id, $subtotal, $discount_amount, $discount_percent, $total_amount, $paid_amount, $payment_status, $payment_method, $bill_date, $free_visits_granted, $created_by);
        $stmt->execute();
        $bill_id = $conn->insert_id;
        
        // Insert service as bill item
        $service_id = $appointment['service_id'];
        $service_name = $appointment['service_name'];
        $service_price = $appointment['service_price'];
        $is_free = $use_free_visit ? 1 : 0;
        $item_total = $is_free ? 0 : $service_price;
        
        $stmt = $conn->prepare("INSERT INTO bill_items (bill_id, item_type, item_id, item_name, quantity, unit_price, total, is_free_visit) VALUES (?, 'service', ?, ?, 1, ?, ?, ?)");
        $stmt->bind_param("iisddi", $bill_id, $service_id, $service_name, $service_price, $item_total, $is_free);
        $stmt->execute();
        
        // If using free visit, update free visits
        if ($use_free_visit && $free_visits) {
            $conn->query("UPDATE free_visits SET used_visits = used_visits + 1, remaining_visits = remaining_visits - 1 WHERE id = {$free_visits['id']}");
        }
        
        // If granting free visits, create record
        if ($free_visits_granted > 0) {
            $expiry_date = date('Y-m-d', strtotime('+1 year'));
            $stmt = $conn->prepare("INSERT INTO free_visits (customer_id, service_id, total_free_visits, used_visits, remaining_visits, granted_date, expiry_date, granted_by) VALUES (?, ?, ?, 0, ?, ?, ?, ?)");
            $stmt->bind_param("iiiissi", $customer_id, $service_id, $free_visits_granted, $free_visits_granted, $bill_date, $expiry_date, $created_by);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $success = "Bill created successfully!";
        header("refresh:2;url=bill_view.php?id=$bill_id");
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating bill: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bill - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .treatment-summary {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .free-visit-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Create Bill</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Customer & Appointment Info -->
        <div class="info-box">
            <h3>Customer Information</h3>
            <div class="form-grid">
                <div>
                    <strong>Name:</strong> <?php echo $appointment['customer_name']; ?><br>
                    <strong>Mobile:</strong> <?php echo $appointment['mobile']; ?>
                </div>
                <div>
                    <strong>Service:</strong> <?php echo $appointment['service_name']; ?><br>
                    <strong>Date:</strong> <?php echo formatDate($appointment['appointment_date']); ?>
                </div>
            </div>
        </div>
        
        <!-- Treatment Summary -->
        <?php if ($appointment['treatment_given']): ?>
        <div class="treatment-summary">
            <h3>Treatment Summary</h3>
            <p><strong>Date:</strong> <?php echo formatDate($appointment['treatment_date']); ?></p>
            <p><strong>Treatment:</strong> <?php echo nl2br(htmlspecialchars($appointment['treatment_given'])); ?></p>
            <?php if ($appointment['products_used']): ?>
                <p><strong>Products Used:</strong> <?php echo nl2br(htmlspecialchars($appointment['products_used'])); ?></p>
            <?php endif; ?>
            <?php if ($appointment['medicines_prescribed']): ?>
                <p><strong>Medicines:</strong> <?php echo nl2br(htmlspecialchars($appointment['medicines_prescribed'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Free Visits Status -->
        <?php if ($free_visits): ?>
        <div class="alert alert-info">
            <strong>🎁 Customer has FREE VISITS available!</strong><br>
            Remaining: <strong><?php echo $free_visits['remaining_visits']; ?> out of <?php echo $free_visits['total_free_visits']; ?></strong> free visits<br>
            <small>Granted on: <?php echo formatDate($free_visits['granted_date']); ?></small>
        </div>
        <?php endif; ?>
        
        <!-- Billing Form -->
        <div class="section">
            <h2>Bill Details</h2>
            <form method="POST" id="billForm">
                <!-- Service Charge -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <strong><?php echo $appointment['service_name']; ?></strong>
                            <br><small>Service Charge</small>
                        </div>
                        <div style="text-align: right;">
                            <strong style="font-size: 20px;">Rs. <?php echo number_format($appointment['service_price'], 2); ?></strong>
                        </div>
                    </div>
                    
                    <?php if ($free_visits): ?>
                    <div style="border-top: 1px solid #dee2e6; padding-top: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="use_free_visit" id="useFreeVisit" onchange="calculateTotal()">
                            <span style="margin-left: 10px; font-weight: bold; color: #28a745;">
                                Use 1 Free Visit (<?php echo $free_visits['remaining_visits']; ?> remaining)
                            </span>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Discount -->
                <div class="form-group">
                    <label>Discount</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="discount_percent" id="discountPercent" 
                               placeholder="%" min="0" max="100" step="0.1" 
                               style="width: 120px;" onchange="calculateTotal()">
                        <span style="padding: 10px;">OR</span>
                        <input type="number" name="discount_amount" id="discountAmount" 
                               placeholder="Rs." min="0" step="0.01" 
                               style="width: 150px;" onchange="calculateTotal()">
                    </div>
                </div>
                
                <!-- Summary -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Subtotal:</span>
                        <span id="subtotalDisplay">Rs. <?php echo number_format($appointment['service_price'], 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Discount:</span>
                        <span id="discountDisplay">Rs. 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 2px solid #dee2e6; font-size: 20px; font-weight: bold; color: #667eea;">
                        <span>Total:</span>
                        <span id="totalDisplay">Rs. <?php echo number_format($appointment['service_price'], 2); ?></span>
                    </div>
                </div>
                
                <!-- Payment -->
                <div class="form-grid">
                    <div class="form-group">
                        <label>Amount Paid *</label>
                        <input type="number" name="paid_amount" id="paidAmount" required min="0" step="0.01" value="<?php echo $appointment['service_price']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <select name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_payment">Mobile Payment</option>
                        </select>
                    </div>
                </div>
                
                <!-- Grant Free Visits -->
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                    <h3 style="margin-bottom: 15px;">🎁 Grant Free Visits to Customer</h3>
                    <div class="form-group">
                        <label>Number of Free Visits (0-5)</label>
                        <select name="free_visits_granted">
                            <option value="0">None</option>
                            <option value="1">1 Free Visit</option>
                            <option value="2">2 Free Visits</option>
                            <option value="3">3 Free Visits</option>
                            <option value="4">4 Free Visits</option>
                            <option value="5">5 Free Visits</option>
                        </select>
                        <small>Free visits will be valid for 1 year and can be used for the same service.</small>
                    </div>
                </div>
                
                <!-- Hidden fields -->
                <input type="hidden" name="subtotal" id="subtotalValue" value="<?php echo $appointment['service_price']; ?>">
                <input type="hidden" name="total_amount" id="totalValue" value="<?php echo $appointment['service_price']; ?>">
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="create_bill" class="btn">Create Bill & Print</button>
                    <a href="appointments.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const servicePrice = <?php echo $appointment['service_price']; ?>;
        
        function calculateTotal() {
            const useFreeVisit = document.getElementById('useFreeVisit') ? document.getElementById('useFreeVisit').checked : false;
            const discountPercent = parseFloat(document.getElementById('discountPercent').value) || 0;
            const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
            
            let subtotal = useFreeVisit ? 0 : servicePrice;
            let discount = 0;
            
            if (discountPercent > 0) {
                discount = (subtotal * discountPercent) / 100;
            } else if (discountAmount > 0) {
                discount = discountAmount;
            }
            
            const total = Math.max(0, subtotal - discount);
            
            // Update display
            document.getElementById('subtotalDisplay').textContent = 'Rs. ' + subtotal.toFixed(2);
            document.getElementById('discountDisplay').textContent = 'Rs. ' + discount.toFixed(2);
            document.getElementById('totalDisplay').textContent = 'Rs. ' + total.toFixed(2);
            
            // Update hidden fields
            document.getElementById('subtotalValue').value = subtotal;
            document.getElementById('totalValue').value = total;
            
            // Update paid amount
            document.getElementById('paidAmount').value = total.toFixed(2);
            
            // If using free visit, show badge
            if (useFreeVisit) {
                document.getElementById('totalDisplay').innerHTML = 'Rs. 0.00 <span class="free-visit-badge">FREE VISIT</span>';
            }
        }
        
        // Clear discount amount when percent is entered
        document.getElementById('discountPercent').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('discountAmount').value = '';
            }
        });
        
        // Clear discount percent when amount is entered
        document.getElementById('discountAmount').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('discountPercent').value = '';
            }
        });
    </script>
</body>
</html>