<?php
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?><?php
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <h2>Ragamaguru</h2>
        </div>
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="customers.php">Customers</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="availability.php">Availability</a></li>
            <li><a href="bills.php">Bills</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li class="user-menu">
                <span>👤 <?php echo $_SESSION['admin_name']; ?></span>
                <div class="dropdown">
                    <a href="logout.php">Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>