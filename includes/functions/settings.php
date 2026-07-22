<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : settings.php
Module  : Account Settings Helpers
Status  : Development
Author  : Umesh + ChatGPT
Created : 13 July 2026
==================================================
*/


/* ==========================================
   01. GET ACCOUNT SETTINGS USER
========================================== */

function getAccountSettingsUser(PDO $pdo, int $userId): ?array
{

    $statement = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            password,
            role,
            status,
            created_at,
            updated_at
         FROM users
         WHERE id = :user_id
         LIMIT 1"
    );

    $statement->bindValue(":user_id", $userId, PDO::PARAM_INT);

    $statement->execute();

    $user = $statement->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;

}


/* ==========================================
   02. CHECK EMAIL OWNERSHIP
========================================== */

function accountSettingsEmailBelongsToAnotherUser(
    PDO $pdo,
    string $email,
    int $currentUserId
): bool {

    $statement = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE email = :email
         AND id != :user_id
         LIMIT 1"
    );

    $statement->bindValue(":email", $email);

    $statement->bindValue(":user_id", $currentUserId, PDO::PARAM_INT);

    $statement->execute();

    return (bool) $statement->fetchColumn();

}


/* ==========================================
   03. UPDATE OWN PROFILE
========================================== */

function updateOwnAccountProfile(
    PDO $pdo,
    int $currentUserId,
    string $fullName,
    string $email
): bool {

    $statement = $pdo->prepare(
        "UPDATE users
         SET full_name = :full_name,
             email = :email
         WHERE id = :user_id
         LIMIT 1"
    );

    $statement->bindValue(":full_name", $fullName);

    $statement->bindValue(":email", $email);

    $statement->bindValue(":user_id", $currentUserId, PDO::PARAM_INT);

    return $statement->execute();

}


/* ==========================================
   04. UPDATE OWN PASSWORD
========================================== */

function updateOwnAccountPassword(
    PDO $pdo,
    int $currentUserId,
    string $passwordHash
): bool {

    $statement = $pdo->prepare(
        "UPDATE users
         SET password = :password
         WHERE id = :user_id
         LIMIT 1"
    );

    $statement->bindValue(":password", $passwordHash);

    $statement->bindValue(":user_id", $currentUserId, PDO::PARAM_INT);

    return $statement->execute();

}
