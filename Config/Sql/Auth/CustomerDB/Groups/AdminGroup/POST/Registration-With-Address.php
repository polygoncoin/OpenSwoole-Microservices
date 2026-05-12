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
	'__QUERY__' => "INSERT INTO `{$this->http->req->s['cDetail']['usersTable']}` SET __SET__",
	'__SET__' => [
		[
			'column' => 'customer_id',
			'fetchFrom' => 'cDetail',
			'fetchFromDetail' => 'id'
		],
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
		[
			'column' => 'username',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'username'
		],
		[
			'column' => 'password_hash',
			'fetchFrom' => 'function',
			'fetchFromDetail' => function($session): string {
				return password_hash(
					password: $session['payload']['password'],
					algo: PASSWORD_DEFAULT
				);
			}
		],
		[
			'column' => 'allowed_cidr',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => '0.0.0.0/0'
		],
		[
			'column' => 'group_id',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => '1'
		],
	],
	'__INSERT-IDs__' => 'registration:id',
	'__SUB-QUERY__' => [
		'address' => [
			'__QUERY__' => 'INSERT INTO `address` SET __SET__',
			'__SET__' => [
				[
					'column' => 'customer_id',
					'fetchFrom' => 'cDetail',
					'fetchFromDetail' => 'id'
				],
				[
					'column' => 'user_id',
					'fetchFrom' => '__INSERT-IDs__',
					'fetchFromDetail' => 'registration:id'
				],
				[
					'column' => 'address',
					'fetchFrom' => 'payload',
					'fetchFromDetail' => 'address'
				]
			],
			'__INSERT-IDs__' => 'address:id',
		]
	],
	'useHierarchy' => true,
	'idempotentWindow' => 10
];
