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

use Microservices\App\DatabaseServerDataType;

return [
	'__QUERY__' => "INSERT INTO `{$Env::$groupsTable}` SET __SET__",
	'__SET__' => [
		[
			'column' => 'name',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'name'
		],
		[
			'column' => 'customer_id',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'customer_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'connection_id',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'connection_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'allowed_cidr',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'allowed_cidr'
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
	'__INSERT-IDs__' => 'group:id',
];
