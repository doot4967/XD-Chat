<?php
$dashboardHeaderUserName = (string) ($_SESSION["user_name"] ?? "User");

$dashboardHeaderInitial = function_exists("mb_substr")
    ? mb_substr($dashboardHeaderUserName, 0, 1, "UTF-8")
    : substr($dashboardHeaderUserName, 0, 1);
?>

<script>
document.body.classList.add("xd-sidebar-enhanced");
</script>

<header class="xd-dashboard-header">

    <div class="xd-header-left">

        <div class="xd-mobile-menu-toolbar">

            <button type="button"
                    class="xd-sidebar-toggle"
                    id="xdDashboardSidebarToggle"
                    aria-label="Open navigation"
                    aria-controls="xdDashboardSidebar"
                    aria-expanded="false">

                <i class="fa-solid fa-bars" aria-hidden="true"></i>

                <span class="xd-sidebar-toggle-label">Menu</span>

            </button>

        </div>

        <div class="xd-header-heading">

            <h1>

                <?php echo htmlspecialchars((string) $page_heading, ENT_QUOTES, "UTF-8"); ?>

            </h1>

            <p>

                <?php echo htmlspecialchars((string) $page_description, ENT_QUOTES, "UTF-8"); ?>

            </p>

        </div>

    </div>

    <div class="xd-header-right">

        <div class="xd-header-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                placeholder="Search...">

        </div>

        <button class="xd-header-icon"
                type="button"
                aria-label="Notifications">

            <i class="fa-regular fa-bell"></i>

            <span>3</span>

        </button>

        <div class="xd-header-profile">

            <div class="xd-profile-avatar">

                <?php echo htmlspecialchars(strtoupper($dashboardHeaderInitial), ENT_QUOTES, "UTF-8"); ?>

            </div>

            <div class="xd-header-profile-details">

                <strong>

                    <?php echo htmlspecialchars($dashboardHeaderUserName, ENT_QUOTES, "UTF-8"); ?>

                </strong>

                <small>

                    Administrator

                </small>

            </div>

            <span class="xd-mobile-account-identity">
                Administrator &ndash;
                <?php echo htmlspecialchars($dashboardHeaderUserName, ENT_QUOTES, "UTF-8"); ?>
            </span>

        </div>

    </div>

</header>

<script src="../assets/js/04-dashboard-sidebar.js"></script>
