<aside class="sidebar">
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="register.php">
                <span class="icon">🏦</span>
                <span>Cash Register</span>
            </a>
        </li>
        <li>
            <a href="pos.php">
                <span class="icon">🛒</span>
                <span>Point of Sale</span>
            </a>
        </li>
        <li>
            <a href="expenses.php">
                <span class="icon">💸</span>
                <span>Expenses</span>
            </a>
        </li>
        <li>
            <a href="sales.php">
                <span class="icon">💰</span>
                <span>Sales History</span>
            </a>
        </li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li>
            <a href="products.php">
                <span class="icon">📦</span>
                <span>Products</span>
            </a>
        </li>
        <li>
            <a href="categories.php">
                <span class="icon">🏷️</span>
                <span>Categories</span>
            </a>
        </li>
        <li>
            <a href="users.php">
                <span class="icon">👥</span>
                <span>Users</span>
            </a>
        </li>
        <li>
            <a href="reports.php">
                <span class="icon">📈</span>
                <span>Reports</span>
            </a>
        </li>
        <li>
            <a href="settings.php">
                <span class="icon">⚙️</span>
                <span>Settings</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>