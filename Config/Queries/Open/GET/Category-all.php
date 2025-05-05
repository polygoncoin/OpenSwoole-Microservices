<?php
namespace Microservices\Config\Queries\Open\GET;

use Microservices\App\DatabaseOpenCacheKey;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `category` WHERE __WHERE__",
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
                'parent_id' => ['sqlResults', 'return:id'],
            ],
            'mode' => 'multipleRowFormat',
            'subQuery' => [
                'subsub' => [
                    'query' => "SELECT * FROM `category` WHERE __WHERE__",
                    '__WHERE__' => [
                        'is_deleted' => ['custom', 'No'],
                        'parent_id' => ['sqlResults', 'return:sub:id'],
                    ],
                    'mode' => 'multipleRowFormat',
                    'subQuery' => [
                        'subsubsub' => [
                            'query' => "SELECT * FROM `category` WHERE __WHERE__",
                            '__WHERE__' => [
                                'is_deleted' => ['custom', 'No'],
                                'parent_id' => ['sqlResults', 'return:sub:subsub:id'],//data:address:id
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
    'cacheKey' => DatabaseOpenCacheKey::$Category
];
