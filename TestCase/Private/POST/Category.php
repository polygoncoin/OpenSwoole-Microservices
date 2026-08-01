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
// $headerArray[] = 'Content-Type: multipart/form-data; charset=utf-8';
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

	return Web::trigger(
		homeURL: $homeURL,
		httpRequestMethod: Constant::$POST,
		route: '/category/import',
		header: $headerArray,
		payload: '',//json_encode(value: $paramArray),
		fileLocation: $curlFile
	);
}
