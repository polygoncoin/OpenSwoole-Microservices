<?php

/**
 * Test Case
 * php version 8.3
 * 
 * @category  Test Case
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCase;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Web;

$headerArray = $defaultHeaderArray;
$headerArray[] = $contentType;
$proceed = false;

if (
	isset($token)
	&& $token !== Constant::$NULL
) {
	$headerArray[] = "Authorization: Bearer {$token}";
	$proceed = true;
}
if (
	isset($sessionCookie)
	&& $sessionCookie !== Constant::$NULL
) {
	$headerArray[] = "Cookie: {$sessionCookie}";
	$proceed = true;
}

if (isset($proceed)) {
	$paramArray = [
		'firstname' => 'Ramesh',
		'lastname' => 'Jangid',
		'email' => 'ramesh@test.com',
		'username' => 'test',
		'password' => 'shames11',
		'address' => [
			'address' => 'A-203'
		]
	];

	return Web::trigger(
		homeURL: $homeURL,
		httpRequestMethod: Constant::$POST,
		route: '/registration-with-address',
		header: $headerArray,
		payload: json_encode(value: $paramArray)
	);
}
