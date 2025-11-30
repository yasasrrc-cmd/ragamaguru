<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Handle expense actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['category'],
                $_POST['amount'],
                $_POST['description'],
                $_POST['expense_date']
            ]);
            $success = "Expense added successfully";
        } elseif ($_POST['action'] === 'delete' && $_SESSION['role'] === 'admin') {
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $success = "Expense deleted successfully";
        }
    }
}

// Get date filter
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get expenses
$stmt = $pdo->prepare("
    SELECT e.*, u.full_name 
    FROM expenses e 
    JOIN users u ON e.user_id = u.id 
    WHERE DATE(e.expense_date) BETWEEN ? AND ? 
    ORDER BY e.expense_date DESC, e.created_at DESC
");
$stmt->execute([$date_from, $date_to]);
$expenses = $stmt->fetchAll();

// Calculate totals by category
$stmt = $pdo->prepare("
    SELECT category, SUM(amount) as total 
    FROM expenses 
    WHERE DATE(expense_date) BETWEEN ? AND ? 
    GROUP BY category 
    ORDER BY total DESC
");
$stmt->execute([$date_from, $date_to]);
$category_totals = $stmt->fetchAll();

$total_expenses = array_sum(array_column($expenses, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - POS System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Expense Management</h1>
                <button onclick="openExpenseModal()" class="btn btn-primary">+ Add Expense</button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <!-- Summary Cards -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dc3545;">💸</div>
                    <div class="stat-content">
                        <h3>Rs <?= number_format($total_expenses, 2) ?></h3>
                        <p>Total Expenses</p>
                    </div>
                </div>
                
                <?php foreach (array_slice($category_totals, 0, 3) as $cat): ?>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #6c757d;">📋</div>
                    <div class="stat-content">
                        <h3>Rs <?= number_format($cat['total'], 2) ?></h3>
                        <p><?= htmlspecialchars($cat['category']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <form method="GET" class="search-form">
                        <input type="date" name="date_from" value="<?= $date_from ?>" required>
                        <span style="padding: 0 10px;">to</span>
                        <input type="date" name="date_to" value="<?= $date_to ?>" required>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="expenses.php" class="btn">Reset</a>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No expenses found for the selected period</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($expense['expense_date'])) ?></td>
                                    <td><span class="badge"><?= htmlspecialchars($expense['category']) ?></span></td>
                                    <td><strong style="color: #dc3545;">Rs <?= number_format($expense['amount'], 2) ?></strong></td>
                                    <td><?= htmlspecialchars($expense['description'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($expense['full_name']) ?></td>
                                    <td>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <button onclick="deleteExpense(<?= $expense['id'] ?>)" class="btn btn-sm btn-danger">Delete</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fa; font-weight: bold;">
                                <td colspan="2" style="text-align: right;">TOTAL:</td>
                                <td style="color: #dc3545;">Rs <?= number_format($total_expenses, 2) ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Expense Modal -->
    <div id="expense-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Expense</h2>
                <span class="close" onclick="closeExpenseModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <option value="Rent">Rent</option>
                            <option value="Utilities">Utilities (Electricity, Water)</option>
                            <option value="Salaries">Salaries & Wages</option>
                            <option value="Supplies">Office Supplies</option>
                            <option value="Maintenance">Maintenance & Repairs</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Marketing">Marketing & Advertising</option>
                            <option value="Inventory">Inventory Purchase</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Insurance">Insurance</option>
                            <option value="Taxes">Taxes & Fees</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount (Rs) *</label>
                        <input type="number" name="amount" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Details about this expense..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeExpenseModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        function openExpenseModal() {
            document.getElementById('expense-modal').style.display = 'flex';
        }
        
        function closeExpenseModal() {
            document.getElementById('expense-modal').style.display = 'none';
        }
        
        function deleteExpense(id) {
            if (!confirm('Are you sure you want to delete this expense?')) {
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
            const modal = document.getElementById('expense-modal');
            if (event.target === modal) {
                closeExpenseModal();
            }
        }
    </script>
</body>
</html>