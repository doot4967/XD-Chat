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

if (!function_exists("getPlatformName")) {
    require_once dirname(__DIR__, 2) . "/includes/functions/platform-settings.php";
}

$superAdminPlatformName = isset($pdo) && $pdo instanceof PDO
    ? getPlatformName($pdo)
    : "XD Chat";

$superAdminPlatformTagline = isset($pdo) && $pdo instanceof PDO
    ? getPlatformTagline($pdo)
    : "Live Chat Platform";

$superAdminPlatformLogoUrl = isset($pdo) && $pdo instanceof PDO
    ? getPlatformLogoUrl($pdo)
    : "";

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
        "href" => "analytics.php"
    ],
    [
        "key" => "audit",
        "label" => "Audit Logs",
        "icon" => "fa-solid fa-shield-halved",
        "href" => "audit-logs.php"
    ],
    [
        "key" => "account",
        "label" => "My Account",
        "icon" => "fa-solid fa-gear",
        "href" => "settings.php"
    ],
    [
        "key" => "platform_settings",
        "label" => "Platform Settings",
        "icon" => "fa-solid fa-sliders",
        "href" => "platform-settings.php"
    ]
];
?>

<aside class="xd-sa-sidebar" id="xdSuperAdminSidebar">

    <div>

        <div class="xd-sa-logo">

            <div class="xd-sa-logo-mark">
                <?php if ($superAdminPlatformLogoUrl !== "") { ?>
                    <img src="<?php echo htmlspecialchars($superAdminPlatformLogoUrl); ?>"
                         alt="<?php echo htmlspecialchars($superAdminPlatformName); ?> logo">
                <?php } else { ?>
                    <i class="fa-regular fa-comments"></i>
                <?php } ?>
            </div>

            <div>
                <strong><?php echo htmlspecialchars($superAdminPlatformName); ?></strong>
                <small><?php echo htmlspecialchars($superAdminPlatformTagline); ?></small>
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
