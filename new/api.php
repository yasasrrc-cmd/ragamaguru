<?php
require_once 'config.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // ============== PUBLIC BOOKING APIs ==============
        case 'get_service_categories':
            $stmt = $pdo->query("SELECT * FROM service_categories WHERE status = 'active' ORDER BY name");
            jsonResponse(true, 'Categories fetched', $stmt->fetchAll());
            break;
            
        case 'get_services_by_category':
            $categoryId = $_GET['category_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM services WHERE category_id = ? AND status = 'active'");
            $stmt->execute([$categoryId]);
            jsonResponse(true, 'Services fetched', $stmt->fetchAll());
            break;
            
        case 'check_date_availability':
            $date = $_GET['date'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM blocked_dates WHERE date = ?");
            $stmt->execute([$date]);
            $blocked = $stmt->fetch();
            jsonResponse(true, 'Date checked', ['available' => !$blocked]);
            break;
            
        case 'book_appointment':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if customer exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile = ?");
            $stmt->execute([$data['mobile']]);
            $customer = $stmt->fetch();
            
            if (!$customer) {
                // Create new customer
                $stmt = $pdo->prepare("INSERT INTO customers (name, mobile, dob, city, how_know_us) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data['name'],
                    $data['mobile'],
                    $data['dob'],
                    $data['city'],
                    $data['how_know_us']
                ]);
                $customerId = $pdo->lastInsertId();
                
                // Initialize free visits
                $stmt = $pdo->prepare("INSERT INTO customer_visits (customer_id) VALUES (?)");
                $stmt->execute([$customerId]);
            } else {
                $customerId = $customer['id'];
            }
            
            // Generate OTP
            $otp = generateOTP();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store OTP
            $stmt = $pdo->prepare("INSERT INTO otp_verification (mobile, otp, purpose, expires_at) VALUES (?, ?, 'appointment', ?)");
            $stmt->execute([$data['mobile'], $otp, $expiresAt]);
            
            // Create pending appointment
            $stmt = $pdo->prepare("INSERT INTO appointments (customer_id, service_id, appointment_date, appointment_time, otp, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $customerId,
                $data['service_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $otp
            ]);
            $appointmentId = $pdo->lastInsertId();
            
            // Send OTP via SMS
            sendOTPSMS($data['mobile'], $otp, 'appointment confirmation');
            
            jsonResponse(true, 'OTP sent to your mobile', ['appointment_id' => $appointmentId, 'customer_id' => $customerId]);
            break;
            
        case 'verify_appointment_otp':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND otp = ? AND otp_verified = FALSE");
            $stmt->execute([$data['appointment_id'], $data['otp']]);
            $appointment = $stmt->fetch();
            
            if ($appointment) {
                // Mark as verified and confirmed
                $stmt = $pdo->prepare("UPDATE appointments SET otp_verified = TRUE, status = 'confirmed' WHERE id = ?");
                $stmt->execute([$data['appointment_id']]);
                jsonResponse(true, 'Appointment confirmed successfully!', ['appointment_id' => $data['appointment_id']]);
            } else {
                jsonResponse(false, 'Invalid OTP');
            }
            break;
            
        // ============== USER PANEL APIs ==============
        case 'send_login_otp':
            $mobile = $_POST['mobile'] ?? '';
            
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile = ?");
            $stmt->execute([$mobile]);
            $customer = $stmt->fetch();
            
            if (!$customer) {
                jsonResponse(false, 'Mobile number not found');
            }
            
            $otp = generateOTP();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $stmt = $pdo->prepare("INSERT INTO otp_verification (mobile, otp, purpose, expires_at) VALUES (?, ?, 'login', ?)");
            $stmt->execute([$mobile, $otp, $expiresAt]);
            
            sendOTPSMS($mobile, $otp, 'login');
            
            jsonResponse(true, 'OTP sent successfully');
            break;
            
        case 'verify_login_otp':
            $mobile = $_POST['mobile'] ?? '';
            $otp = $_POST['otp'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM otp_verification WHERE mobile = ? AND otp = ? AND purpose = 'login' AND verified = FALSE AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$mobile, $otp]);
            $otpRecord = $stmt->fetch();
            
            if ($otpRecord) {
                $stmt = $pdo->prepare("UPDATE otp_verification SET verified = TRUE WHERE id = ?");
                $stmt->execute([$otpRecord['id']]);
                
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE mobile = ?");
                $stmt->execute([$mobile]);
                $customer = $stmt->fetch();
                
                $_SESSION['user_id'] = $customer['id'];
                $_SESSION['user_mobile'] = $customer['mobile'];
                $_SESSION['user_name'] = $customer['name'];
                
                jsonResponse(true, 'Login successful', $customer);
            } else {
                jsonResponse(false, 'Invalid or expired OTP');
            }
            break;
            
        case 'get_user_dashboard':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(false, 'Not logged in');
            }
            
            $userId = $_SESSION['user_id'];
            
            // Get user info
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            // Get appointments
            $stmt = $pdo->prepare("SELECT a.*, s.name as service_name FROM appointments a 
                                   JOIN services s ON a.service_id = s.id 
                                   WHERE a.customer_id = ? ORDER BY a.appointment_date DESC");
            $stmt->execute([$userId]);
            $appointments = $stmt->fetchAll();
            
            // Get treatments
            $stmt = $pdo->prepare("SELECT * FROM treatments WHERE customer_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $treatments = $stmt->fetchAll();
            
            // Get bills
            $stmt = $pdo->prepare("SELECT * FROM bills WHERE customer_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $bills = $stmt->fetchAll();
            
            // Get free visits
            $stmt = $pdo->prepare("SELECT * FROM customer_visits WHERE customer_id = ?");
            $stmt->execute([$userId]);
            $visits = $stmt->fetch();
            
            jsonResponse(true, 'Dashboard data', [
                'user' => $user,
                'appointments' => $appointments,
                'treatments' => $treatments,
                'bills' => $bills,
                'free_visits' => $visits
            ]);
            break;
            
        case 'upload_payment_slip':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(false, 'Not logged in');
            }
            
            $billId = $_POST['bill_id'];
            $amount = $_POST['amount'];
            $paymentDate = $_POST['payment_date'];
            
            if (isset($_FILES['payment_slip'])) {
                $file = $_FILES['payment_slip'];
                $fileName = time() . '_' . $file['name'];
                $filePath = UPLOAD_DIR . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $stmt = $pdo->prepare("INSERT INTO payment_slips (bill_id, customer_id, file_path, amount, payment_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$billId, $_SESSION['user_id'], $filePath, $amount, $paymentDate]);
                    
                    jsonResponse(true, 'Payment slip uploaded successfully');
                } else {
                    jsonResponse(false, 'Failed to upload file');
                }
            } else {
                jsonResponse(false, 'No file uploaded');
            }
            break;
            
        // ============== ADMIN LOGIN ==============
        case 'admin_login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                jsonResponse(true, 'Login successful', $admin);
            } else {
                jsonResponse(false, 'Invalid credentials');
            }
            break;
            
        // ============== ADMIN - SERVICE MANAGEMENT ==============
        case 'add_service_category':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO service_categories (name, type) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['type']]);
            jsonResponse(true, 'Category added', ['id' => $pdo->lastInsertId()]);
            break;
            
        case 'add_service':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO services (category_id, name, price, duration) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['category_id'], $data['name'], $data['price'], $data['duration']]);
            jsonResponse(true, 'Service added', ['id' => $pdo->lastInsertId()]);
            break;
            
        case 'update_service':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE services SET name = ?, price = ?, duration = ?, status = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['price'], $data['duration'], $data['status'], $data['id']]);
            jsonResponse(true, 'Service updated');
            break;
            
        case 'delete_service':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(true, 'Service deleted');
            break;
            
        // ============== ADMIN - PRODUCT MANAGEMENT ==============
        case 'add_product':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
            $stmt->execute([$data['name'], $data['price'], $data['stock']]);
            jsonResponse(true, 'Product added', ['id' => $pdo->lastInsertId()]);
            break;
            
        case 'get_products':
            $stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY name");
            jsonResponse(true, 'Products fetched', $stmt->fetchAll());
            break;
            
        // ============== ADMIN - DATE MANAGEMENT ==============
        case 'block_date':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO blocked_dates (date, reason) VALUES (?, ?)");
            $stmt->execute([$data['date'], $data['reason']]);
            jsonResponse(true, 'Date blocked');
            break;
            
        case 'unblock_date':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $date = $_POST['date'];
            $stmt = $pdo->prepare("DELETE FROM blocked_dates WHERE date = ?");
            $stmt->execute([$date]);
            jsonResponse(true, 'Date unblocked');
            break;
            
        // ============== ADMIN - CUSTOMER MANAGEMENT ==============
        case 'get_all_customers':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $stmt = $pdo->query("SELECT c.*, cv.total_free_visits, cv.used_free_visits, cv.remaining_free_visits 
                                FROM customers c 
                                LEFT JOIN customer_visits cv ON c.id = cv.customer_id 
                                ORDER BY c.created_at DESC");
            jsonResponse(true, 'Customers fetched', $stmt->fetchAll());
            break;
            
        case 'add_customer':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO customers (name, mobile, dob, city, how_know_us) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data['name'], $data['mobile'], $data['dob'], $data['city'], $data['how_know_us']]);
            $customerId = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO customer_visits (customer_id) VALUES (?)");
            $stmt->execute([$customerId]);
            
            jsonResponse(true, 'Customer added', ['id' => $customerId]);
            break;
            
        // ============== ADMIN - APPOINTMENT MANAGEMENT ==============
        case 'get_all_appointments':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $stmt = $pdo->query("SELECT a.*, c.name as customer_name, c.mobile, s.name as service_name 
                                FROM appointments a 
                                JOIN customers c ON a.customer_id = c.id 
                                JOIN services s ON a.service_id = s.id 
                                ORDER BY a.appointment_date DESC, a.appointment_time DESC");
            jsonResponse(true, 'Appointments fetched', $stmt->fetchAll());
            break;
            
        case 'update_appointment_status':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->execute([$data['status'], $data['id']]);
            jsonResponse(true, 'Appointment updated');
            break;
            
        // ============== DOCTOR - TREATMENT MANAGEMENT ==============
        case 'add_treatment':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO treatments (appointment_id, customer_id, doctor_id, diagnosis, treatment_details, prescription, notes, ready_for_billing) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['appointment_id'],
                $data['customer_id'],
                $_SESSION['admin_id'],
                $data['diagnosis'],
                $data['treatment_details'],
                $data['prescription'],
                $data['notes'],
                $data['ready_for_billing'] ?? false
            ]);
            
            // Update appointment status
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $stmt->execute([$data['appointment_id']]);
            
            jsonResponse(true, 'Treatment added', ['id' => $pdo->lastInsertId()]);
            break;
            
        case 'update_treatment':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE treatments SET diagnosis = ?, treatment_details = ?, prescription = ?, notes = ?, ready_for_billing = ? WHERE id = ?");
            $stmt->execute([
                $data['diagnosis'],
                $data['treatment_details'],
                $data['prescription'],
                $data['notes'],
                $data['ready_for_billing'],
                $data['id']
            ]);
            jsonResponse(true, 'Treatment updated');
            break;
            
        case 'get_treatments_for_billing':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $stmt = $pdo->query("SELECT t.*, c.name as customer_name, c.mobile, a.appointment_date 
                                FROM treatments t 
                                JOIN customers c ON t.customer_id = c.id 
                                JOIN appointments a ON t.appointment_id = a.id 
                                WHERE t.ready_for_billing = TRUE 
                                AND t.id NOT IN (SELECT treatment_id FROM bills WHERE treatment_id IS NOT NULL)
                                ORDER BY t.created_at DESC");
            jsonResponse(true, 'Treatments fetched', $stmt->fetchAll());
            break;
            
        // ============== BILLING ==============
        case 'create_bill':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $pdo->beginTransaction();
            try {
                // Generate invoice number
                $invoiceNumber = 'INV' . date('Ymd') . sprintf('%04d', rand(1, 9999));
                
                // Calculate totals
                $subtotal = 0;
                foreach ($data['items'] as $item) {
                    $subtotal += $item['total_price'];
                }
                
                $discount = $data['discount'] ?? 0;
                $tax = $data['tax'] ?? 0;
                $total = $subtotal - $discount + $tax;
                $paidAmount = $data['paid_amount'] ?? 0;
                $advanceAmount = $data['advance_amount'] ?? 0;
                $remaining = $total - $paidAmount - $advanceAmount;
                
                $paymentStatus = 'unpaid';
                if ($paidAmount + $advanceAmount >= $total) {
                    $paymentStatus = 'paid';
                } elseif ($paidAmount + $advanceAmount > 0) {
                    $paymentStatus = 'partial';
                }
                
                // Insert bill
                $stmt = $pdo->prepare("INSERT INTO bills (invoice_number, customer_id, appointment_id, treatment_id, bill_type, subtotal, discount, tax, total_amount, paid_amount, advance_amount, remaining_amount, free_visits_granted, payment_status, notes, created_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $invoiceNumber,
                    $data['customer_id'],
                    $data['appointment_id'] ?? null,
                    $data['treatment_id'] ?? null,
                    $data['bill_type'] ?? 'invoice',
                    $subtotal,
                    $discount,
                    $tax,
                    $total,
                    $paidAmount,
                    $advanceAmount,
                    $remaining,
                    $data['free_visits_granted'] ?? 0,
                    $paymentStatus,
                    $data['notes'] ?? '',
                    $_SESSION['admin_id']
                ]);
                $billId = $pdo->lastInsertId();
                
                // Insert bill items
                foreach ($data['items'] as $item) {
                    $stmt = $pdo->prepare("INSERT INTO bill_items (bill_id, item_type, item_id, item_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $billId,
                        $item['item_type'],
                        $item['item_id'] ?? null,
                        $item['item_name'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['total_price']
                    ]);
                }
                
                // Update free visits
                if (isset($data['free_visits_granted']) && $data['free_visits_granted'] > 0) {
                    $stmt = $pdo->prepare("UPDATE customer_visits SET total_free_visits = total_free_visits + ? WHERE customer_id = ?");
                    $stmt->execute([$data['free_visits_granted'], $data['customer_id']]);
                }
                
                $pdo->commit();
                jsonResponse(true, 'Bill created successfully', ['bill_id' => $billId, 'invoice_number' => $invoiceNumber]);
            } catch (Exception $e) {
                $pdo->rollBack();
                jsonResponse(false, 'Failed to create bill: ' . $e->getMessage());
            }
            break;
            
        case 'get_bill_details':
            $billId = $_GET['bill_id'];
            
            $stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, c.mobile, c.city 
                                  FROM bills b 
                                  JOIN customers c ON b.customer_id = c.id 
                                  WHERE b.id = ?");
            $stmt->execute([$billId]);
            $bill = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
            $stmt->execute([$billId]);
            $items = $stmt->fetchAll();
            
            jsonResponse(true, 'Bill details', ['bill' => $bill, 'items' => $items]);
            break;
            
        case 'update_bill_payment':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $paidAmount = $data['paid_amount'];
            $advanceAmount = $data['advance_amount'];
            $totalAmount = $data['total_amount'];
            $remaining = $totalAmount - $paidAmount - $advanceAmount;
            
            $paymentStatus = 'unpaid';
            if ($paidAmount + $advanceAmount >= $totalAmount) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount + $advanceAmount > 0) {
                $paymentStatus = 'partial';
            }
            
            $stmt = $pdo->prepare("UPDATE bills SET paid_amount = ?, advance_amount = ?, remaining_amount = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$paidAmount, $advanceAmount, $remaining, $paymentStatus, $data['bill_id']]);
            
            jsonResponse(true, 'Payment updated');
            break;
            
        case 'get_all_bills':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $stmt = $pdo->query("SELECT b.*, c.name as customer_name, c.mobile 
                                FROM bills b 
                                JOIN customers c ON b.customer_id = c.id 
                                ORDER BY b.created_at DESC");
            jsonResponse(true, 'Bills fetched', $stmt->fetchAll());
            break;
            
        case 'delete_bill':
            if (!isAdmin()) jsonResponse(false, 'Unauthorized');
            
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM bills WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(true, 'Bill deleted');
            break;
            
        case 'logout':
            session_destroy();
            jsonResponse(true, 'Logged out');
            break;
            
        default:
            jsonResponse(false, 'Invalid action');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>