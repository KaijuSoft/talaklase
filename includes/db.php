<?php
// TalaKlase - Database Connection
// Reads connection settings from config/database.php

$dbConfig = require __DIR__ . '/config/database.php';

function buildPDO(array $cfg): PDO
{
    ...
}

function getConnection(): PDO
{
    global $dbConfig;

    try {
        $pdo = buildPDO($dbConfig['online']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['db_source'] = 'Online Database';
        }

        return $pdo;

    } catch (Throwable $e) {

        try {

            $pdo = buildPDO($dbConfig['local']);

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['db_source'] = 'Local Database';
            }

            return $pdo;

        } catch (Throwable $e) {
            throw $e;
        }
    }
}

function getLocalConnection(): PDO
{
    global $dbConfig;

    return buildPDO($dbConfig['local']);
}

function getOnlineConnection(): PDO
{
    global $dbConfig;

    return buildPDO($dbConfig['online']);
}