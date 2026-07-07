<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : website-delete.php
Module  : Delete Website
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../database/connection.php';

require_once '../includes/functions/session.php';

require_once '../includes/functions/website.php';

requireLogin();


/* ==========================================
   02. VALIDATE WEBSITE ID
========================================== */

if (!isset($_GET["id"])) {

    header("Location: websites.php");

    exit;

}

$website_id = (int) $_GET["id"];


/* ==========================================
   03. DELETE WEBSITE
========================================== */

deleteWebsite(

    $pdo,

    $website_id,

    $_SESSION["user_id"]

);


/* ==========================================
   04. REDIRECT WITH SUCCESS MESSAGE
========================================== */

header("Location: websites.php?deleted=1");

exit;