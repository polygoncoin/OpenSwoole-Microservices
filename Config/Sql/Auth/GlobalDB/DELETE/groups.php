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
	'__QUERY__' => "UPDATE `{$Env::$groupsTable}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => 'Yes'
		],
		[
			'column' => 'updated_by',
			'fetchFrom' => 'uDetail',
			'fetchFromDetail' => 'id'
		],
		[
			'column' => 'updated_on',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => date(format: 'Y-m-d H:i:s')
		]
	],
	'__WHERE__' => [
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => 'No'
		],
		[
			'column' => 'id',
			'fetchFrom' => 'routeParamArr',
			'fetchFromDetail' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		]
	],
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', $Env::$groupsTable],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT]
			],
			'errorMessage' => 'Invalid Group id'
		],
		[
			'function' => '_checkColumnValueExist',
			'functionArgs' => [
				'table' => ['custom', $Env::$groupsTable],
				'column' => ['custom', 'is_deleted'],
				'columnValue' => ['custom', 'No'],
				'primary' => ['custom', 'id'],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT],
			],
			'errorMessage' => 'Record is already deleted'
		]
	]
];
