<header class="admin-header">
    <div class="admin-header-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="admin-logo">💅 Salon Admin</div>
    </div>
    <div class="admin-header-right">
        <div class="admin-user">
            <div class="admin-user-avatar">
                <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
            </div>
            <span><?php echo $_SESSION['admin_name']; ?></span>
        </div>
        <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
    </div>
</header>

<script>
function toggleSidebar() {
    document.querySelector('.admin-sidebar').classList.toggle('active');
}
</script>