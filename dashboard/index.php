<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : index.php
Module  : Dashboard
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../includes/functions/session.php';

requireLogin();


/* ==========================================
   02. PAGE TITLE
========================================== */

$page_title = "Dashboard | XD Chat";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo $page_title; ?></title>

</head>

<body>

    <h1>
        Welcome,
        <?php echo $_SESSION["user_name"]; ?>
    </h1>

    <p>

        XD Chat Dashboard

    </p>

    <hr>

    <p>

        Logged in as :

        <strong>

            <?php echo $_SESSION["user_email"]; ?>

        </strong>

    </p>

    <br>

    <a href="../auth/logout.php">

        Logout

    </a>

</body>

</html>