<header class="header">
    <div class="header-left">
        <h1>🏪 POS System</h1>
    </div>
    <div class="header-right">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?>
            </div>
            <div>
                <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                <div style="font-size: 12px; color: #666;">
                    <?= ucfirst($_SESSION['role']) ?>
                </div>
            </div>
        </div>
        <button onclick="confirmLogout()" class="btn btn-danger">Logout</button>
    </div>
</header>