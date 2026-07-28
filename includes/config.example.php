<?php
declare(strict_types=1);

return [
    'local' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'your_database',
        'username' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
        'ssl' => false,
        'ssl_ca' => null,
    ],
    'online' => [
        'host' => 'your_remote_host',
        'port' => 3306,
        'database' => 'your_remote_database',
        'username' => 'your_remote_username',
        'password' => 'your_remote_password',
        'charset' => 'utf8mb4',
        'ssl' => false,
        'ssl_ca' => null,
    ],
];
