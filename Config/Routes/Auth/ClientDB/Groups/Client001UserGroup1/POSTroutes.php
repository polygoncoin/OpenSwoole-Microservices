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
namespace Microservices\Config\Routes\Auth\ClientDB\Groups\Client001UserGroup1;

return array_merge(
    require $Constants::$DOC_ROOT .
        DIRECTORY_SEPARATOR . 'Config' .
        DIRECTORY_SEPARATOR . 'Routes' .
        DIRECTORY_SEPARATOR . 'Auth' .
        DIRECTORY_SEPARATOR . 'ClientDB' .
        DIRECTORY_SEPARATOR . 'Common' .
        DIRECTORY_SEPARATOR . 'POSTroutes.php',
    require $Constants::$DOC_ROOT .
        DIRECTORY_SEPARATOR . 'Config' .
        DIRECTORY_SEPARATOR . 'Routes' .
        DIRECTORY_SEPARATOR . 'Auth' .
        DIRECTORY_SEPARATOR . 'ClientDB' .
        DIRECTORY_SEPARATOR . 'Common' .
        DIRECTORY_SEPARATOR . 'ThirdParty' .
        DIRECTORY_SEPARATOR . 'POSTroutes.php',
    require $Constants::$DOC_ROOT .
        DIRECTORY_SEPARATOR . 'Config' .
        DIRECTORY_SEPARATOR . 'Routes' .
        DIRECTORY_SEPARATOR . 'Auth' .
        DIRECTORY_SEPARATOR . 'ClientDB' .
        DIRECTORY_SEPARATOR . 'Common' .
        DIRECTORY_SEPARATOR . 'Upload' .
        DIRECTORY_SEPARATOR . 'POSTroutes.php',
);
