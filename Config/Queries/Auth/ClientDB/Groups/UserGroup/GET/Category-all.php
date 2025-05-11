<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseDataTypes;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `category` WHERE __WHERE__",
    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'parent_id' => ['custom', 0]
    ],
    '__MODE__' => 'multipleRowFormat',
    '__SUB-QUERY__' => [
        'sub' => [
            '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'parent_id' => ['sqlResults', 'return:id'],
            ],
            '__MODE__' => 'multipleRowFormat',
            '__SUB-QUERY__' => [
                'subsub' => [
                    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
                    '__WHERE__' => [
                        'is_deleted' => ['custom', 'No'],
                        'parent_id' => ['sqlResults', 'return:sub:id'],
                    ],
                    '__MODE__' => 'multipleRowFormat',
                    '__SUB-QUERY__' => [
                        'subsubsub' => [
                            '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
                            '__WHERE__' => [
                                'is_deleted' => ['custom', 'No'],
                                'parent_id' => ['sqlResults', 'return:sub:subsub:id'],//data:address:id
                            ],
                            '__MODE__' => 'multipleRowFormat',
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
