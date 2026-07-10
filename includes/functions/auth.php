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

function registerUser($pdo, $full_name, $email, $password, array $options = [])
{

    $allowedRoles = [
        "admin",
        "agent"
    ];

    $allowedStatuses = [
        "active",
        "inactive"
    ];

    $role = $options["role"] ?? "admin";

    $status = $options["status"] ?? "active";

    $minimumPasswordLength = (int) ($options["minimum_password_length"] ?? 8);

    if (!in_array($role, $allowedRoles, true)) {

        $role = "admin";

    }

    if (!in_array($status, $allowedStatuses, true)) {

        $status = "active";

    }

    if ($minimumPasswordLength < 8 || $minimumPasswordLength > 64) {

        $minimumPasswordLength = 8;

    }

    if (strlen($password) < $minimumPasswordLength) {

        return "Password must be at least " . $minimumPasswordLength . " characters.";

    }

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
            password,
            role,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $insert->execute([
        $full_name,
        $email,
        $hash,
        $role,
        $status
    ]);

    if ($status === "inactive") {

        return "Account created successfully. Your account is pending activation. Please contact support.";

    }

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
