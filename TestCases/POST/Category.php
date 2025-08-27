<?php
/**
 * TestCases
 * php version 8.3
 *
 * @category  TestCases
 * @package   Openswoole-Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\TestCases;

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

return trigger(
    homeURL: $homeURL,
    method: 'POST',
    route: '/category',
    header: $header,
    payload: json_encode(value: $params)
);
