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

use Microservices\TestCases\TestFunctions;

$header = $defaultHeaders;
$header[] = $contentType;
if (isset($token)) {
    $header[] = "Authorization: Bearer {$token}";
}

$params = [
    [
        'name' => 'ramesh0',
        'sub' => [
            'subname' => 'ramesh1',
            'subsub' => [
                [
                    'subsubname' => 'ramesh'
                ],
                [
                    'subsubname' => 'ramesh'
                ]
            ]
        ]
    ],
    [
        'name' => 'ramesh1',
        'sub' => [
            'subname' => 'ramesh1',
            'subsub' => [
                'subsubname' => 'ramesh'
            ]
        ]
    ]
];

return TestFunctions::trigger(
    homeURL: $homeURL,
    method: 'POST',
    route: '/category/import',
    header: $header,
    payload: '',//json_encode(value: $params),
    file: $curlFile
);
