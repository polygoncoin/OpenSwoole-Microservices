<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `master_users` SET __SET__",
    '__SET__' => [
        ['column' => 'firstname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'firstname'],
        ['column' => 'lastname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'lastname'],
        ['column' => 'email', 'fetchFrom' => 'payload', 'fetchFromValue' => 'email'],
        ['column' => 'username', 'fetchFrom' => 'payload', 'fetchFromValue' => 'username'],
        ['column' => 'password_hash', 'fetchFrom' => 'function', 'fetchFromValue' => function($sess) {
            return password_hash($sess['payload']['password'], PASSWORD_DEFAULT);
        }],
        ['column' => 'ip', 'fetchFrom' => 'custom', 'fetchFromValue' => '127.0.0.1'],
        ['column' => 'group_id', 'fetchFrom' => 'custom', 'fetchFromValue' => '1'],
    ],
    '__INSERT-IDs__' => 'registration:id',
    'idempotentWindow' => 10
];
