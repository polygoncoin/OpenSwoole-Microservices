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
$proceed = Constant::$FALSE;

if (
	isset($token)
	&& $token !== Constant::$NULL
) {
	$headerArray[] = "Authorization: Bearer {$token}";
	$proceed = Constant::$TRUE;
}
if (
	isset($sessionCookie)
	&& $sessionCookie !== Constant::$NULL
) {
	$headerArray[] = "Cookie: {$sessionCookie}";
	$proceed = Constant::$TRUE;
}

if (isset($proceed)) {
	$paramArray = [
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
			'payload-param-1' => 'payload-param-2-value',
			'sub' => [
				'sub-payload-id-1' => 1,
				'sub-payload-param-1' => 'sub-payload-param-1-value'
			]
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
			'payload-param-1' => 'payload-param-4-value',
			'sub' => [
				'sub-payload-id-1' => 1,
				'sub-payload-param-1' => 'sub-payload-param-1-value'
			]
		],
	];

	return Web::trigger(
		homeURL: $homeURL,
		httpRequestMethod: Constant::$POST,
		route: '/custom/SupplementTest',
		header: $headerArray,
		payload: json_encode(
			value: $paramArray
		)
	);
}
