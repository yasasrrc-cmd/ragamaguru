<?php
// config.php - Database Configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Wasupa2202#');
define('DB_NAME', 'appointment_platform');

// Richmo SMS API Configuration
define('SMS_API_URL', 'https://portal.richmo.lk/api/sms/send/');
define('SMS_API_TOKEN', '2998679128001b2e398204ed938608846775b8ec');
define('SMS_FROM', 'RagamaGuru');

// Database Connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Send OTP via Richmo API
function sendOTP($mobile, $otp) {
    $message = urlencode("Your OTP code is: $otp. Valid for 10 minutes.");
    $url = SMS_API_URL . "?dst=94" . ltrim($mobile, '0') . "&from=" . SMS_FROM . "&msg=" . $message;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . SMS_API_TOKEN
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Generate and store OTP
function generateOTP($mobile, $purpose = 'login') {
    $conn = getDBConnection();
    $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Delete old OTPs
    $stmt = $conn->prepare("DELETE FROM otp_codes WHERE mobile = ? AND purpose = ?");
    $stmt->bind_param("ss", $mobile, $purpose);
    $stmt->execute();
    
    // Insert new OTP
    $stmt = $conn->prepare("INSERT INTO otp_codes (mobile, otp_code, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $mobile, $otp, $purpose, $expires);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    // Send OTP
    sendOTP($mobile, $otp);
    
    return $otp;
}

// Verify OTP
function verifyOTP($mobile, $otp, $purpose = 'login') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE mobile = ? AND otp_code = ? AND purpose = ? AND expires_at > NOW() AND is_used = 0");
    $stmt->bind_param("sss", $mobile, $otp, $purpose);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Mark OTP as used
        $stmt = $conn->prepare("UPDATE otp_codes SET is_used = 1 WHERE mobile = ? AND otp_code = ?");
        $stmt->bind_param("ss", $mobile, $otp);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return true;
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

session_start();
?>