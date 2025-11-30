<?php
// api.php - Backend API Handler
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$conn = getDBConnection();

switch ($action) {
    
    // Get blocked dates
    case 'get_blocked_dates':
        $result = $conn->query("SELECT blocked_date FROM blocked_dates");
        $dates = [];
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['blocked_date'];
        }
        echo json_encode(['blocked_dates' => $dates]);
        break;
    
    // Send booking OTP
    case 'send_booking_otp':
        $mobile = $_POST['mobile'] ?? '';
        
        if (empty($mobile)) {
            echo json_encode(['success' => false, 'message' => 'Mobile number required']);
            exit;
        }
        
        try {
            $otp = generateOTP($mobile, 'booking');
            echo json_encode(['success' => true, 'message' => 'OTP sent to your mobile']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
        }
        break;
    
    // Confirm booking
    case 'confirm_booking':
        $mobile = $_POST['mobile'] ?? '';
        $otp = $_POST['otp'] ?? '';
        $name = $_POST['name'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $city = $_POST['city'] ?? '';
        $referral_source = $_POST['referral_source'] ?? '';
        $service_category_id = $_POST['service_category'] ?? '';
        $service_id = $_POST['service_id'] ?? '';
        $appointment_date = $_POST['appointment_date'] ?? '';
        
        if (!verifyOTP($mobile, $otp, 'booking')) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
            exit;
        }
        
        // Check if customer exists
        $stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer_id = $result->fetch_assoc()['id'];
        } else {
            // Create new customer
            $stmt = $conn->prepare("INSERT INTO customers (name, mobile, dob, city, referral_source) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $mobile, $dob, $city, $referral_source);
            $stmt->execute();
            $customer_id = $conn->insert_id;
        }
        
        // Get service category name
        $stmt = $conn->prepare("SELECT name FROM service_categories WHERE id = ?");
        $stmt->bind_param("i", $service_category_id);
        $stmt->execute();
        $service_category = $stmt->get_result()->fetch_assoc()['name'];
        
        // Create appointment
        $stmt = $conn->prepare("INSERT INTO appointments (customer_id, service_category, service_id, appointment_date, referral_source, status) VALUES (?, ?, ?, ?, ?, 'Confirmed')");
        $stmt->bind_param("isiss", $customer_id, $service_category, $service_id, $appointment_date, $referral_source);
        
        if ($stmt->execute()) {
            // Send confirmation SMS
            $confirmMsg = "Your appointment is confirmed for " . date('d M Y', strtotime($appointment_date)) . ". Thank you!";
            sendOTP($mobile, $confirmMsg); // Reusing sendOTP function for SMS
            
            echo json_encode(['success' => true, 'message' => 'Appointment booked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to book appointment']);
        }
        break;
    
    // Send login OTP
    case 'send_login_otp':
        $mobile = $_POST['mobile'] ?? '';
        
        // Check if customer exists
        $stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Customer not found. Please book an appointment first.']);
            exit;
        }
        
        try {
            $otp = generateOTP($mobile, 'login');
            echo json_encode(['success' => true, 'message' => 'OTP sent to your mobile']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
        }
        break;
    
    // Verify login
    case 'verify_login':
        $mobile = $_POST['mobile'] ?? '';
        $otp = $_POST['otp'] ?? '';
        
        if (!verifyOTP($mobile, $otp, 'login')) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
            exit;
        }
        
        // Get customer
        $stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $_SESSION['customer_id'] = $customer['id'];
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
        break;
    
    // Upload payment slip
    case 'upload_payment':
        if (!isset($_SESSION['customer_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $bill_id = $_POST['bill_id'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $customer_id = $_SESSION['customer_id'];
        
        if (isset($_FILES['payment_slip'])) {
            $upload_dir = 'uploads/payments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
            $file_name = 'payment_' . $bill_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $file_path)) {
                $stmt = $conn->prepare("INSERT INTO payment_slips (bill_id, customer_id, file_path, amount) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisd", $bill_id, $customer_id, $file_path, $amount);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Payment slip uploaded successfully. Admin will verify soon.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save payment record']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        }
        break;
    
    // Logout
    case 'logout':
        session_destroy();
        header('Location: index.php');
        exit;
        break;
    
    // Admin login
    case 'admin_login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_role'] = $admin['role'];
                echo json_encode(['success' => true, 'message' => 'Login successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
        }
        break;
    
    // Get all customers (Admin)
    case 'get_customers':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT * FROM customers ORDER BY created_at DESC");
        $customers = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'customers' => $customers]);
        break;
    
    // Get all appointments (Admin)
    case 'get_appointments':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT a.*, c.name as customer_name, c.mobile, s.name as service_name 
                                FROM appointments a 
                                JOIN customers c ON a.customer_id = c.id 
                                JOIN services s ON a.service_id = s.id 
                                ORDER BY a.appointment_date DESC");
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'appointments' => $appointments]);
        break;
    
    // Add service category (Admin)
    case 'add_service_category':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $stmt = $conn->prepare("INSERT INTO service_categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add category']);
        }
        break;
    
    // Add service (Admin)
    case 'add_service':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $category_id = $_POST['category_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO services (category_id, name, price) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $category_id, $name, $price);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Service added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add service']);
        }
        break;
    
    // Add product (Admin)
    case 'add_product':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("sdi", $name, $price, $stock);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product']);
        }
        break;
    
    // Block date (Admin)
    case 'block_date':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $date = $_POST['date'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO blocked_dates (blocked_date, reason) VALUES (?, ?)");
        $stmt->bind_param("ss", $date, $reason);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Date blocked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to block date']);
        }
        break;
    
    // Add treatment (Admin)
    case 'add_treatment':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $appointment_id = $_POST['appointment_id'] ?? '';
        $customer_id = $_POST['customer_id'] ?? '';
        $treatment_details = $_POST['treatment_details'] ?? '';
        $doctor_notes = $_POST['doctor_notes'] ?? '';
        $ready_for_billing = $_POST['ready_for_billing'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO treatments (appointment_id, customer_id, treatment_details, doctor_notes, ready_for_billing) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $appointment_id, $customer_id, $treatment_details, $doctor_notes, $ready_for_billing);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Treatment added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add treatment']);
        }
        break;
    
    // Update customer free visits (Admin)
    case 'update_free_visits':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $customer_id = $_POST['customer_id'] ?? '';
        $free_visits = $_POST['free_visits'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE customers SET free_visits = ? WHERE id = ?");
        $stmt->bind_param("ii", $free_visits, $customer_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Free visits updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }
        break;
    
    // Get all service categories (Admin)
    case 'get_categories':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT * FROM service_categories ORDER BY id ASC");
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;
    
    // Get all services (Admin)
    case 'get_services':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT s.*, sc.name as category_name 
                                FROM services s 
                                JOIN service_categories sc ON s.category_id = sc.id 
                                ORDER BY s.id ASC");
        $services = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'services' => $services]);
        break;
    
    // Get all products (Admin)
    case 'get_products':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT * FROM products ORDER BY id ASC");
        $products = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'products' => $products]);
        break;
    
    // Get all treatments (Admin)
    case 'get_treatments':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT t.*, c.name as customer_name, a.appointment_date 
                                FROM treatments t 
                                JOIN customers c ON t.customer_id = c.id 
                                JOIN appointments a ON t.appointment_id = a.id 
                                ORDER BY t.created_at DESC");
        $treatments = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'treatments' => $treatments]);
        break;
    
    // Get all bills (Admin)
    case 'get_bills':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT b.*, c.name as customer_name, c.mobile 
                                FROM bills b 
                                JOIN customers c ON b.customer_id = c.id 
                                ORDER BY b.bill_date DESC");
        $bills = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'bills' => $bills]);
        break;
    
    // Get blocked dates (Admin)
    case 'get_blocked_dates_admin':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $result = $conn->query("SELECT * FROM blocked_dates ORDER BY blocked_date DESC");
        $dates = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'dates' => $dates]);
        break;
    
    // Get dashboard stats (Admin)
    case 'get_dashboard_stats':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
        $total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
        $today_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['count'];
        $pending_bills = $conn->query("SELECT COUNT(*) as count FROM bills WHERE balance > 0")->fetch_assoc()['count'];
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_customers' => $total_customers,
                'total_appointments' => $total_appointments,
                'today_appointments' => $today_appointments,
                'pending_bills' => $pending_bills
            ]
        ]);
        break;
    
    // Delete service category (Admin)
    case 'delete_category':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM service_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
        }
        break;
    
    // Delete service (Admin)
    case 'delete_service':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete service']);
        }
        break;
    
    // Delete product (Admin)
    case 'delete_product':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
        }
        break;
    
    // Delete blocked date (Admin)
    case 'delete_blocked_date':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM blocked_dates WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Date unblocked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unblock date']);
        }
        break;
    
    // Add customer (Admin)
    case 'add_customer':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $city = $_POST['city'] ?? '';
        $free_visits = $_POST['free_visits'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO customers (name, mobile, dob, city, free_visits) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $mobile, $dob, $city, $free_visits);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add customer']);
        }
        break;
    
    // Get customer by ID
    case 'get_customer':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_GET['id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['success' => true, 'customer' => $customer]);
        break;
    
    // Update customer (Admin)
    case 'update_customer':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_POST['customer_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $city = $_POST['city'] ?? '';
        
        $stmt = $conn->prepare("UPDATE customers SET name = ?, mobile = ?, dob = ?, city = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $mobile, $dob, $city, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update customer']);
        }
        break;
    
    // Add appointment (Admin)
    case 'add_appointment_admin':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $mobile = $_POST['customer_mobile'] ?? '';
        $service_category_id = $_POST['service_category'] ?? '';
        $service_id = $_POST['service_id'] ?? '';
        $appointment_date = $_POST['appointment_date'] ?? '';
        
        // Get customer
        $stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
            exit;
        }
        
        $customer_id = $result->fetch_assoc()['id'];
        
        // Get category name
        $stmt = $conn->prepare("SELECT name FROM service_categories WHERE id = ?");
        $stmt->bind_param("i", $service_category_id);
        $stmt->execute();
        $service_category = $stmt->get_result()->fetch_assoc()['name'];
        
        // Create appointment
        $stmt = $conn->prepare("INSERT INTO appointments (customer_id, service_category, service_id, appointment_date, status) VALUES (?, ?, ?, ?, 'Confirmed')");
        $stmt->bind_param("isis", $customer_id, $service_category, $service_id, $appointment_date);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create appointment']);
        }
        break;
    
    // Delete appointment
    case 'delete_appointment':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete appointment']);
        }
        break;
    
    // Get customer by mobile
    case 'get_customer_by_mobile':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $mobile = $_GET['mobile'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM customers WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => true, 'customer' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
        break;
    
    // Get customer appointments
    case 'get_customer_appointments':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $customer_id = $_GET['customer_id'] ?? '';
        $stmt = $conn->prepare("SELECT a.*, s.name as service_name FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.customer_id = ? ORDER BY a.appointment_date DESC");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'appointments' => $appointments]);
        break;
    
    // Create bill
    case 'create_bill':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $mobile = $_POST['customer_mobile'] ?? '';
        $bill_type = $_POST['bill_type'] ?? 'invoice';
        $total_amount = $_POST['total_amount'] ?? 0;
        $advance_paid = $_POST['advance_paid'] ?? 0;
        $free_visits_used = $_POST['free_visits_used'] ?? 0;
        $appointment_id = $_POST['appointment_id'] ?? null;
        $items = json_decode($_POST['items'] ?? '[]', true);
        
        // Get customer
        $stmt = $conn->prepare("SELECT id FROM customers WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
            exit;
        }
        
        $customer_id = $result->fetch_assoc()['id'];
        
        // Generate bill number
        $bill_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $balance = $total_amount - $advance_paid;
        
        // Insert bill
        $stmt = $conn->prepare("INSERT INTO bills (bill_number, customer_id, appointment_id, total_amount, advance_paid, balance, free_visits_used, bill_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $appointment_id_val = $appointment_id ?: null;
        $stmt->bind_param("siidddis", $bill_number, $customer_id, $appointment_id_val, $total_amount, $advance_paid, $balance, $free_visits_used, $bill_type);
        
        if ($stmt->execute()) {
            $bill_id = $conn->insert_id;
            
            // Insert bill items
            foreach ($items as $item) {
                $item_total = $item['quantity'] * $item['price'];
                $stmt = $conn->prepare("INSERT INTO bill_items (bill_id, item_type, item_id, item_name, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isisdd", $bill_id, $item['type'], $item['id'], $item['name'], $item['quantity'], $item['price'], $item_total);
                $stmt->execute();
            }
            
            // Update customer free visits if used
            if ($free_visits_used > 0) {
                $stmt = $conn->prepare("UPDATE customers SET free_visits = free_visits - ? WHERE id = ?");
                $stmt->bind_param("ii", $free_visits_used, $customer_id);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'Bill created successfully', 'bill_number' => $bill_number]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create bill']);
        }
        break;
    
    // Get bill details
    case 'get_bill_details':
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $id = $_GET['id'] ?? '';
        
        // Get bill
        $stmt = $conn->prepare("SELECT b.*, c.name as customer_name, c.mobile FROM bills b JOIN customers c ON b.customer_id = c.id WHERE b.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();
        
        // Get bill items
        $stmt = $conn->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'bill' => $bill, 'items' => $items]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
?>