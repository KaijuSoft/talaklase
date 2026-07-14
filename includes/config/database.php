<?php

return [

    'local' => [

        'host' => '127.0.0.1',
        'port' => 3306,

        'database' => 'talaklasedb',

        'username' => 'root',
        'password' => '',

        'charset' => 'utf8mb4',

        'ssl' => false,
        'ssl_ca' => null,
    ],

    'tidb' => [

        'host' => 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com',
        'port' => 4000,

        'database' => 'talaklasedb',

        'username' => '43PYUCXNx91RQ8v.root',

        'password' => 'kxfO6GgjbTi7fbbH',

        'charset' => 'utf8mb4',

        'ssl' => true,

        'ssl_ca' => __DIR__ . '/../cert/isrgrootx1.pem',
	],

     'online' => [

        'host' => 'sql12.freesqldatabase.com',
        'port' => 3306,

        'database' => 'sql12817970',

        'username' => 'sql12817970',

        'password' => 'N9dIfCwPRj',

        'charset' => 'utf8mb4',

         'ssl' => false,
        'ssl_ca' => null,
       
    ],

];