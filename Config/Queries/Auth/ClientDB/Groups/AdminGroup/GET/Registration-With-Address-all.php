<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `master_users` WHERE __WHERE__",
    '__QUERY__' => "SELECT * FROM `master_users` WHERE __WHERE__",
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No']
    ],
    '__MODE__' => 'multipleRowFormat',
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "SELECT * FROM `address` WHERE __WHERE__",
            '__WHERE__' => [
                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
                ['column' => 'user_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:user_id'],
            ],
            '__MODE__' => 'multipleRowFormat',
        ]
    ],
    'useResultSet' => true
];
