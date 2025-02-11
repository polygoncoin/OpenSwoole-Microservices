<?php
namespace Microservices\Config\Queries\ClientDB\GET;

//return represents root for hierarchyData
use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "SELECT * FROM `category` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'parent_id' => ['custom', 0]
    ],
    'mode' => 'multipleRowFormat',//Multiple rows returned.
    'subQuery' => [
        'sub' => [
            'query' => "SELECT * FROM `category` WHERE __WHERE__",
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'parent_id' => ['hierarchyData', 'return:id'],
            ],
            'mode' => 'multipleRowFormat',//Multiple rows returned.
            'subQuery' => [
                'subsub' => [
                    'query' => "SELECT * FROM `category` WHERE __WHERE__",
                    '__WHERE__' => [
                        'is_deleted' => ['custom', 'No'],
                        'parent_id' => ['hierarchyData', 'return:sub:id'],
                    ],
                    'mode' => 'multipleRowFormat',//Multiple rows returned.
                    'subQuery' => [
                        'subsubsub' => [
                            'query' => "SELECT * FROM `category` WHERE __WHERE__",
                            '__WHERE__' => [
                                'is_deleted' => ['custom', 'No'],
                                'parent_id' => ['hierarchyData', 'return:sub:subsub:id'],//data:address:id
                            ],
                            'mode' => 'multipleRowFormat',//Multiple rows returned.
                        ]
                    ]
                ]
            ],
        ]
    ],
    'useHierarchy' => true,
    'fetchFrom' => 'Master',
];
