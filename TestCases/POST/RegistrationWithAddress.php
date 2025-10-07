<?php

/**
 * TestCases
 * php version 8.3
 *
 * @category  TestCases
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCases;

$params = [
    'firstname' => 'Ramesh',
    'lastname' => 'Jangid',
    'email' => 'ramesh@test.com',
    'username' => 'test',
    'password' => 'shames11',
    'address' => [
        'address' => 'A-203'
    ]
];

return trigger(
    homeURL: $homeURL,
    method: 'POST',
    route: '/registration-with-address',
    header: $header,
    payload: json_encode(value: $params)
);
