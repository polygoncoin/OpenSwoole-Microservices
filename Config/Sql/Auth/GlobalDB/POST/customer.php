<?php

/**
 * API Query config
 * php version 8.3
 *
 * @category  API_Query_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

return [
	'__QUERY__' => "INSERT INTO `{$Env::$customerTable}` SET __SET__",
	'__SET__' => [
		[
			'column' => 'name',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'name'
		],
		[
			'column' => 'comments',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'comments'
		],
		[
			'column' => 'created_by',
			'fetchFrom' => 'uDetail',
			'fetchFromDetail' => 'id'
		],
		[
			'column' => 'created_on',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => date(format: 'Y-m-d H:i:s')
		],
		[
			'column' => 'is_approved',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => 'No'
		],
		[
			'column' => 'is_disabled',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => 'No'
		],
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => 'No'
		]
	],
	'__INSERT-IDs__' => 'customer:id',
];
