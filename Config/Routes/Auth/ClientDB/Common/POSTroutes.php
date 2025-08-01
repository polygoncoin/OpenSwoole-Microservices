<?php
/**
 * API Route config
 * php version 8.3
 *
 * @category  API_Route_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Config\Routes\Auth\CommonRoutes\Client;

return [
    'category' => [
        '__FILE__' => $Constants::$DOC_ROOT .
            DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Queries' .
            DIRECTORY_SEPARATOR . 'Auth' .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'UserGroup' .
            DIRECTORY_SEPARATOR . 'POST' .
            DIRECTORY_SEPARATOR . 'Category.php',
    ],
    'registration' => [
        '__FILE__' => $Constants::$DOC_ROOT .
            DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Queries' .
            DIRECTORY_SEPARATOR . 'Auth' .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'UserGroup' .
            DIRECTORY_SEPARATOR . 'POST' .
            DIRECTORY_SEPARATOR . 'Registration.php',
    ],
    'address' => [
        '__FILE__' => $Constants::$DOC_ROOT .
            DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Queries' .
            DIRECTORY_SEPARATOR . 'Auth' .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'UserGroup' .
            DIRECTORY_SEPARATOR . 'POST' .
            DIRECTORY_SEPARATOR . 'Address.php',
    ],
    'registration-with-address' => [
        '__FILE__' => $Constants::$DOC_ROOT .
            DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Queries' .
            DIRECTORY_SEPARATOR . 'Auth' .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'UserGroup' .
            DIRECTORY_SEPARATOR . 'POST' .
            DIRECTORY_SEPARATOR . 'Registration-With-Address.php',
    ],
];
