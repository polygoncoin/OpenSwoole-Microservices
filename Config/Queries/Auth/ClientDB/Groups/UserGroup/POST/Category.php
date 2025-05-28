<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `category` SET __SET__",
    '__SET__' => [
        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'name'],
        ['column' => 'parent_id', 'fetchFrom' => 'custom', 'fetchFromValue' => 0],
    ],
    '__INSERT-IDs__' => 'category:id',
    '__SUB-QUERY__' => [
        'sub' => [
            '__QUERY__' => "INSERT INTO `category` SET __SET__",
            '__SET__' => [
                ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'subname'],
                ['column' => 'parent_id', 'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'category:id'],
            ],
            '__INSERT-IDs__' => 'sub:id',
            '__SUB-QUERY__' => [
                'subsub' => [
                    '__QUERY__' => "INSERT INTO `category` SET __SET__",
                    '__SET__' => [
                        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'subsubname'],
                        ['column' => 'parent_id', 'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'sub:id'],
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
