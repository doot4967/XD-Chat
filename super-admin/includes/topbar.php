<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : topbar.php
Module  : Super Admin Topbar
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/
?>

<header class="xd-sa-topbar">

    <div class="xd-sa-topbar-left">

        <button class="xd-sa-menu-toggle"
                type="button"
                id="xdSuperAdminMenuToggle"
                aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div>
            <h1><?php echo htmlspecialchars($page_heading); ?></h1>
            <p><?php echo htmlspecialchars($page_description); ?></p>
        </div>

    </div>

    <div class="xd-sa-topbar-right">

        <button class="xd-sa-icon-button"
                type="button"
                aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
            <span></span>
        </button>

        <div class="xd-sa-topbar-profile">
            <div class="xd-sa-avatar small">
                <?php echo htmlspecialchars(strtoupper(substr($_SESSION["user_name"], 0, 1))); ?>
            </div>
            <div>
                <strong><?php echo htmlspecialchars($_SESSION["user_name"]); ?></strong>
                <small>Super Admin</small>
            </div>
        </div>

    </div>

</header>
