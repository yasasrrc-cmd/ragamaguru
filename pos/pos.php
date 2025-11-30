
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Check if register is open
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$open_register = $stmt->fetch();

if (!$open_register) {
    header('Location: register.php');
    exit;
}

// Handle sale processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_sale') {
    header('Content-Type: application/json');
    
    try {
        $cart = json_decode($_POST['cart'], true);
        $payment_method = $_POST['payment_method'];
        $amount_paid = floatval($_POST['amount_paid']);
        
        if (empty($cart)) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit;
        }
        
        // Calculate total
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        if ($amount_paid < $total) {
            echo json_encode(['success' => false, 'message' => 'Insufficient payment amount']);
            exit;
        }
        
        $change = $amount_paid - $total;
        
        // Generate invoice number
        $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert sale
        $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, user_id, total, payment_method, amount_paid, change_amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_no, $_SESSION['user_id'], $total, $payment_method, $amount_paid, $change]);
        $sale_id = $pdo->lastInsertId();
        
        // Insert sale items and update stock
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $update_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        
        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $subtotal]);
            $update_stock->execute([$item['quantity'], $item['id']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Sale completed successfully',
            'invoice_no' => $invoice_no,
            'sale_id' => $sale_id,
            'total' => $total,
            'paid' => $amount_paid,
            'change' => $change
        ]);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error processing sale: ' . $e->getMessage()]);
        exit;
    }
}

// Search products
if (isset($_GET['search'])) {
    header('Content-Type: application/json');
    $search = '%' . $_GET['search'] . '%';
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.barcode LIKE ? OR p.name LIKE ? LIMIT 20");
    $stmt->execute([$search, $search]);
    echo json_encode($stmt->fetchAll());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="pos-container">
                <div class="pos-products">
                    <div class="pos-search">
                        <input type="text" id="product-search" placeholder="🔍 Search by name or barcode..." autofocus>
                    </div>
                    
                    <div id="product-list" class="product-grid">
                        <p class="text-muted">Start typing to search products...</p>
                    </div>
                </div>
                
                <div class="pos-cart">
                    <div class="cart-header">
                        <h2>Shopping Cart</h2>
                        <button id="clear-cart" class="btn btn-sm btn-danger">Clear</button>
                    </div>
                    
                    <div id="cart-items" class="cart-items">
                        <div class="empty-cart">
                            <p>🛒 Cart is empty</p>
                        </div>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="cart-subtotal">$0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="cart-total">$0.00</span>
                        </div>
                    </div>
                    
                    <div class="cart-payment">
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select id="payment-method" class="form-control">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Amount Paid</label>
                            <input type="number" id="amount-paid" class="form-control" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Change</label>
                            <input type="text" id="change-amount" class="form-control" readonly>
                        </div>
                        
                        <button id="process-sale" class="btn btn-primary btn-block btn-lg">Complete Sale</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Sale Success Modal -->
    <div id="success-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header success">
                <h2>✓ Sale Completed</h2>
            </div>
            <div class="modal-body">
                <div class="success-details">
                    <p><strong>Invoice #:</strong> <span id="modal-invoice"></span></p>
                    <p><strong>Total:</strong> $<span id="modal-total"></span></p>
                    <p><strong>Paid:</strong> $<span id="modal-paid"></span></p>
                    <p><strong>Change:</strong> $<span id="modal-change"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="printInvoice()">Print Invoice</button>
                <button class="btn" onclick="closeModal()">New Sale</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/pos.js"></script>
</body>
</html>