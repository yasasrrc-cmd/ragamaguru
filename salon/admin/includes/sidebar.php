<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="appointments.php" class="<?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📅</span>
                <span>Appointments</span>
            </a>
        </li>
        <li>
            <a href="customers.php" class="<?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                <span class="menu-icon">👥</span>
                <span>Customers</span>
            </a>
        </li>
        <li>
            <a href="employees.php" class="<?php echo $current_page == 'employees.php' ? 'active' : ''; ?>">
                <span class="menu-icon">👨‍💼</span>
                <span>Employees</span>
            </a>
        </li>
        <li>
            <a href="services.php" class="<?php echo $current_page == 'services.php' ? 'active' : ''; ?>">
                <span class="menu-icon">💆</span>
                <span>Services</span>
            </a>
        </li>
        <li>
            <a href="billing.php" class="<?php echo $current_page == 'billing.php' ? 'active' : ''; ?>">
                <span class="menu-icon">💳</span>
                <span>Billing</span>
            </a>
        </li>
        <li>
            <a href="invoices.php" class="<?php echo $current_page == 'invoices.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📄</span>
                <span>Invoices</span>
            </a>
        </li>
        <li>
            <a href="availability.php" class="<?php echo $current_page == 'availability.php' ? 'active' : ''; ?>">
                <span class="menu-icon">⏰</span>
                <span>Availability</span>
            </a>
        </li>
        <li>
            <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📈</span>
                <span>Reports</span>
            </a>
        </li>
        <li>
            <a href="employee_reports.php" class="<?php echo $current_page == 'employee_reports.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📊</span>
                <span>Employee Reports</span>
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <span class="menu-icon">⚙️</span>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</aside>