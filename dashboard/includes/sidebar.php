<aside class="xd-dashboard-sidebar">

    <div>

        <div class="xd-dashboard-logo">

            <div class="xd-logo-mark">
                <i class="fa-regular fa-comments"></i>
            </div>

            <div>
                XD Chat
                <small>Live Chat Platform</small>
            </div>

        </div>

        <div class="xd-sidebar-label">
            Main Menu
        </div>

        <nav class="xd-dashboard-nav">

            <a href="index.php">
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>

            <a href="chats.php">
                <i class="fa-regular fa-comments"></i>
                Live Chats
            </a>

            <a href="websites.php">
                <i class="fa-solid fa-globe"></i>
                Websites
            </a>

            <a href="widgets.php">
                <i class="fa-solid fa-puzzle-piece"></i>
                Widgets
            </a>

            <a href="visitors.php">
                <i class="fa-solid fa-users"></i>
                Visitors
            </a>

            <a href="analytics.php">
                <i class="fa-solid fa-chart-line"></i>
                Analytics
            </a>

            <a href="settings.php">
                <i class="fa-solid fa-gear"></i>
                Settings
            </a>

        </nav>

    </div>

    <div class="xd-sidebar-bottom">

        <div class="xd-sidebar-profile">

            <div class="xd-sidebar-avatar">
                <?php echo strtoupper(substr($_SESSION["user_name"], 0, 1)); ?>
            </div>

            <div>
                <strong><?php echo $_SESSION["user_name"]; ?></strong>
                <small>Administrator</small>
            </div>

        </div>

        <a href="../auth/logout.php"
           class="xd-sidebar-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Logout
        </a>

    </div>

</aside>
