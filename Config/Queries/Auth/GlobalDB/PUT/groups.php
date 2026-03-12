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

namespace Microservices\Config\Queries\Auth\GlobalDB\PUT;

use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;

return [
	'__QUERY__' => "UPDATE `{$Env::$groupsTable}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'name',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'name'
		],
		[
			'column' => 'customer_id',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'customer_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'connection_id',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'connection_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'allowed_cidr',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'allowed_cidr'
		],
		[
			'column' => 'comments',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'comments'
		],
		[
			'column' => 'updated_by',
			'fetchFrom' => 'uDetails',
			'fetchFromValue' => 'id'
		],
		[
			'column' => 'updated_on',
			'fetchFrom' => 'custom',
			'fetchFromValue' => date(format: 'Y-m-d H:i:s')
		]
	],
	'__WHERE__' => [
		[
			'column' => 'is_approved',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'Yes'
		],
		[
			'column' => 'is_disabled',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'No'
		],
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'No'
		],
		[
			'column' => 'id',
			'fetchFrom' => 'routeParams',
			'fetchFromValue' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		]
	],
	'__VALIDATE__' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
				'table' => ['custom', Env::$groupsTable],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT]
			],
			'errorMessage' => 'Invalid Group Id'
		],
	]
];
