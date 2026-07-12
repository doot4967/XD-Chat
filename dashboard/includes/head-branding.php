<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : head-branding.php
Module  : Dashboard Branding Head Include
Status  : Development
Author  : Umesh + ChatGPT
Created : 12 July 2026
==================================================
*/

if (!function_exists("getPlatformFaviconUrl")) {
    require_once dirname(__DIR__, 2) . "/includes/functions/platform-settings.php";
}

$dashboardFaviconUrl = isset($pdo) && $pdo instanceof PDO
    ? getPlatformFaviconUrl($pdo)
    : "";
?>

<?php if ($dashboardFaviconUrl !== "") { ?>
    <link rel="icon" href="<?php echo htmlspecialchars($dashboardFaviconUrl); ?>">
<?php } ?>
