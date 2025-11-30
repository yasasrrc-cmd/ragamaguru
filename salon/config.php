<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Wasupa2202#');
define('DB_NAME', 'salon_booking');

// Site configuration
define('SITE_NAME', 'Salon Booking System');
define('TIMEZONE', 'Asia/Colombo');
date_default_timezone_set(TIMEZONE);

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper functions
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

function generate_otp() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function send_sms($mobile, $message) {
    global $conn;
    
    // Get SMS settings
    $stmt = $conn->prepare("SELECT api_key, sender_mask, api_url FROM sms_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    
    if (!$settings) {
        return ['success' => false, 'message' => 'SMS settings not configured'];
    }
    
    // Format mobile number (ensure it starts with 94)
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (substr($mobile, 0, 1) === '0') {
        $mobile = '94' . substr($mobile, 1);
    }
    
    // Prepare API URL
    $url = $settings['api_url'] . '?dst=' . $mobile . 
           '&from=' . urlencode($settings['sender_mask']) . 
           '&msg=' . urlencode($message) . 
           '&key=' . $settings['api_key'];
    
    // Send SMS using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        return ['success' => true, 'message' => 'SMS sent successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send SMS'];
    }
}

function check_admin_login() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: admin/login.php');
        exit();
    }
}

function format_currency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

function format_date($date) {
    return date('d M Y', strtotime($date));
}

function format_time($time) {
    return date('h:i A', strtotime($time));
}
?>