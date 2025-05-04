<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseDataTypes;

return [
    'query' => "INSERT INTO `category` SET __SET__",
    '__SET__' => [
        'name' => ['payload', 'name'],
        'parent_id' => ['custom', 0],
    ],
    'insertId' => 'category:id',
    'subQuery' => [
        'sub' => [
            'query' => "INSERT INTO `category` SET __SET__",
            '__SET__' => [
                'name' => ['payload', 'subname'],
                'parent_id' => ['insertId', 'category:id'],
            ],
            'insertId' => 'sub:id',
            'subQuery' => [
                'subsub' => [
                    'query' => "INSERT INTO `category` SET __SET__",
                    '__SET__' => [
                        'name' => ['payload', 'subsubname'],
                        // 'name' => ['sqlInputs', 'return:sub:name'],
                        'parent_id' => ['insertId', 'sub:id'],
                    ],
                    'insertId' => 'subsub:id',
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
