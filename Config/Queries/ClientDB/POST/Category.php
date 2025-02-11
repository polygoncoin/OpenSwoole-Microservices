<?php
namespace Microservices\Config\Queries\ClientDB\POST;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "INSERT INTO `category` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', DatabaseDataTypes::$Default, Constants::$REQUIRED],
    ],
    '__SET__' => [
        //column => [payload|userDetails|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
        'parent_id' => ['custom', 0],
    ],
    'insertId' => 'category:id',
    'subQuery' => [
        'sub' => [
            'query' => "INSERT INTO `category` SET __SET__",
            '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
                ['payload', 'subname', DatabaseDataTypes::$Default, Constants::$REQUIRED],
            ],
            '__SET__' => [
                'name' => ['payload', 'subname'],
                'parent_id' => ['insertIdParams', 'category:id'],
            ],
            'insertId' => 'sub:id',
            'subQuery' => [
                'subsub' => [
                    'query' => "INSERT INTO `category` SET __SET__",
                    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
                        ['payload', 'subsubname', DatabaseDataTypes::$Default, Constants::$REQUIRED],
                    ],
                    '__SET__' => [
                        'name' => ['payload', 'subsubname'],
                        'parent_id' => ['insertIdParams', 'sub:id'],
                    ],
                    'insertId' => 'subsub:id',
                ]
            ]
        ]
    ],
    'useHierarchy' => true
];
