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
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['username'], $hashed_password, $_POST['full_name'], $_POST['role']]);
            $success = "User added successfully";
        } elseif ($_POST['action'] === 'edit') {
            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, full_name=?, role=? WHERE id=?");
                $stmt->execute([$_POST['username'], $hashed_password, $_POST['full_name'], $_POST['role'], $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE id=?");
                $stmt->execute([$_POST['username'], $_POST['full_name'], $_POST['role'], $_POST['id']]);
            }
            $success = "User updated successfully";
        } elseif ($_POST['action'] === 'delete') {
            if ($_POST['id'] != $_SESSION['user_id']) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
                $stmt->execute([$_POST['id']]);
                $success = "User deleted successfully";
            } else {
                $error = "Cannot delete your own account";
            }
        }
    }
}

// Get users
$stmt = $pdo->query("SELECT * FROM users ORDER BY full_name");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>User Management</h1>
                <button onclick="openUserModal('add')" class="btn btn-primary">+ Add User</button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : 'badge-success' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <button onclick='editUser(<?= json_encode($user) ?>)' class="btn btn-sm">Edit</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?= $user['id'] ?>)" class="btn btn-sm btn-danger">Delete</button>
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
    
    <!-- User Modal -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Add User</h2>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <form method="POST" id="user-form">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="id" id="user-id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" id="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password <span id="pwd-note">(leave blank to keep current)</span> *</label>
                        <input type="password" name="password" id="password">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="role" required>
                            <option value="cashier">Cashier</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        function openUserModal(action) {
            const modal = document.getElementById('user-modal');
            const form = document.getElementById('user-form');
            const title = document.getElementById('modal-title');
            const pwdNote = document.getElementById('pwd-note');
            const password = document.getElementById('password');
            
            form.reset();
            document.getElementById('form-action').value = action;
            
            if (action === 'add') {
                title.textContent = 'Add User';
                document.getElementById('user-id').value = '';
                pwdNote.style.display = 'none';
                password.required = true;
            }
            
            modal.style.display = 'flex';
        }

        function editUser(user) {
            const modal = document.getElementById('user-modal');
            const title = document.getElementById('modal-title');
            const pwdNote = document.getElementById('pwd-note');
            const password = document.getElementById('password');
            
            title.textContent = 'Edit User';
            document.getElementById('form-action').value = 'edit';
            document.getElementById('user-id').value = user.id;
            document.getElementById('full_name').value = user.full_name;
            document.getElementById('username').value = user.username;
            document.getElementById('role').value = user.role;
            password.value = '';
            password.required = false;
            pwdNote.style.display = 'inline';
            
            modal.style.display = 'flex';
        }

        function closeUserModal() {
            document.getElementById('user-modal').style.display = 'none';
        }

        function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user?')) {
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
            const modal = document.getElementById('user-modal');
            if (event.target === modal) {
                closeUserModal();
            }
        }
    </script>
</body>
</html>