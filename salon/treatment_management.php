<?php
require_once 'config.php';
requireLogin();

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get appointment details
$appointment = $conn->query("
    SELECT a.*, c.name as customer_name, c.mobile, s.service_name, s.price 
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.id = $appointment_id
")->fetch_assoc();

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

$success = '';
$error = '';

// Handle treatment submission
if (isset($_POST['save_treatment'])) {
    $treatment_date = cleanInput($_POST['treatment_date']);
    $treatment_time = cleanInput($_POST['treatment_time']);
    $chief_complaint = cleanInput($_POST['chief_complaint']);
    $diagnosis = cleanInput($_POST['diagnosis']);
    $treatment_given = cleanInput($_POST['treatment_given']);
    $products_used = cleanInput($_POST['products_used']);
    $medicines_prescribed = cleanInput($_POST['medicines_prescribed']);
    $dosage_instructions = cleanInput($_POST['dosage_instructions']);
    $notes = cleanInput($_POST['notes']);
    $next_visit_date = cleanInput($_POST['next_visit_date']);
    $treated_by = $_SESSION['admin_id'];
    $customer_id = $appointment['customer_id'];
    
    // Check if treatment already exists
    $existing = $conn->query("SELECT id FROM treatment_records WHERE appointment_id = $appointment_id");
    
    if ($existing->num_rows > 0) {
        // Update existing treatment
        $treatment_id = $existing->fetch_assoc()['id'];
        $stmt = $conn->prepare("UPDATE treatment_records SET treatment_date = ?, treatment_time = ?, chief_complaint = ?, diagnosis = ?, treatment_given = ?, products_used = ?, medicines_prescribed = ?, dosage_instructions = ?, notes = ?, next_visit_date = ?, treated_by = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssii", $treatment_date, $treatment_time, $chief_complaint, $diagnosis, $treatment_given, $products_used, $medicines_prescribed, $dosage_instructions, $notes, $next_visit_date, $treated_by, $treatment_id);
    } else {
        // Insert new treatment
        $stmt = $conn->prepare("INSERT INTO treatment_records (appointment_id, customer_id, treatment_date, treatment_time, chief_complaint, diagnosis, treatment_given, products_used, medicines_prescribed, dosage_instructions, notes, next_visit_date, treated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssssssssi", $appointment_id, $customer_id, $treatment_date, $treatment_time, $chief_complaint, $diagnosis, $treatment_given, $products_used, $medicines_prescribed, $dosage_instructions, $notes, $next_visit_date, $treated_by);
    }
    
    if ($stmt->execute()) {
        // Update appointment status to completed
        $conn->query("UPDATE appointments SET status = 'completed' WHERE id = $appointment_id");
        $success = "Treatment record saved successfully!";
    } else {
        $error = "Error saving treatment: " . $conn->error;
    }
}

// Get existing treatment record
$treatment = $conn->query("SELECT * FROM treatment_records WHERE appointment_id = $appointment_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treatment Management - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Treatment Management</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Appointment Info -->
        <div class="section">
            <h2>Appointment Information</h2>
            <div class="form-grid">
                <div>
                    <strong>Customer:</strong> <?php echo $appointment['customer_name']; ?><br>
                    <strong>Mobile:</strong> <?php echo $appointment['mobile']; ?>
                </div>
                <div>
                    <strong>Service:</strong> <?php echo $appointment['service_name']; ?><br>
                    <strong>Date:</strong> <?php echo formatDate($appointment['appointment_date']); ?> at <?php echo formatTime($appointment['appointment_time']); ?>
                </div>
            </div>
        </div>
        
        <!-- Treatment Form -->
        <div class="section">
            <h2>Treatment Details</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Treatment Date *</label>
                        <input type="date" name="treatment_date" 
                               value="<?php echo $treatment ? $treatment['treatment_date'] : date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Treatment Time *</label>
                        <input type="time" name="treatment_time" 
                               value="<?php echo $treatment ? $treatment['treatment_time'] : date('H:i'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Chief Complaint</label>
                    <textarea name="chief_complaint" rows="2" placeholder="Patient's main concerns or symptoms"><?php echo $treatment ? htmlspecialchars($treatment['chief_complaint']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Diagnosis</label>
                    <textarea name="diagnosis" rows="2" placeholder="Medical diagnosis or assessment"><?php echo $treatment ? htmlspecialchars($treatment['diagnosis']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Treatment Given *</label>
                    <textarea name="treatment_given" rows="4" placeholder="Describe the treatment provided" required><?php echo $treatment ? htmlspecialchars($treatment['treatment_given']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Products/Materials Used</label>
                    <textarea name="products_used" rows="3" placeholder="List products or materials used during treatment"><?php echo $treatment ? htmlspecialchars($treatment['products_used']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Medicines Prescribed</label>
                    <textarea name="medicines_prescribed" rows="3" placeholder="List medicines prescribed"><?php echo $treatment ? htmlspecialchars($treatment['medicines_prescribed']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Dosage Instructions</label>
                    <textarea name="dosage_instructions" rows="3" placeholder="Dosage and usage instructions"><?php echo $treatment ? htmlspecialchars($treatment['dosage_instructions']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Additional Notes</label>
                    <textarea name="notes" rows="3" placeholder="Any additional observations or recommendations"><?php echo $treatment ? htmlspecialchars($treatment['notes']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Next Visit Date (if applicable)</label>
                    <input type="date" name="next_visit_date" 
                           value="<?php echo $treatment ? $treatment['next_visit_date'] : ''; ?>">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="save_treatment" class="btn">Save Treatment Record</button>
                    <a href="appointment_view.php?id=<?php echo $appointment_id; ?>" class="btn btn-secondary">Cancel</a>
                    <a href="billing_create.php?appointment_id=<?php echo $appointment_id; ?>" class="btn btn-success">Proceed to Billing →</a>
                </div>
            </form>
        </div>
        
        <!-- Previous Treatment History -->
        <?php
        $customer_id = $appointment['customer_id'];
        $previous_treatments = $conn->query("
            SELECT t.*, s.service_name, a.appointment_date 
            FROM treatment_records t
            JOIN appointments a ON t.appointment_id = a.id
            JOIN services s ON a.service_id = s.id
            WHERE t.customer_id = $customer_id AND t.appointment_id != $appointment_id
            ORDER BY t.treatment_date DESC
            LIMIT 5
        ");
        
        if ($previous_treatments->num_rows > 0):
        ?>
        <div class="section">
            <h2>Previous Treatment History</h2>
            <?php while ($prev = $previous_treatments->fetch_assoc()): ?>
                <div style="background: #f8f9fa; padding: 15px; margin-bottom: 15px; border-radius: 5px; border-left: 4px solid #667eea;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong><?php echo $prev['service_name']; ?></strong>
                        <span style="color: #666;"><?php echo formatDate($prev['treatment_date']); ?></span>
                    </div>
                    <?php if ($prev['diagnosis']): ?>
                        <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($prev['diagnosis']); ?></p>
                    <?php endif; ?>
                    <p><strong>Treatment:</strong> <?php echo htmlspecialchars($prev['treatment_given']); ?></p>
                    <?php if ($prev['medicines_prescribed']): ?>
                        <p><strong>Medicines:</strong> <?php echo htmlspecialchars($prev['medicines_prescribed']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>