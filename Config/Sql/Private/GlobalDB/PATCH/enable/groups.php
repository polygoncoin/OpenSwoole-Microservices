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
	'__QUERY__' => "UPDATE `{$Env::$groupTable}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'is_disabled',
			'fetchFrom' => 'custom',
			'fetchFromData' => 'No'
		],
		[
			'column' => 'updated_by',
			'fetchFrom' => 'userData',
			'fetchFromData' => 'id'
		],
		[
			'column' => 'updated_on',
			'fetchFrom' => 'custom',
			'fetchFromData' => date(format: 'Y-m-d H:i:s')
		]
	],
	'__WHERE__' => [
		[
			'column' => 'is_disabled',
			'fetchFrom' => 'custom',
			'fetchFromData' => 'Yes'
		],
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromData' => 'No'
		],
		[
			'column' => 'id',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		]
	],
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', $Env::$groupTable],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT]
			],
			'errorMessage' => 'Invalid Group Id'
		],
		[
			'function' => '_checkColumnValueExist',
			'functionArgs' => [
				'table' => ['custom', $Env::$groupTable],
				'column' => ['custom', 'is_deleted'],
				'columnValue' => ['custom', 'No'],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT],
			],
			'errorMessage' => 'Record is deleted'
		],
		[
			'function' => '_checkColumnValueExist',
			'functionArgs' => [
				'table' => ['custom', $Env::$groupTable],
				'column' => ['custom', 'is_disabled'],
				'columnValue' => ['custom', 'Yes'],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT],
			],
			'errorMessage' => 'Record is already enabled'
		]
	]
];
