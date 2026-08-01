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

use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;
use Microservices\DatabaseTable;

return [
	'__QUERY__' => "INSERT INTO `{$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_table']}` SET __SET__",
	'__SET__' => [
		[
			'column' => 'customer_user_contact_name',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'firstname'
		],
		[
			'column' => 'customer_user_contact_person',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'lastname'
		],
		[
			'column' => 'customer_user_contact_email_address',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'email'
		],
		[
			'column' => 'customer_user_username',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'username'
		],
		[
			'column' => 'customer_user_password_hash',
			'activeRequestDataKey' => 'function',
			'activeRequestDataKeySubKey' => function($activeRequestData) {
				if (
					isset($activeRequestData['payload'])
					&& isset($activeRequestData['payload']['password'])
				) {
					return password_hash(
						password: $activeRequestData['payload']['password'],
						algo: PASSWORD_DEFAULT
					);
				}
			}
		],
		[
			'column' => 'customer_user_allowed_cidr',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => '0.0.0.0/0'
		],
		[
			'column' => DatabaseTable::$customerUserGroupPrimaryKey,
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => '1'
		],
	],
	'__INSERT-IDs__' => 'registration:id',
	'__PRIMARY-KEY__' => DatabaseTable::$customerUserPrimaryKey,
	'__SUB-QUERY__' => [
		'address' => [
			'__QUERY__' => 'INSERT INTO `address` SET __SET__',
			'__SET__' => [
				[
					'column' => DatabaseTable::$customerPrimaryKey,
					'activeRequestDataKey' => 'customerData',
					'activeRequestDataKeySubKey' => DatabaseTable::$customerPrimaryKey
				],
				[
					'column' => DatabaseTable::$customerUserPrimaryKey,
					'activeRequestDataKey' => '__INSERT-IDs__',
					'activeRequestDataKeySubKey' => 'registration:id'
				],
				[
					'column' => 'address',
					'activeRequestDataKey' => 'payload',
					'activeRequestDataKeySubKey' => 'address'
				]
			],
			'__INSERT-IDs__' => 'address:id',
			'__PRIMARY-KEY__' => DatabaseTable::$addressPrimaryKey,
			'__PAYLOAD-TYPE__' => 'Array',
			'__MAX-PAYLOAD-OBJECTS__' => 2
		]
	],
	'maintainHierarchy' => Constant::$TRUE,
	'__PAYLOAD-TYPE__' => 'Object',
	'idempotentWindow' => 10
];
