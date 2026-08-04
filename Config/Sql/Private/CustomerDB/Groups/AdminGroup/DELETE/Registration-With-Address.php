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
	'__SQL__' => "UPDATE `{$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_table']}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'customer_user_is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$YES
		]
	],
	'__WHERE__' => [
		[
			'column' => 'customer_user_is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
		],
		[
			'column' => DatabaseTable::$customerUserPrimaryKey,
			'activeRequestDataKey' => 'routeParamArray',
			'activeRequestDataKeySubKey' => 'id',
			'dataType' => DatabaseServerDataType::$PrimaryKey
		],
	],
	'__SUB-CONFIG__' => [
		'address' => [
			'__SQL__' => 'UPDATE `address` SET __SET__ WHERE __WHERE__',
			'__SET__' => [
				[
					'column' => 'is_deleted',
					'activeRequestDataKey' => 'custom',
					'activeRequestDataKeySubKey' => Constant::$YES
				]
			],
			'__WHERE__' => [
				[
					'column' => 'is_deleted',
					'activeRequestDataKey' => 'custom',
					'activeRequestDataKeySubKey' => Constant::$NO
				],
				[
					'column' => DatabaseTable::$addressPrimaryKey,
					'activeRequestDataKey' => 'payload',
					'activeRequestDataKeySubKey' => 'id',
					'dataType' => DatabaseServerDataType::$PrimaryKey
				],
				[
					'column' => DatabaseTable::$customerUserPrimaryKey,
					'activeRequestDataKey' => 'routeParamArray',
					'activeRequestDataKeySubKey' => 'id',
					'dataType' => DatabaseServerDataType::$PrimaryKey
				],
			],
		]
	],
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_table']],
				'primary' => ['custom', DatabaseTable::$customerUserPrimaryKey],
				'id' => ['routeParamArray', 'id']
			],
			'errorMessage' => 'Invalid registration id'
		],
	],
	'__HIERARCHY__' => Constant::$TRUE,
	'idempotentWindow' => 10
];
