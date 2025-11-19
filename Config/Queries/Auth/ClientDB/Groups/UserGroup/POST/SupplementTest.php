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

namespace Microservices\Config\Queries\Auth\ClientDB\Common;

use Microservices\App\Constants;

return [
    // Details of data to perform task
    '__PAYLOAD__' => [
        // [
        //     'column' => 'id',
        //     'fetchFrom' => 'pathParams',
        //     'fetchFromValue' => 'id',
        //     'dataType' => DatabaseDataTypes::$PrimaryKey,
        //     'required' => Constants::$REQUIRED
        // ],
        [
            'column' => 'id',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'payload-id-1',
        ],
        [
            'column' => 'column-1',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'payload-param-1',
        ],
    ],
    '__FUNCTION__' => 'process',
    '__SUB-PAYLOAD__' => [
        'sub' => [
            '__PAYLOAD__' => [
                [
                    'column' => 'sub-id',
                    'fetchFrom' => 'payload',
                    'fetchFromValue' => 'sub-payload-id-1',
                ],
                [
                    'column' => 'sub-column-1',
                    'fetchFrom' => 'payload',
                    'fetchFromValue' => 'sub-payload-param-1',
                ],
            ],
            '__FUNCTION__' => 'processSub',
        ]
    ],
    '__PRE-SQL-HOOKS__' => [
        'Hook_Example',
    ],

    'useHierarchy' => true
];
