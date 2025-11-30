<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Wasupa2202#');
define('DB_NAME', 'appointment_system');

// SMS API Configuration
define('SMS_API_URL', 'https://portal.richmo.lk/api/sms/send/');
define('SMS_API_TOKEN', '2998679128001b2e398204ed938608846775b8ec');
define('SMS_FROM', 'RagamaGuru');

// Application Settings
define('SITE_NAME', 'Medical Appointment System');
define('TIMEZONE', 'Asia/Colombo');
date_default_timezone_set(TIMEZONE);

// Upload Directory
define('UPLOAD_DIR', 'uploads/payment_slips/');
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper Functions
function sendSMS($mobile, $message) {
    $url = SMS_API_URL;
    $params = [
        'dst' => $mobile,
        'from' => SMS_FROM,
        'msg' => urlencode($message)
    ];
    
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . SMS_API_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

function generateOTP() {
    return sprintf("%06d", mt_rand(1, 999999));
}

function sendOTPSMS($mobile, $otp, $purpose = 'verification') {
    $message = "Your OTP for $purpose is: $otp. Valid for 10 minutes.";
    return sendSMS($mobile, $message);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
}

function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

function formatDateTime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}
?>