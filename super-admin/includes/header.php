<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : header.php
Module  : Super Admin Header
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($page_title); ?></title>

    <link rel="stylesheet" href="../assets/css/01-reset.css">
    <link rel="stylesheet" href="../assets/css/02-variables.css">
    <link rel="stylesheet" href="../assets/css/03-base.css">
    <link rel="stylesheet" href="assets/css/super-admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="xd-sa-dashboard">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="xd-sa-main">

        <?php require_once 'includes/topbar.php'; ?>

        <div class="xd-sa-content">
