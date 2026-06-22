<?php

function current_ay_id(PDO $pdo): ?int
{
    $stmt = $pdo->query("
        SELECT ay_id
        FROM academic_year
        WHERE is_active = 1
        LIMIT 1
    ");

    $id = $stmt->fetchColumn();

    return $id ? (int)$id : null;
}

function current_ay_name(PDO $pdo): string
{
    $stmt = $pdo->query("
        SELECT ay_name
        FROM academic_year
        WHERE is_active = 1
        LIMIT 1
    ");

    return (string)($stmt->fetchColumn() ?: 'No Active AY');
}