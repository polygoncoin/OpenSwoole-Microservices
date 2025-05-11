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
    '__INSERT-ID__' => 'category:id',
    '__SUB-QUERY__' => [
        'sub' => [
            '__QUERY__' => "INSERT INTO `category` SET __SET__",
            '__SET__' => [
                'name' => ['payload', 'subname'],
                'parent_id' => ['__INSERT-ID__', 'category:id'],
            ],
            '__INSERT-ID__' => 'sub:id',
            '__SUB-QUERY__' => [
                'subsub' => [
                    '__QUERY__' => "INSERT INTO `category` SET __SET__",
                    '__SET__' => [
                        'name' => ['payload', 'subsubname'],
                        // 'name' => ['sqlInputs', 'return:sub:name'],
                        'parent_id' => ['__INSERT-ID__', 'sub:id'],
                    ],
                    '__INSERT-ID__' => 'subsub:id',
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
