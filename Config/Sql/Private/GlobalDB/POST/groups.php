<?php

/**
 * API Query config
 * php version 8.3
 *
 * @category  API_Query_Config
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

use Microservices\App\DatabaseServerDataType;

return [
	'__QUERY__' => "INSERT INTO `{$Env::$groupTable}` SET __SET__",
	'__SET__' => [
		[
			'column' => 'name',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'name'
		],
		[
			'column' => 'customer_id',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'customer_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'connection_id',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'connection_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'allowed_cidr',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'allowed_cidr'
		],
		[
			'column' => 'comments',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'comments'
		],
		[
			'column' => 'created_by',
			'fetchFrom' => 'userData',
			'fetchFromData' => 'id'
		],
		[
			'column' => 'created_on',
			'fetchFrom' => 'custom',
			'fetchFromData' => date(format: 'Y-m-d H:i:s')
		],
		[
			'column' => 'is_approved',
			'fetchFrom' => 'custom',
			'fetchFromData' => 'No'
		],
		[
			'column' => 'is_disabled',
			'fetchFrom' => 'custom',
			'fetchFromData' => 'No'
		],
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromData' => 'No'
		]
	],
	'__INSERT-IDs__' => 'group:id',
];
