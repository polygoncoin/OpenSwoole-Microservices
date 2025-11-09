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
    'user_id' => 1,
    'address' => '203'
];

return TestFunctions::trigger(
    homeURL: $homeURL,
    method: 'POST',
    route: '/address',
    header: $header,
    payload: json_encode(value: $params)
);
