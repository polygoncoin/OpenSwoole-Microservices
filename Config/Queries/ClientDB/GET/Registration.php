<?php
namespace Microservices\Config\Queries\ClientDB\GET;

//return represents root for hierarchyData
use Microservices\App\Constants;

return [
    'query' => "SELECT * FROM `{$Env::$clientDB}`.`registration` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No']
    ],
    'mode' => 'multipleRowFormat',//Multiple rows returned.
    'subQuery' => [
        'reg-address' => [
            'query' => "SELECT * FROM `{$Env::$clientDB}`.`address` WHERE __WHERE__",
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'registration_id' => ['hierarchyData', 'return:id'],
            ],
            'mode' => 'multipleRowFormat',//Multiple rows returned.
        ]
    ],
    'useHierarchy' => true
];
