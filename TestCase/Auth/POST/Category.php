<?php

/**
 * Test Case
 * php version 8.3
 *
 * @category  Test Case
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCase;

use Microservices\App\Web;

$header = $defaultHeaders;
// $header[] = 'Content-Type: multipart/form-data; charset=utf-8';
if (isset($token)) {
	$header[] = "Authorization: Bearer {$token}";

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

	return Web::trigger(
		homeURL: $homeURL,
		method: 'POST',
		route: '/category/import',
		header: $header,
		payload: '',//json_encode(value: $params),
		file: $curlFile
	);
}
