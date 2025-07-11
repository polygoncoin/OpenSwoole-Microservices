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
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `address` SET __SET__",
    '__SET__' => [
        [
            'column' => 'user_id',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'user_id',
            'dataType' => DatabaseDataTypes::$INT
        ],
        [
            'column' => 'address',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'address'
        ],
    ],
    '__INSERT-IDs__' => 'address:id'
];
