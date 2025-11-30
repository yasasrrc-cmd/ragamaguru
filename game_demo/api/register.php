<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields required']);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "INSERT INTO users (username, email, password, balance) VALUES (:username, :email, :password, 100.00)";
    $stmt = $conn->prepare($query);
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    
    try {
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'User already exists']);
    }
}
?>