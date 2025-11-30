<?php
require_once 'config.php';
requireLogin();

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get appointment details
$appointment = $conn->query("
    SELECT a.*, c.name, c.mobile, c.address, s.service_name, s.duration, s.price 
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.id = $appointment_id
")->fetch_assoc();

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

// Get treatment details if exists
$treatment = $conn->query("
    SELECT * FROM treatments 
    WHERE appointment_id = $appointment_id
")->fetch_assoc();

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = cleanInput($_POST['status']);
    $conn->query("UPDATE appointments SET status = '$new_status' WHERE id = $appointment_id");
    header("refresh:0");
}

// Handle treatment save
if (isset($_POST['save_treatment'])) {
    $treatment_details = cleanInput($_POST['treatment_details']);
    $products_used = cleanInput($_POST['products_used']);
    $therapist_notes = cleanInput($_POST['therapist_notes']);
    $treatment_date = cleanInput($_POST['treatment_date']);
    
    if ($treatment) {
        $stmt = $conn->prepare("UPDATE treatments SET treatment_details = ?, products_used = ?, therapist_notes = ?, treatment_date = ? WHERE appointment_id = ?");
        $stmt->bind_param("ssssi", $treatment_details, $products_used, $therapist_notes, $treatment_date, $appointment_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO treatments (appointment_id, customer_id, treatment_details, products_used, therapist_notes, treatment_date) VALUES (?, ?, ?, ?, ?, ?)");
        $customer_id = $appointment['customer_id'];
        $stmt->bind_param("iissss", $appointment_id, $customer_id, $treatment_details, $products_used, $therapist_notes, $treatment_date);
    }
    
    if ($stmt->execute()) {
        $success = "Treatment details saved successfully!";
        header("refresh:1");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Appointment Details</h1>
            <div>
                <a href="appointment_edit.php?id=<?php echo $appointment_id; ?>" class="btn btn-success">Edit</a>
                <?php if ($appointment['status'] != 'completed'): ?>
                    <a href="treatment_management.php?id=<?php echo $appointment_id; ?>" class="btn" style="background: #28a745;">Manage Treatment</a>
                <?php endif; ?>
                <?php if ($treatment): ?>
                    <a href="billing_create.php?appointment_id=<?php echo $appointment_id; ?>" class="btn" style="background: #ffc107; color: #000;">Proceed to Billing</a>
                <?php endif; ?>
                <a href="appointments.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
        
        <!-- Appointment Info -->
        <div class="section">
            <h2>Appointment Information</h2>
            <div class="form-grid">
                <div>
                    <strong>Customer:</strong> <?php echo $appointment['name']; ?><br>
                    <strong>Mobile:</strong> <?php echo $appointment['mobile']; ?>
                </div>
                <div>
                    <strong>Date:</strong> <?php echo formatDate($appointment['appointment_date']); ?><br>
                    <strong>Time:</strong> <?php echo formatTime($appointment['appointment_time']); ?>
                </div>
                <div>
                    <strong>Service:</strong> <?php echo $appointment['service_name']; ?><br>
                    <strong>Duration:</strong> <?php echo $appointment['duration']; ?> minutes
                </div>
                <div>
                    <strong>Price:</strong> Rs. <?php echo number_format($appointment['price'], 2); ?><br>
                    <strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($appointment['notes']): ?>
                <div style="margin-top: 15px;">
                    <strong>Notes:</strong><br>
                    <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                </div>
            <?php endif; ?>
            
            <!-- Update Status -->
            <form method="POST" style="margin-top: 20px;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <label><strong>Update Status:</strong></label>
                    <select name="status">
                        <option value="pending" <?php echo $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $appointment['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-sm">Update</button>
                </div>
            </form>
        </div>
        
        <!-- Treatment Details -->
        <div class="section">
            <h2>Treatment Details</h2>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Treatment Date *</label>
                    <input type="date" name="treatment_date" 
                           value="<?php echo $treatment ? $treatment['treatment_date'] : $appointment['appointment_date']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Treatment Details *</label>
                    <textarea name="treatment_details" required><?php echo $treatment ? htmlspecialchars($treatment['treatment_details']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Products Used</label>
                    <textarea name="products_used"><?php echo $treatment ? htmlspecialchars($treatment['products_used']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Therapist Notes</label>
                    <textarea name="therapist_notes"><?php echo $treatment ? htmlspecialchars($treatment['therapist_notes']) : ''; ?></textarea>
                </div>
                
                <button type="submit" name="save_treatment" class="btn">Save Treatment Details</button>
            </form>
        </div>
    </div>
</body>
</html>