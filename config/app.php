<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : app.php
Module  : Application Configuration
Status  : Production Readiness
Author  : Umesh + ChatGPT
Created : 09 July 2026
==================================================
*/


/* ==========================================
   01. APPLICATION CONFIGURATION
========================================== */

return [

    "environment" => "local",

    "base_url" => "http://localhost/XD-Chat/",


    /* ==========================================
       02. DATABASE CONFIGURATION
    ========================================== */

    "database" => [

        "host" => "localhost",

        "name" => "xd_chat",

        "username" => "root",

        "password" => "",

        "charset" => "utf8mb4"

    ],


    /* ==========================================
       03. ERROR CONFIGURATION
    ========================================== */

    "errors" => [

        "display_errors" => true,

        "log_errors" => true

    ]

];
