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

return [
  '__QUERY__' => 'UPDATE `master_users` SET __SET__ WHERE __WHERE__',
  '__VALIDATE__' => [
    [
      'fn' => 'primaryKeyExist',
      'fnArgs' => [
                'table' => ['custom', 'master_users'],
                'primary' => ['custom', 'id'],
                'id' => ['uriParams', 'id']
            ],
          'errorMessage' => 'Invalid registration id'
    ],
  ]
];
