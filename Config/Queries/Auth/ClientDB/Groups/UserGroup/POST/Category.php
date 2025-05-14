<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `category` SET __SET__",
    '__SET__' => [
        'name' => ['payload', 'name'],
        'parent_id' => ['custom', 0],
    ],
    '__INSERT-IDs__' => 'category:id',
    '__SUB-QUERY__' => [
        'sub' => [
            '__QUERY__' => "INSERT INTO `category` SET __SET__",
            '__SET__' => [
                'name' => ['payload', 'subname'],
                'parent_id' => ['__INSERT-IDs__', 'category:id'],
            ],
            '__INSERT-IDs__' => 'sub:id',
            '__SUB-QUERY__' => [
                'subsub' => [
                    '__QUERY__' => "INSERT INTO `category` SET __SET__",
                    '__SET__' => [
                        'name' => ['payload', 'subsubname'],
                        // 'name' => ['sqlParams', 'return:sub:name'],
                        'parent_id' => ['__INSERT-IDs__', 'sub:id'],
                    ],
                    '__INSERT-IDs__' => 'subsub:id',
                ]
            ]
        ]
    ],
    'useHierarchy' => true,
    'affectedCacheKeys' => [
        DatabaseCacheKey::$Category,
        DatabaseCacheKey::$Category1
    ]
];
