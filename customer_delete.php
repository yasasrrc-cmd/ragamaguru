<?php
require_once 'config.php';
requireLogin();

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id) {
    // Check if customer has appointments
    $check = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE customer_id = $customer_id");
    $has_appointments = $check->fetch_assoc()['count'] > 0;
    
    if ($has_appointments) {
        $_SESSION['error'] = "Cannot delete customer with existing appointments!";
    } else {
        if ($conn->query("DELETE FROM customers WHERE id = $customer_id")) {
            $_SESSION['success'] = "Customer deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting customer: " . $conn->error;
        }
    }
}

header("Location: customers.php");
exit();
?>