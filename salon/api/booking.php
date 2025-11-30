<?php
require_once '../config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_appointment':
        createAppointment();
        break;
    case 'verify_otp':
        verifyOTP();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function createAppointment() {
    global $conn;
    
    $customer_data = json_decode($_POST['customer_data'], true);
    $appointment_data = json_decode($_POST['appointment_data'], true);
    $service_id = clean_input($_POST['service_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if customer exists
        $stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
        $stmt->bind_param("s", $customer_data['mobile']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $customer_id = $customer['id'];
            
            // Update customer info
            $stmt = $conn->prepare("UPDATE customers SET name = ?, dob = ?, city = ? WHERE id = ?");
            $stmt->bind_param("sssi", 
                $customer_data['name'], 
                $customer_data['dob'], 
                $customer_data['city'], 
                $customer_id
            );
            $stmt->execute();
        } else {
            // Create new customer
            $stmt = $conn->prepare("INSERT INTO customers (name, mobile, dob, city) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", 
                $customer_data['name'], 
                $customer_data['mobile'], 
                $customer_data['dob'], 
                $customer_data['city']
            );
            $stmt->execute();
            $customer_id = $conn->insert_id;
        }
        
        // Check if time slot is still available
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE appointment_date = ? 
            AND appointment_time = ? 
            AND status NOT IN ('cancelled')
        ");
        $stmt->bind_param("ss", $appointment_data['date'], $appointment_data['time']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            throw new Exception('This time slot is no longer available');
        }
        
        // Generate OTP
        $otp = generate_otp();
        
        // Create appointment
        $stmt = $conn->prepare("
            INSERT INTO appointments (customer_id, service_id, appointment_date, appointment_time, otp, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iisss", 
            $customer_id, 
            $service_id, 
            $appointment_data['date'], 
            $appointment_data['time'],
            $otp
        );
        $stmt->execute();
        $appointment_id = $conn->insert_id;
        
        // Send OTP via SMS
        $message = "Your OTP for salon booking is: " . $otp . ". Valid for 10 minutes.";
        $sms_result = send_sms($customer_data['mobile'], $message);
        
        if (!$sms_result['success']) {
            throw new Exception('Failed to send OTP. Please try again.');
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'appointment_id' => $appointment_id,
            'message' => 'OTP sent successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function verifyOTP() {
    global $conn;
    
    $appointment_id = clean_input($_POST['appointment_id']);
    $otp = clean_input($_POST['otp']);
    
    try {
        // Get appointment details
        $stmt = $conn->prepare("
            SELECT a.*, c.name as customer_name, c.mobile, s.name as service_name, s.price
            FROM appointments a
            JOIN customers c ON a.customer_id = c.id
            JOIN services s ON a.service_id = s.id
            WHERE a.id = ?
        ");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Appointment not found');
        }
        
        $appointment = $result->fetch_assoc();
        
        // Check OTP
        if ($appointment['otp'] !== $otp) {
            throw new Exception('Invalid OTP');
        }
        
        // Check if already verified
        if ($appointment['otp_verified']) {
            throw new Exception('OTP already verified');
        }
        
        // Update appointment status
        $stmt = $conn->prepare("
            UPDATE appointments 
            SET otp_verified = TRUE, status = 'confirmed' 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        
        // Send confirmation SMS
        $message = "Your appointment is confirmed!\n" .
                   "Service: " . $appointment['service_name'] . "\n" .
                   "Date: " . date('d M Y', strtotime($appointment['appointment_date'])) . "\n" .
                   "Time: " . date('h:i A', strtotime($appointment['appointment_time'])) . "\n" .
                   "Thank you!";
        
        send_sms($appointment['mobile'], $message);
        
        echo json_encode([
            'success' => true,
            'appointment' => $appointment,
            'message' => 'Booking confirmed successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>