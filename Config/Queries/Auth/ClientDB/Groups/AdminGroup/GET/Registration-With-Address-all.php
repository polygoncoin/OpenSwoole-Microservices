<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `master_users` WHERE __WHERE__",
    'query' => "SELECT * FROM `master_users` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No']
    ],
    'mode' => 'multipleRowFormat',
    'subQuery' => [
        'address' => [
            'query' => "SELECT * FROM `address` WHERE __WHERE__",
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'user_id' => ['sqlResults', 'return:user_id'],
            ],
            'mode' => 'multipleRowFormat',
        ]
    ],
    'useResultSet' => true
];
