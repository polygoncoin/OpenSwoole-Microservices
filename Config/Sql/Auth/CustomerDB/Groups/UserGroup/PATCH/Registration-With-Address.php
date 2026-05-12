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
	'__QUERY__' => "UPDATE `{$this->http->req->s['cDetail']['usersTable']}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'firstname',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'firstname'
		],
		[
			'column' => 'lastname',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'lastname'
		],
		[
			'column' => 'email',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'email'
		],
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
			'dataType' => DatabaseServerDataType::$PrimaryKey
		]
	],
	'__SUB-QUERY__' => [
		'address' => [
			'__QUERY__' => 'UPDATE `address` SET __SET__ WHERE __WHERE__',
			'__SET__' => [
				[
					'column' => 'address',
					'fetchFrom' => 'payload',
					'fetchFromDetail' => 'address'
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
					'fetchFrom' => 'payload',
					'fetchFromDetail' => 'id',
					'dataType' => DatabaseServerDataType::$PrimaryKey
				],
			],
		]
	],
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', $this->http->req->s['cDetail']['usersTable']],
				'primary' => ['custom', 'id'],
				'id' => ['routeParamArr', 'id']
			],
			'errorMessage' => 'Invalid registration id'
		],
	],
	'useHierarchy' => true,
	'idempotentWindow' => 10
];
