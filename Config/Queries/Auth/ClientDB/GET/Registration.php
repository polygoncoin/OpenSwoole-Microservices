<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `registration` WHERE __WHERE__",
    'query' => "SELECT * FROM `registration` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No']
    ],
    'mode' => 'multipleRowFormat',
    'subQuery' => [
        'reg-address' => [
            'query' => "SELECT * FROM `address` WHERE __WHERE__",
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'registration_id' => ['resultSetData', 'return:id'],
            ],
            'mode' => 'multipleRowFormat',
        ]
    ],
    'useResultSet' => true
];
