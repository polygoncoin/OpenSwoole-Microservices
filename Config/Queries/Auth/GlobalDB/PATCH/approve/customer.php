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

namespace Microservices\Config\Queries\Auth\GlobalDB\PATCH\approve;

use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
	'__QUERY__' => "UPDATE `{$Env::$customerTable}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'is_approved',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'Yes'
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
			'fetchFromValue' => 'No'
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
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'id',
			'dataType' => DatabaseDataTypes::$INT
		]
	],
	'__VALIDATE__' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
				'table' => ['custom', Env::$customerTable],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseDataTypes::$INT]
			],
			'errorMessage' => 'Invalid Customer Id'
		],
		[
			'fn' => '_checkColumnValueExist',
			'fnArgs' => [
				'table' => ['custom', Env::$customerTable],
				'column' => ['custom', 'is_deleted'],
				'columnValue' => ['custom', 'No'],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseDataTypes::$INT],
			],
			'errorMessage' => 'Record is deleted'
		],
		[
			'fn' => '_checkColumnValueExist',
			'fnArgs' => [
				'table' => ['custom', Env::$customerTable],
				'column' => ['custom', 'is_approved'],
				'columnValue' => ['custom', 'No'],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseDataTypes::$INT],
			],
			'errorMessage' => 'Record is already approved'
		]
	]
];
