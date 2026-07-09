<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : audit.php
Module  : Audit Log Helpers
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/


/* ==========================================
   01. CREATE AUDIT LOG
========================================== */

function createAuditLog(PDO $pdo, array $data): bool
{

    try {

        $ipAddress = $_SERVER["REMOTE_ADDR"] ?? null;

        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? null;

        $statement = $pdo->prepare(
            "INSERT INTO audit_logs (
                actor_user_id,
                actor_name,
                action,
                target_type,
                target_id,
                target_name,
                description,
                ip_address,
                user_agent
            )
            VALUES (
                :actor_user_id,
                :actor_name,
                :action,
                :target_type,
                :target_id,
                :target_name,
                :description,
                :ip_address,
                :user_agent
            )"
        );

        $statement->bindValue(
            ":actor_user_id",
            $data["actor_user_id"] ?? null,
            ($data["actor_user_id"] ?? null) === null ? PDO::PARAM_NULL : PDO::PARAM_INT
        );

        $statement->bindValue(":actor_name", $data["actor_name"] ?? "Unknown");

        $statement->bindValue(":action", $data["action"] ?? "");

        $statement->bindValue(":target_type", $data["target_type"] ?? "");

        $statement->bindValue(":target_id", (int) ($data["target_id"] ?? 0), PDO::PARAM_INT);

        $statement->bindValue(":target_name", $data["target_name"] ?? null);

        $statement->bindValue(":description", $data["description"] ?? null);

        $statement->bindValue(":ip_address", $ipAddress);

        $statement->bindValue(":user_agent", $userAgent);

        return $statement->execute();

    } catch (Throwable $exception) {

        return false;

    }

}
