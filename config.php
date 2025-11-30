<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Wasupa2202#');
define('DB_NAME', 'ragamaguru_appointments');

// SMS API Configuration
define('SMS_API_URL', 'https://portal.richmo.lk/api/v1/sms/send/');
define('SMS_API_KEY', '2998679128001b2e398204ed938608846775b8ec'); // Replace with your actual API token
define('SMS_FROM_MASK', 'RagamaGuru'); // Replace with your mask

// Timezone
date_default_timezone_set('Asia/Colombo');

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if admin is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Helper function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Helper function to send SMS
function sendSMS($mobile, $message) {
    $url = SMS_API_URL;
    $params = http_build_query([
        'dst' => $mobile,
        'from' => SMS_FROM_MASK,
        'msg' => $message,
        'key' => SMS_API_KEY
    ]);
    
    $fullUrl = $url . '?' . $params;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return  $response;
}

// Helper function to format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Helper function to format time
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// Helper function to sanitize input
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>