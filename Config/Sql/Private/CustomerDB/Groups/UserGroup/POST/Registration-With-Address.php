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

return [
	'__QUERY__' => "INSERT INTO `{$this->http->req->s['customerData']['userTable']}` SET __SET__",
	'__SET__' => [
		[
			'column' => 'customer_id',
			'fetchFrom' => 'customerData',
			'fetchFromData' => 'id'
		],
		[
			'column' => 'firstname',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'firstname'
		],
		[
			'column' => 'lastname',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'lastname'
		],
		[
			'column' => 'email',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'email'
		],
		[
			'column' => 'username',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'username'
		],
		[
			'column' => 'password_hash',
			'fetchFrom' => 'function',
			'fetchFromData' => function($session) {
				if (
					isset($session['payload'])
					&& isset($session['payload']['password'])
				) {
					return password_hash(
						password: $session['payload']['password'],
						algo: PASSWORD_DEFAULT
					);
				}
			}
		],
		[
			'column' => 'allowed_cidr',
			'fetchFrom' => 'custom',
			'fetchFromData' => '0.0.0.0/0'
		],
		[
			'column' => 'group_id',
			'fetchFrom' => 'custom',
			'fetchFromData' => '1'
		],
	],
	'__INSERT-IDs__' => 'registration:id',
	'__SUB-QUERY__' => [
		'address' => [
			'__QUERY__' => 'INSERT INTO `address` SET __SET__',
			'__SET__' => [
				[
					'column' => 'customer_id',
					'fetchFrom' => 'customerData',
					'fetchFromData' => 'id'
				],
				[
					'column' => 'user_id',
					'fetchFrom' => '__INSERT-IDs__',
					'fetchFromData' => 'registration:id'
				],
				[
					'column' => 'address',
					'fetchFrom' => 'payload',
					'fetchFromData' => 'address'
				]
			],
			'__INSERT-IDs__' => 'address:id',
			'__PAYLOAD-TYPE__' => 'Array',
			'__MAX-PAYLOAD-OBJECTS__' => 2
		]
	],
	'useHierarchy' => true,
	'__PAYLOAD-TYPE__' => 'Object',
	'idempotentWindow' => 10
];
