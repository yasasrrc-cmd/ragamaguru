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
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description']]);
            $success = "Category added successfully";
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['id']]);
            $success = "Category updated successfully";
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$_POST['id']]);
            $success = "Category deleted successfully";
        }
    }
}

// Get categories with product count
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Product Categories</h1>
                <button onclick="openCategoryModal('add')" class="btn btn-primary">+ Add Category</button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                                <td><?= htmlspecialchars($category['description'] ?? 'N/A') ?></td>
                                <td><span class="badge badge-success"><?= $category['product_count'] ?></span></td>
                                <td><?= date('M d, Y', strtotime($category['created_at'])) ?></td>
                                <td>
                                    <button onclick='editCategory(<?= json_encode($category) ?>)' class="btn btn-sm">Edit</button>
                                    <?php if ($category['product_count'] == 0): ?>
                                    <button onclick="deleteCategory(<?= $category['id'] ?>)" class="btn btn-sm btn-danger">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Category Modal -->
    <div id="category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Add Category</h2>
                <span class="close" onclick="closeCategoryModal()">&times;</span>
            </div>
            <form method="POST" id="category-form">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="id" id="category-id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        function openCategoryModal(action) {
            const modal = document.getElementById('category-modal');
            const form = document.getElementById('category-form');
            const title = document.getElementById('modal-title');
            
            form.reset();
            document.getElementById('form-action').value = action;
            
            if (action === 'add') {
                title.textContent = 'Add Category';
                document.getElementById('category-id').value = '';
            }
            
            modal.style.display = 'flex';
        }

        function editCategory(category) {
            const modal = document.getElementById('category-modal');
            const title = document.getElementById('modal-title');
            
            title.textContent = 'Edit Category';
            document.getElementById('form-action').value = 'edit';
            document.getElementById('category-id').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('description').value = category.description || '';
            
            modal.style.display = 'flex';
        }

        function closeCategoryModal() {
            document.getElementById('category-modal').style.display = 'none';
        }

        function deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category?')) {
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('category-modal');
            if (event.target === modal) {
                closeCategoryModal();
            }
        }
    </script>
</body>
</html>