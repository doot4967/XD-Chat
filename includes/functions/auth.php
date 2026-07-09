<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : auth.php
Module  : Authentication Functions
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==================================================
   01. REGISTER USER
================================================== */

function registerUser($pdo, $full_name, $email, $password)
{

    /* -------------------------------
       Check Existing Email
    -------------------------------- */

    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
    ");

    $check->execute([$email]);

    if ($check->rowCount() > 0) {

        return "Email already registered.";

    }


    /* -------------------------------
       Password Hash
    -------------------------------- */

    $hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /* -------------------------------
       Insert User
    -------------------------------- */

    $insert = $pdo->prepare("
        INSERT INTO users
        (
            full_name,
            email,
            password
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    $insert->execute([
        $full_name,
        $email,
        $hash
    ]);

    return "Account created successfully.";

}
/* ==================================================
   02. LOGIN USER
================================================== */

function authenticateUser($pdo, $email, $password)
{

    $query = $pdo->prepare("
        SELECT
            id,
            full_name,
            email,
            password,
            role,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $query->execute([$email]);

    $user = $query->fetch();

    if (!$user) {

        return [
            "status" => false,
            "message" => "Invalid email or password."
        ];

    }

    if ($user["status"] !== "active") {

        return [
            "status" => false,
            "message" => "Your account is inactive. Please contact support."
        ];

    }

    if (!password_verify($password, $user["password"])) {

        return [
            "status" => false,
            "message" => "Invalid email or password."
        ];

    }

    return [
        "status" => true,
        "user" => $user
    ];

}
