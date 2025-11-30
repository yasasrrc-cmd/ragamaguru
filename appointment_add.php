<?php
require_once 'config.php';
requireLogin();
error_reporting(E_ALL);
ini_set('display_errors', 'On');
$success = '';
$error = '';
$preselected_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $service_id = intval($_POST['service_id']);
    $appointment_date = cleanInput($_POST['appointment_date']);
    $appointment_time = cleanInput($_POST['appointment_time']);
    $notes = cleanInput($_POST['notes']);
    $send_sms = isset($_POST['send_sms']);
    
    // Check for conflicts
    $check = $conn->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $check->bind_param("ss", $appointment_date, $appointment_time);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "This time slot is already booked!";
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments (customer_id, service_id, appointment_date, appointment_time, status, notes) VALUES (?, ?, ?, ?, 'confirmed', ?)");
        $stmt->bind_param("iisss", $customer_id, $service_id, $appointment_date, $appointment_time, $notes);
        
        if ($stmt->execute()) {
            $appointmentId = $conn->insert_id;
            
            // Send SMS notification
            //if ($send_sms) {
                $customer = $conn->query("SELECT * FROM customers WHERE id = $customer_id")->fetch_assoc();
                $service = $conn->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
                
                $message = "Hi {$customer['name']}, your appointment at Ragamaguru is confirmed for " . 
                          date('d M Y', strtotime($appointment_date)) . " at " . 
                          date('h:i A', strtotime($appointment_time)) . " for {$service['service_name']}.";
						  echo  $message ;
                
                sendSMS($customer['mobile'], $message);
            //}
            
            $success = "Appointment booked successfully!";
            header("refresh:2;url=appointment_view.php?id=$appointmentId");
        } else {
            $error = "Error booking appointment: " . $conn->error;
        }
    }
}

// Get all customers
$customers = $conn->query("SELECT id, name, mobile FROM customers ORDER BY name ASC");

// Get all active services
$services = $conn->query("SELECT * FROM services WHERE active = 1 ORDER BY service_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Book New Appointment</h1>
        
        <div class="section">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Customer *</label>
                        <select name="customer_id" required onchange="loadCustomerInfo(this.value)">
                            <option value="">Select Customer</option>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>" 
                                        <?php echo $preselected_customer == $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo $customer['name']; ?> (<?php echo $customer['mobile']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small><a href="customer_add.php" target="_blank">+ Add New Customer</a></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Service *</label>
                        <select name="service_id" required id="serviceSelect" onchange="updateServiceInfo()">
                            <option value="">Select Service</option>
                            <?php while ($service = $services->fetch_assoc()): ?>
                                <option value="<?php echo $service['id']; ?>" 
                                        data-duration="<?php echo $service['duration']; ?>"
                                        data-price="<?php echo $service['price']; ?>">
                                    <?php echo $service['service_name']; ?> - Rs. <?php echo number_format($service['price'], 2); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div id="serviceInfo" style="margin-top: 5px; font-size: 12px; color: #666;"></div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo date('Y-m-d'); ?>" onchange="checkAvailability()">
                    </div>
                    
                    <div class="form-group">
                        <label>Time *</label>
                        <input type="time" name="appointment_time" required onchange="checkAvailability()">
                    </div>
                </div>
                
                <div id="customerHistory" style="margin-bottom: 20px;"></div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="send_sms" checked>
                        Send SMS Confirmation
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Book Appointment</button>
                    <a href="appointments.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function updateServiceInfo() {
            const select = document.getElementById('serviceSelect');
            const option = select.options[select.selectedIndex];
            const info = document.getElementById('serviceInfo');
            
            if (option.value) {
                const duration = option.getAttribute('data-duration');
                info.innerHTML = 'Duration: ' + duration + ' minutes';
            } else {
                info.innerHTML = '';
            }
        }
        
        function loadCustomerInfo(customerId) {
            if (!customerId) return;
            
            fetch('api_customer_history.php?id=' + customerId)
                .then(response => response.json())
                .then(data => {
                    const historyDiv = document.getElementById('customerHistory');
                    if (data.appointments && data.appointments.length > 0) {
                        let html = '<div class="alert alert-info">';
                        html += '<strong>Customer History:</strong><br>';
                        html += 'Total Appointments: ' + data.total + '<br>';
                        html += 'Last Visit: ' + data.last_visit;
                        html += '</div>';
                        historyDiv.innerHTML = html;
                    } else {
                        historyDiv.innerHTML = '<div class="alert alert-info">New customer - No previous appointments</div>';
                    }
                });
        }
        
        function checkAvailability() {
            // Add availability checking logic if needed
        }
    </script>
</body>
</html>