<?php
namespace Microservices\Config\Queries\Open\GET;

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseDataTypes;

return [
    'query' => "SELECT * FROM `category` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'parent_id' => ['custom', 0]
    ],
    'mode' => 'multipleRowFormat',
    'subQuery' => [
        'sub' => [
            'query' => "SELECT * FROM `category` WHERE __WHERE__",
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'parent_id' => ['resultSetData', 'return:id'],
            ],
            'mode' => 'multipleRowFormat',
            'subQuery' => [
                'subsub' => [
                    'query' => "SELECT * FROM `category` WHERE __WHERE__",
                    '__WHERE__' => [
                        'is_deleted' => ['custom', 'No'],
                        'parent_id' => ['resultSetData', 'return:sub:id'],
                    ],
                    'mode' => 'multipleRowFormat',
                    'subQuery' => [
                        'subsubsub' => [
                            'query' => "SELECT * FROM `category` WHERE __WHERE__",
                            '__WHERE__' => [
                                'is_deleted' => ['custom', 'No'],
                                'parent_id' => ['resultSetData', 'return:sub:subsub:id'],//data:address:id
                            ],
                            'mode' => 'multipleRowFormat',
                        ]
                    ]
                ]
            ],
        ]
    ],
    'useResultSet' => true,
    'fetchFrom' => 'Master',
    'cacheKey' => DatabaseCacheKey::$Category
];
