<?php
/**
 * API Query config
 * php version 8.3
 *
 * @category  API_Query_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\POST;

use Microservices\App\DatabaseCacheKey;

return [
    '__QUERY__' => 'INSERT INTO `category` SET __SET__',
    '__SET__' => [
        [
            'column' => 'name',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'name'
        ],
        [
            'column' => 'parent_id',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 0
        ],
    ],
    '__INSERT-IDs__' => 'category:id',
    '__SUB-QUERY__' => [
        'sub' => [
            '__QUERY__' => 'INSERT INTO `category` SET __SET__',
            '__SET__' => [
                [
                    'column' => 'name',
                    'fetchFrom' => 'payload',
                    'fetchFromValue' => 'subname'
                ],
                [
                    'column' => 'parent_id',
                    'fetchFrom' => '__INSERT-IDs__',
                    'fetchFromValue' => 'category:id'
                ],
            ],
            '__INSERT-IDs__' => 'sub:id',
            '__SUB-QUERY__' => [
                'subsub' => [
                    '__QUERY__' => 'INSERT INTO `category` SET __SET__',
                    '__SET__' => [
                        [
                            'column' => 'name',
                            'fetchFrom' => 'payload',
                            'fetchFromValue' => 'subsubname'
                        ],
                        [
                            'column' => 'parent_id',
                            'fetchFrom' => '__INSERT-IDs__',
                            'fetchFromValue' => 'sub:id'
                        ],
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
