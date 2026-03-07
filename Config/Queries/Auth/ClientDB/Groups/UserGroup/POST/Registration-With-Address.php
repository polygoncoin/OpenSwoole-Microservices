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

namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\POST;

return [
	'__QUERY__' => "INSERT INTO `{$this->api->req->usersTable}` SET __SET__",
	'__SET__' => [
		[
			'column' => 'client_id',
			'fetchFrom' => 'cDetails',
			'fetchFromValue' => 'id'
		],
		[
			'column' => 'firstname',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'firstname'
		],
		[
			'column' => 'lastname',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'lastname'
		],
		[
			'column' => 'email',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'email'
		],
		[
			'column' => 'username',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'username'
		],
		[
			'column' => 'password_hash',
			'fetchFrom' => 'function',
			'fetchFromValue' => function($session): string {
				return password_hash(
					password: $session['payload']['password'],
					algo: PASSWORD_DEFAULT
				);
			}
		],
		[
			'column' => 'allowed_cidr',
			'fetchFrom' => 'custom',
			'fetchFromValue' => '0.0.0.0/0'
		],
		[
			'column' => 'group_id',
			'fetchFrom' => 'custom',
			'fetchFromValue' => '1'
		],
	],
	'__INSERT-IDs__' => 'registration:id',
	'__SUB-QUERY__' => [
		'address' => [
			'__QUERY__' => 'INSERT INTO `address` SET __SET__',
			'__SET__' => [
				[
					'column' => 'client_id',
					'fetchFrom' => 'cDetails',
					'fetchFromValue' => 'id'
				],
				[
					'column' => 'user_id',
					'fetchFrom' => '__INSERT-IDs__',
					'fetchFromValue' => 'registration:id'
				],
				[
					'column' => 'address',
					'fetchFrom' => 'payload',
					'fetchFromValue' => 'address'
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
