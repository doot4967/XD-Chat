<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : connection.php
Module  : Database Connection
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. DATABASE CONFIGURATION
========================================== */

$host = "localhost";

$dbname = "xd_chat";

$username = "root";

$password = "";


/* ==========================================
   02. PDO CONNECTION
========================================== */

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database connection failed.");

}