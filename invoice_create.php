<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

// Get appointment details if provided
$appointment = null;
$customer = null;
if ($appointment_id) {
    $apt_query = $conn->query("
        SELECT a.*, c.*, s.service_name, s.price 
        FROM appointments a
        JOIN customers c ON a.customer_id = c.id
        JOIN services s ON a.service_id = s.id
        WHERE a.id = $appointment_id
    ");
    $appointment = $apt_query->fetch_assoc();
    if ($appointment) {
        $customer = $appointment;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $appointment_id_post = intval($_POST['appointment_id']) ?: null;
    $invoice_date = cleanInput($_POST['invoice_date']);
    $payment_method = cleanInput($_POST['payment_method']);
    $paid_amount = floatval($_POST['paid_amount']);
    
    $services_selected = $_POST['services'];
    $quantities = $_POST['quantities'];
    
    // Calculate total
    $total_amount = 0;
    $invoice_items = [];
    
    foreach ($services_selected as $index => $service_id) {
        if ($service_id) {
            $service = $conn->query("SELECT price FROM services WHERE id = $service_id")->fetch_assoc();
            $quantity = intval($quantities[$index]);
            $price = $service['price'];
            $item_total = $price * $quantity;
            $total_amount += $item_total;
            
            $invoice_items[] = [
                'service_id' => $service_id,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $item_total
            ];
        }
    }
    
    // Determine payment status
    if ($paid_amount >= $total_amount) {
        $payment_status = 'paid';
    } elseif ($paid_amount > 0) {
        $payment_status = 'partial';
    } else {
        $payment_status = 'unpaid';
    }
    
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert invoice
    $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, customer_id, appointment_id, total_amount, paid_amount, payment_status, payment_method, invoice_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiddsss", $invoice_number, $customer_id, $appointment_id_post, $total_amount, $paid_amount, $payment_status, $payment_method, $invoice_date);
    
    if ($stmt->execute()) {
        $invoice_id = $conn->insert_id;
        
        // Insert invoice items
        foreach ($invoice_items as $item) {
            $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, service_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $invoice_id, $item['service_id'], $item['quantity'], $item['price'], $item['total']);
            $stmt->execute();
        }
        
        $success = "Invoice created successfully!";
        header("refresh:2;url=invoice_view.php?id=$invoice_id");
    } else {
        $error = "Error creating invoice: " . $conn->error;
    }
}

// Get all customers
$customers = $conn->query("SELECT id, name, mobile FROM customers ORDER BY name ASC");

// Get all active services
$services = $conn->query("SELECT * FROM services WHERE active = 1 ORDER BY service_name ASC");
$services_list = [];
while ($s = $services->fetch_assoc()) {
    $services_list[] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Create Invoice</h1>
        
        <div class="section">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="invoiceForm">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Customer *</label>
                        <select name="customer_id" required <?php echo $customer ? 'disabled' : ''; ?>>
                            <option value="">Select Customer</option>
                            <?php 
                            $customers_reset = $conn->query("SELECT id, name, mobile FROM customers ORDER BY name ASC");
                            while ($cust = $customers_reset->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cust['id']; ?>" 
                                        <?php echo ($customer && $customer['id'] == $cust['id']) ? 'selected' : ''; ?>>
                                    <?php echo $cust['name']; ?> (<?php echo $cust['mobile']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if ($customer): ?>
                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Invoice Date *</label>
                        <input type="date" name="invoice_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <h3>Services</h3>
                <div id="servicesContainer">
                    <div class="service-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <div style="flex: 2;">
                            <select name="services[]" required onchange="updateTotal()">
                                <option value="">Select Service</option>
                                <?php foreach ($services_list as $service): ?>
                                    <option value="<?php echo $service['id']; ?>" 
                                            data-price="<?php echo $service['price']; ?>"
                                            <?php echo ($appointment && $appointment['service_id'] == $service['id']) ? 'selected' : ''; ?>>
                                        <?php echo $service['service_name']; ?> - Rs. <?php echo number_format($service['price'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <input type="number" name="quantities[]" value="1" min="1" required 
                                   onchange="updateTotal()" placeholder="Quantity">
                        </div>
                        <div>
                            <button type="button" onclick="removeService(this)" class="btn btn-sm btn-danger">Remove</button>
                        </div>
                    </div>
                </div>
                
                <button type="button" onclick="addService()" class="btn btn-sm" style="margin-bottom: 20px;">+ Add Service</button>
                
                <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <h3>Total Amount: Rs. <span id="totalAmount">0.00</span></h3>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Paid Amount *</label>
                        <input type="number" name="paid_amount" step="0.01" min="0" required 
                               value="0" onchange="updateTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Create Invoice</button>
                    <a href="invoices.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const servicesData = <?php echo json_encode($services_list); ?>;
        
        function addService() {
            const container = document.getElementById('servicesContainer');
            const row = document.createElement('div');
            row.className = 'service-row';
            row.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px;';
            
            let optionsHTML = '<option value="">Select Service</option>';
            servicesData.forEach(service => {
                optionsHTML += `<option value="${service.id}" data-price="${service.price}">${service.service_name} - Rs. ${parseFloat(service.price).toFixed(2)}</option>`;
            });
            
            row.innerHTML = `
                <div style="flex: 2;">
                    <select name="services[]" required onchange="updateTotal()">
                        ${optionsHTML}
                    </select>
                </div>
                <div style="flex: 1;">
                    <input type="number" name="quantities[]" value="1" min="1" required 
                           onchange="updateTotal()" placeholder="Quantity">
                </div>
                <div>
                    <button type="button" onclick="removeService(this)" class="btn btn-sm btn-danger">Remove</button>
                </div>
            `;
            
            container.appendChild(row);
        }
        
        function removeService(btn) {
            btn.closest('.service-row').remove();
            updateTotal();
        }
        
        function updateTotal() {
            const serviceSelects = document.querySelectorAll('select[name="services[]"]');
            const quantities = document.querySelectorAll('input[name="quantities[]"]');
            let total = 0;
            
            serviceSelects.forEach((select, index) => {
                if (select.value) {
                    const option = select.options[select.selectedIndex];
                    const price = parseFloat(option.getAttribute('data-price') || 0);
                    const qty = parseInt(quantities[index].value || 1);
                    total += price * qty;
                }
            });
            
            document.getElementById('totalAmount').textContent = total.toFixed(2);
        }
        
        // Initial calculation
        updateTotal();
    </script>
</body>
</html>