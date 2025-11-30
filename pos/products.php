<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (barcode, name, category_id, price, cost, stock, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['barcode'],
                $_POST['name'],
                $_POST['category_id'] ?: null,
                $_POST['price'],
                $_POST['cost'],
                $_POST['stock'],
                $_POST['min_stock']
            ]);
            $success = "Product added successfully";
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE products SET barcode=?, name=?, category_id=?, price=?, cost=?, stock=?, min_stock=? WHERE id=?");
            $stmt->execute([
                $_POST['barcode'],
                $_POST['name'],
                $_POST['category_id'] ?: null,
                $_POST['price'],
                $_POST['cost'],
                $_POST['stock'],
                $_POST['min_stock'],
                $_POST['id']
            ]);
            $success = "Product updated successfully";
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$_POST['id']]);
            $success = "Product deleted successfully";
        }
    }
}

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get products
$search = $_GET['search'] ?? '';
$where = $search ? "WHERE p.name LIKE ? OR p.barcode LIKE ?" : "";
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.name");
if ($search) {
    $search_param = "%$search%";
    $stmt->execute([$search_param, $search_param]);
} else {
    $stmt->execute();
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Products</h1>
                <button onclick="openModal('add')" class="btn btn-primary">+ Add Product</button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn">Search</button>
                        <?php if ($search): ?>
                            <a href="products.php" class="btn">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Cost</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['barcode']) ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                <td>Rs <?= number_format($product['cost'], 2) ?></td>
                                <td>Rs <?= number_format($product['price'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $product['stock'] <= $product['min_stock'] ? 'badge-danger' : 'badge-success' ?>">
                                        <?= $product['stock'] ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick='editProduct(<?= json_encode($product) ?>)' class="btn btn-sm">Edit</button>
                                    <button onclick="deleteProduct(<?= $product['id'] ?>)" class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Product Modal -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Add Product</h2>
                <span class="close" onclick="closeProductModal()">&times;</span>
            </div>
            <form method="POST" id="product-form">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="id" id="product-id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Barcode *</label>
                        <input type="text" name="barcode" id="barcode" required>
                    </div>
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" id="category_id">
                            <option value="">None</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cost Price *</label>
                            <input type="number" name="cost" id="cost" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Selling Price *</label>
                            <input type="number" name="price" id="price" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stock Quantity *</label>
                            <input type="number" name="stock" id="stock" required>
                        </div>
                        <div class="form-group">
                            <label>Min Stock Alert *</label>
                            <input type="number" name="min_stock" id="min_stock" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/products.js"></script>
</body>
</html>