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

namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\GET;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `{$Env::$clientUsersTable}` WHERE __WHERE__",
    '__QUERY__' => "SELECT * FROM `{$Env::$clientUsersTable}` WHERE __WHERE__",
    '__WHERE__' => [
        [
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ]
    ],
    '__MODE__' => 'multipleRowFormat',
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => 'SELECT * FROM `address` WHERE __WHERE__',
            '__WHERE__' => [
                [
                    'column' => 'is_deleted',
                    'fetchFrom' => 'custom',
                    'fetchFromValue' => 'No'
                ],
                [
                    'column' => 'user_id',
                    'fetchFrom' => 'sqlResults',
                    'fetchFromValue' => 'return:id'
                ],
            ],
            '__MODE__' => 'multipleRowFormat',
        ]
    ],
    'useResultSet' => true
];
