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
   02. VALIDATE REQUEST
========================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: websites.php");

    exit;

}

$csrf_token = isset($_POST["csrf_token"])
    ? $_POST["csrf_token"]
    : "";

if (!verifyCsrfToken($csrf_token)) {

    header("Location: websites.php");

    exit;

}


/* ==========================================
   03. VALIDATE WEBSITE ID
========================================== */

if (!isset($_POST["id"])) {

    header("Location: websites.php");

    exit;

}

$website_id = (int) $_POST["id"];

if ($website_id <= 0) {

    header("Location: websites.php");

    exit;

}


/* ==========================================
   04. DELETE WEBSITE
========================================== */

deleteWebsite(

    $pdo,

    $website_id,

    $_SESSION["user_id"]

);


/* ==========================================
   05. REDIRECT WITH SUCCESS MESSAGE
========================================== */

header("Location: websites.php?deleted=1");

exit;
