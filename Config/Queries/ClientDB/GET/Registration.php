<?php
namespace Microservices\Config\Queries\ClientDB\GET;

//return represents root for hierarchyData
use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
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
                'registration_id' => ['hierarchyData', 'return:id'],
            ],
            'mode' => 'multipleRowFormat',
        ]
    ],
    'useHierarchy' => true
];
