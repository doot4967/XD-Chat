<?php
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
   01. LOAD APPLICATION CONFIGURATION
========================================== */

$config_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "app.php";

$app_config = is_file($config_path)
    ? require $config_path
    : [];

$environment = $app_config["environment"] ?? "local";

$base_url = $app_config["base_url"] ?? "http://localhost/XD-Chat/";

$database_config = $app_config["database"] ?? [];

$error_config = $app_config["errors"] ?? [];


/* ==========================================
   02. ERROR CONFIGURATION
========================================== */

error_reporting(E_ALL);

ini_set(
    "display_errors",
    !empty($error_config["display_errors"]) ? "1" : "0"
);

ini_set(
    "log_errors",
    !empty($error_config["log_errors"]) ? "1" : "0"
);


/* ==========================================
   03. BACKWARD-COMPATIBLE CONSTANTS
========================================== */

if (!defined("XD_APP_ENV")) {

    define("XD_APP_ENV", $environment);

}

if (!defined("XD_BASE_URL")) {

    define("XD_BASE_URL", rtrim($base_url, "/") . "/");

}


/* ==========================================
   04. DATABASE CONFIGURATION
========================================== */

$host = $database_config["host"] ?? "localhost";

$dbname = $database_config["name"] ?? "xd_chat";

$username = $database_config["username"] ?? "root";

$password = $database_config["password"] ?? "";

$charset = $database_config["charset"] ?? "utf8mb4";


/* ==========================================
   05. PDO CONNECTION
========================================== */

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset={$charset}",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database connection failed.");

}
