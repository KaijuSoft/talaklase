<?php
// TalaKlase - Database Connection
// Reads connection settings from config/database.php

$config = require __DIR__ . '/config/database.php';

function buildPDO(array $cfg): PDO
{
    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=%s",
        $cfg['host'],
        $cfg['port'],
        $cfg['database'],
        $cfg['charset'] ?? 'utf8mb4'
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    // SSL Support (TiDB, etc.)
    if (!empty($cfg['ssl'])) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $cfg['ssl_ca'];
    }

    return new PDO(
        $dsn,
        $cfg['username'],
        $cfg['password'],
        $options
    );
}

function getConnection(): PDO
{
    global $config;

    try {
        $pdo = buildPDO($config['online']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['db_source'] = 'Online Database';
        }

        return $pdo;

    } catch (PDOException $e) {

        try {

            $pdo = buildPDO($config['local']);

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['db_source'] = 'Local Database';
            }

            return $pdo;

        } catch (PDOException $e2) {

            die(json_encode([
                'error' => 'Both databases unavailable: ' . $e2->getMessage()
            ]));

        }

    }
}

function getLocalConnection(): PDO
{
    global $config;

    return buildPDO($config['local']);
}

function getOnlineConnection(): PDO
{
    global $config;

    return buildPDO($config['online']);
}