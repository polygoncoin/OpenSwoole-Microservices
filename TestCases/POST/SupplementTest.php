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

$header = $defaultHeaders;
$header[] = $contentType;
if (isset($token)) {
    $header[] = "Authorization: Bearer {$token}";
}

$params = [
    [
        'payload-id-1' => 1,
        'payload-param-1' => 'payload-param-1-value',
        'sub' => [
            'sub-payload-id-1' => 1,
            'sub-payload-param-1' => 'sub-payload-param-1-value'
        ]
    ],
    [
        'payload-id-1' => 2,
        'payload-param-1' => 'payload-param-2-value'
    ],
    [
        'payload-id-1' => 3,
        'payload-param-1' => 'payload-param-3-value',
        'sub' => [
            'sub-payload-id-1' => 2,
            'sub-payload-param-1' => 'sub-payload-param-2-value'
        ]
    ],
    [
        'payload-id-1' => 4,
        'payload-param-1' => 'payload-param-4-value'
    ],
];

return TestFunctions::trigger(
    homeURL: $homeURL,
    method: 'POST',
    route: '/custom/SupplementTest',
    header: $header,
    payload: json_encode(value: $params)
);
