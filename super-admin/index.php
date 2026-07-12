<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : index.php
Module  : Super Admin Dashboard
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../includes/functions/session.php';

require_once '../database/connection.php';

require_once '../includes/functions/platform-settings.php';

requireRole([
    "super_admin"
]);


/* ==========================================
   02. DASHBOARD HELPERS
========================================== */

function getSuperAdminCount(PDO $pdo, string $table): string
{

    $allowedTables = [
        "users",
        "websites",
        "widgets",
        "chats",
        "messages"
    ];

    if (!in_array($table, $allowedTables, true)) {

        return "--";

    }

    try {

        $statement = $pdo->prepare("SELECT COUNT(*) FROM " . $table);

        $statement->execute();

        return number_format((int) $statement->fetchColumn());

    } catch (Throwable $exception) {

        return "--";

    }

}


function formatSuperAdminStorageSize(int $bytes): string
{

    if ($bytes >= 1024 * 1024 * 1024) {

        return round($bytes / 1024 / 1024 / 1024, 2) . " GB";

    }

    if ($bytes >= 1024 * 1024) {

        return round($bytes / 1024 / 1024, 2) . " MB";

    }

    return round($bytes / 1024, 2) . " KB";

}


function getSuperAdminStorageUsed(): string
{

    $uploadPath = dirname(__DIR__)
        . DIRECTORY_SEPARATOR
        . "uploads"
        . DIRECTORY_SEPARATOR
        . "chat-files";

    if (!is_dir($uploadPath)) {

        return "--";

    }

    try {

        $totalBytes = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $uploadPath,
                FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $fileInfo) {

            if ($fileInfo->isFile()) {

                $totalBytes += $fileInfo->getSize();

            }

        }

        return formatSuperAdminStorageSize($totalBytes);

    } catch (Throwable $exception) {

        return "--";

    }

}


/* ==========================================
   03. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Super Admin Dashboard");

$page_heading = "Super Admin Dashboard";

$page_description = "Monitor the XD Chat platform from one secure control center.";

$active_menu = "dashboard";

$dashboardCards = [
    [
        "label" => "Total Users",
        "value" => getSuperAdminCount($pdo, "users"),
        "icon" => "fa-solid fa-users",
        "tone" => "blue"
    ],
    [
        "label" => "Total Websites",
        "value" => getSuperAdminCount($pdo, "websites"),
        "icon" => "fa-solid fa-globe",
        "tone" => "green"
    ],
    [
        "label" => "Total Widgets",
        "value" => getSuperAdminCount($pdo, "widgets"),
        "icon" => "fa-solid fa-puzzle-piece",
        "tone" => "purple"
    ],
    [
        "label" => "Total Chats",
        "value" => getSuperAdminCount($pdo, "chats"),
        "icon" => "fa-regular fa-comments",
        "tone" => "orange"
    ],
    [
        "label" => "Total Messages",
        "value" => getSuperAdminCount($pdo, "messages"),
        "icon" => "fa-regular fa-envelope",
        "tone" => "blue"
    ],
    [
        "label" => "Storage Used",
        "value" => getSuperAdminStorageUsed(),
        "icon" => "fa-solid fa-hard-drive",
        "tone" => "green"
    ]
];

require_once 'includes/header.php';
?>

<section class="xd-sa-card-grid">

    <?php foreach ($dashboardCards as $card) { ?>

        <article class="xd-sa-card">

            <div class="xd-sa-card-icon <?php echo htmlspecialchars($card["tone"]); ?>">
                <i class="<?php echo htmlspecialchars($card["icon"]); ?>"></i>
            </div>

            <div>
                <span><?php echo htmlspecialchars($card["label"]); ?></span>
                <strong><?php echo htmlspecialchars($card["value"]); ?></strong>
                <small>Live platform count.</small>
            </div>

        </article>

    <?php } ?>

</section>


<section class="xd-sa-panel-grid">

    <article class="xd-sa-panel large">

        <div class="xd-sa-panel-header">
            <div>
                <h2>Platform Activity</h2>
                <p>Charts and trend analytics will appear here in Phase 2.</p>
            </div>
        </div>

        <div class="xd-sa-chart-placeholder">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>

    </article>


    <article class="xd-sa-panel">

        <div class="xd-sa-panel-header">
            <div>
                <h2>Recent Activity</h2>
                <p>Audit and platform events placeholder.</p>
            </div>
        </div>

        <div class="xd-sa-activity-list">
            <div>
                <strong>Activity feed pending</strong>
                <span>Real events will be connected in a later phase.</span>
            </div>
            <div>
                <strong>User actions pending</strong>
                <span>Admin, website, and widget logs will be added later.</span>
            </div>
        </div>

    </article>


    <article class="xd-sa-panel">

        <div class="xd-sa-panel-header">
            <div>
                <h2>System Status</h2>
                <p>Static status shell for Phase 1.</p>
            </div>
        </div>

        <div class="xd-sa-status-list">
            <div>
                <span>Application</span>
                <strong>Ready</strong>
            </div>
            <div>
                <span>Database</span>
                <strong>Configured</strong>
            </div>
            <div>
                <span>Uploads</span>
                <strong>Protected</strong>
            </div>
        </div>

    </article>

</section>

<?php require_once 'includes/footer.php'; ?>
