<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : sidebar.php
Module  : Super Admin Sidebar
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/

$superAdminMenus = [
    [
        "key" => "dashboard",
        "label" => "Dashboard",
        "icon" => "fa-solid fa-house",
        "href" => "index.php"
    ],
    [
        "key" => "users",
        "label" => "Users",
        "icon" => "fa-solid fa-users",
        "href" => "users.php"
    ],
    [
        "key" => "websites",
        "label" => "Websites",
        "icon" => "fa-solid fa-globe",
        "href" => "websites.php"
    ],
    [
        "key" => "widgets",
        "label" => "Widgets",
        "icon" => "fa-solid fa-puzzle-piece",
        "href" => "widgets.php"
    ],
    [
        "key" => "chats",
        "label" => "Chats",
        "icon" => "fa-regular fa-comments",
        "href" => "chats.php"
    ],
    [
        "key" => "analytics",
        "label" => "Analytics",
        "icon" => "fa-solid fa-chart-line",
        "href" => "#"
    ],
    [
        "key" => "audit",
        "label" => "Audit Logs",
        "icon" => "fa-solid fa-shield-halved",
        "href" => "audit-logs.php"
    ],
    [
        "key" => "settings",
        "label" => "Settings",
        "icon" => "fa-solid fa-gear",
        "href" => "#"
    ]
];
?>

<aside class="xd-sa-sidebar" id="xdSuperAdminSidebar">

    <div>

        <div class="xd-sa-logo">

            <div class="xd-sa-logo-mark">
                <i class="fa-regular fa-comments"></i>
            </div>

            <div>
                <strong>XD Chat</strong>
                <small>Super Admin</small>
            </div>

        </div>

        <div class="xd-sa-sidebar-label">
            Platform Menu
        </div>

        <nav class="xd-sa-nav">

            <?php foreach ($superAdminMenus as $menu) { ?>

                <a href="<?php echo htmlspecialchars($menu["href"]); ?>"
                   class="<?php echo $active_menu === $menu["key"] ? "active" : ""; ?>"
                   <?php if ($menu["href"] === "#") { echo 'aria-disabled="true"'; } ?>>
                    <i class="<?php echo htmlspecialchars($menu["icon"]); ?>"></i>
                    <span><?php echo htmlspecialchars($menu["label"]); ?></span>
                </a>

            <?php } ?>

        </nav>

    </div>

    <div class="xd-sa-sidebar-bottom">

        <div class="xd-sa-profile-card">

            <div class="xd-sa-avatar">
                <?php echo htmlspecialchars(strtoupper(substr($_SESSION["user_name"], 0, 1))); ?>
            </div>

            <div>
                <strong><?php echo htmlspecialchars($_SESSION["user_name"]); ?></strong>
                <small>Super Admin</small>
            </div>

        </div>

        <a href="../auth/logout.php"
           class="xd-sa-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </div>

</aside>
