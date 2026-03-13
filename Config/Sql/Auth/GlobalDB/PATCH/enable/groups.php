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

namespace Microservices\Config\Sql\Auth\GlobalDB\PATCH\enable;

use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;

return [
	'__QUERY__' => "UPDATE `{$Env::$groupsTable}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'is_disabled',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'No'
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
			'column' => 'is_disabled',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'Yes'
		],
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'No'
		],
		[
			'column' => 'id',
			'fetchFrom' => 'payload',
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
		[
			'fn' => '_checkColumnValueExist',
			'fnArgs' => [
				'table' => ['custom', Env::$groupsTable],
				'column' => ['custom', 'is_deleted'],
				'columnValue' => ['custom', 'No'],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT],
			],
			'errorMessage' => 'Record is deleted'
		],
		[
			'fn' => '_checkColumnValueExist',
			'fnArgs' => [
				'table' => ['custom', Env::$groupsTable],
				'column' => ['custom', 'is_disabled'],
				'columnValue' => ['custom', 'Yes'],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT],
			],
			'errorMessage' => 'Record is already enabled'
		]
	]
];
