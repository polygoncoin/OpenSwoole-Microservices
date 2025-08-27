<?php
/**
 * TestCases
 * php version 8.3
 *
 * @category  TestCases
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\TestCases;

$res = trigger(
    homeURL: $homeURL,
    method: 'POST',
    route: '/login',
    header: [],
    payload: json_encode(value: $payload)
);

if ($res) {
    $token = $res['responseBody']['Results']['Token'];
    $header = ["Authorization: Bearer {$token}"];
}

return $res;
