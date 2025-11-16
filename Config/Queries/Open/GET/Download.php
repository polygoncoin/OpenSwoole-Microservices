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

namespace Microservices\public_html\Config\Queries\Open\GET;

return [
    '__DOWNLOAD__' => 'SELECT * FROM `category` WHERE __WHERE__',
    '__WHERE__' => [
        [
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ]
    ],
    'fetchFrom' => 'Master',
    'downloadFile' => 'Test.csv',
    'exportFile' => ''
];
