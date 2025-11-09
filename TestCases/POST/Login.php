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

$res = TestFunctions::trigger(
    homeURL: $homeURL,
    method: 'POST',
    route: '/login',
    header: $header,
    payload: json_encode(value: $payload)
);

if (
    !isset($res['responseHeaders']['Set-Cookie'])
    && $res
) {
    $token = $res['responseBody']['Results']['Token'];
}

return $res;
