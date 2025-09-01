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
namespace Microservices\Config\Queries\Auth\GlobalDB\DELETE;

use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    '__QUERY__' => 'UPDATE `{$Env::$clients}` SET __SET__ WHERE __WHERE__',
    '__SET__' => [
        [
            'column' => 'updated_by',
            'fetchFrom' => 'uDetails',
            'fetchFromValue' => 'id'
        ],
        [
            'column' => 'updated_on',
            'fetchFrom' => 'custom',
            'fetchFromValue' => date('Y-m-d H:i:s')
        ],
    ],
    '__WHERE__' => [
        [
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ],
        [
            'column' => 'id',
            'fetchFrom' => 'uriParams',
            'fetchFromValue' => 'id',
            'dataType' => DatabaseDataTypes::$INT
        ]
    ],
    '__VALIDATE__' => [
        [
            'fn' => '_primaryKeyExist',
            'fnArgs' => [
                'table' => ['custom', Env::$clients],
                'primary' => ['custom', 'id'],
                'id' => ['payload', 'id', DatabaseDataTypes::$INT]
            ],
            'errorMessage' => 'Invalid Client Id'
        ],
        [
            'fn' => '_checkColumnValueExist',
            'fnArgs' => [
                'table' => ['custom', Env::$clients],
                'column' => ['custom', 'is_deleted'],
                'columnValue' => ['custom', 'No'],
                'primary' => ['custom', 'id'],
                'id' => ['payload', 'id', DatabaseDataTypes::$INT],
            ],
            'errorMessage' => 'Record is already deleted'
        ]
    ]
];
