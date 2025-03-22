<?php

return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'lti_provider',
        'user' => 'root',
        'password' => 'root',
        'driver' => 'pdo_mysql',
    ],
    'lti13' => [
        'private_key' => '', // Path to private key file
        'kid' => 'tool-key-1', // Key identifier
    ]
];
