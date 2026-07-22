<?php
if (!function_exists("getPlatformName")) {
    require_once dirname(__DIR__, 2) . "/includes/functions/platform-settings.php";
}

$dashboardPlatformName = isset($pdo) && $pdo instanceof PDO
    ? getPlatformName($pdo)
    : "XD Chat";

$dashboardPlatformTagline = isset($pdo) && $pdo instanceof PDO
    ? getPlatformTagline($pdo)
    : "Live Chat Platform";

$dashboardPlatformLogoUrl = isset($pdo) && $pdo instanceof PDO
    ? getPlatformLogoUrl($pdo)
    : "";

$dashboardSidebarUserName = (string) ($_SESSION["user_name"] ?? "User");

$dashboardSidebarInitial = function_exists("mb_substr")
    ? mb_substr($dashboardSidebarUserName, 0, 1, "UTF-8")
    : substr($dashboardSidebarUserName, 0, 1);

$dashboardCurrentPage = basename((string) ($_SERVER["SCRIPT_NAME"] ?? ""));

$dashboardPageMenuMap = [
    "index.php" => "dashboard",
    "chats.php" => "chats",
    "websites.php" => "websites",
    "website-add.php" => "websites",
    "website-edit.php" => "websites",
    "website-view.php" => "websites",
    "widgets.php" => "widgets",
    "widget-add.php" => "widgets",
    "widget-edit.php" => "widgets",
    "visitors.php" => "visitors",
    "analytics.php" => "analytics",
    "settings.php" => "settings",
    "profile.php" => "settings"
];

$dashboardActiveMenu = $dashboardPageMenuMap[$dashboardCurrentPage] ?? "";

$dashboardMenus = [
    [
        "key" => "dashboard",
        "label" => "Dashboard",
        "icon" => "fa-solid fa-house",
        "href" => "index.php"
    ],
    [
        "key" => "chats",
        "label" => "Live Chats",
        "icon" => "fa-regular fa-comments",
        "href" => "chats.php"
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
        "key" => "visitors",
        "label" => "Visitors",
        "icon" => "fa-solid fa-users",
        "href" => "visitors.php"
    ],
    [
        "key" => "analytics",
        "label" => "Analytics",
        "icon" => "fa-solid fa-chart-line",
        "href" => "analytics.php"
    ],
    [
        "key" => "settings",
        "label" => "Settings",
        "icon" => "fa-solid fa-gear",
        "href" => "settings.php"
    ]
];
?>

<aside class="xd-dashboard-sidebar"
       id="xdDashboardSidebar"
       aria-label="Dashboard navigation">

    <div>

        <div class="xd-dashboard-logo">

            <div class="xd-logo-mark">
                <?php if ($dashboardPlatformLogoUrl !== "") { ?>
                    <img src="<?php echo htmlspecialchars($dashboardPlatformLogoUrl); ?>"
                         alt="<?php echo htmlspecialchars($dashboardPlatformName); ?> logo">
                <?php } else { ?>
                    <i class="fa-regular fa-comments"></i>
                <?php } ?>
            </div>

            <div>
                <?php echo htmlspecialchars($dashboardPlatformName); ?>
                <small><?php echo htmlspecialchars($dashboardPlatformTagline); ?></small>
            </div>

            <button type="button"
                    class="xd-sidebar-close"
                    id="xdDashboardSidebarClose"
                    aria-label="Close navigation">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>

        </div>

        <div class="xd-sidebar-label">
            Main Menu
        </div>

        <nav class="xd-dashboard-nav">

            <?php foreach ($dashboardMenus as $dashboardMenu) { ?>

                <?php $isDashboardMenuActive = $dashboardActiveMenu === $dashboardMenu["key"]; ?>

                <a href="<?php echo htmlspecialchars($dashboardMenu["href"], ENT_QUOTES, "UTF-8"); ?>"
                   class="<?php echo $isDashboardMenuActive ? "active" : ""; ?>"
                   <?php if ($isDashboardMenuActive) { ?>aria-current="page"<?php } ?>>
                    <i class="<?php echo htmlspecialchars($dashboardMenu["icon"], ENT_QUOTES, "UTF-8"); ?>"
                       aria-hidden="true"></i>
                    <?php echo htmlspecialchars($dashboardMenu["label"], ENT_QUOTES, "UTF-8"); ?>
                </a>

            <?php } ?>

        </nav>

    </div>

    <div class="xd-sidebar-bottom">

        <div class="xd-sidebar-profile">

            <div class="xd-sidebar-avatar">
                <?php echo htmlspecialchars(strtoupper($dashboardSidebarInitial), ENT_QUOTES, "UTF-8"); ?>
            </div>

            <div>
                <strong><?php echo htmlspecialchars($dashboardSidebarUserName, ENT_QUOTES, "UTF-8"); ?></strong>
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

<button type="button"
        class="xd-sidebar-backdrop"
        id="xdDashboardSidebarBackdrop"
        aria-label="Close navigation"
        tabindex="-1"></button>
