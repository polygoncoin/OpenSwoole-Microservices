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
namespace Microservices\Config\Queries\Auth\GlobalDB\PATCH\enable;

use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    '__QUERY__' => 'UPDATE `{$Env::$groups}` SET __SET__ WHERE __WHERE__',
    '__SET__' => [
        [
            'column' => 'is_disabled',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ],
        [
            'column' => 'updated_by',
            'fetchFrom' => 'userDetails',
            'fetchFromValue' => 'user_id'
        ],
        [
            'column' => 'updated_on',
            'fetchFrom' => 'custom',
            'fetchFromValue' => date(format: 'Y-m-d H:i:s')
        ]
    ],
    '__WHERE__' => [
        [
            'column' => 'is_disabled',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'Yes'
        ],
        [
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ],
        [
            'column' => 'group_id',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'group_id',
            'dataType' => DatabaseDataTypes::$INT
        ]
    ],
    '__VALIDATE__' => [
        [
            'fn' => '_primaryKeyExist',
            'fnArgs' => [
                'table' => ['custom', Env::$groups],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT]
            ],
            'errorMessage' => 'Invalid Group Id'
        ],
        [
            'fn' => '_checkColumnValueExist',
            'fnArgs' => [
                'table' => ['custom', Env::$groups],
                'column' => ['custom', 'is_deleted'],
                'columnValue' => ['custom', 'No'],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT],
            ],
            'errorMessage' => 'Record is deleted'
        ],
        [
            'fn' => '_checkColumnValueExist',
            'fnArgs' => [
                'table' => ['custom', Env::$groups],
                'column' => ['custom', 'is_disabled'],
                'columnValue' => ['custom', 'Yes'],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT],
            ],
            'errorMessage' => 'Record is already enabled'
        ]
    ]
];
