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
	'__QUERY__' => "UPDATE `{$Env::$customerTable}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'name',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'name'
		],
		[
			'column' => 'comments',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'comments'
		],
		[
			'column' => 'updated_by',
			'activeRequestDataKey' => 'userData',
			'activeRequestDataKeySubKey' => DatabaseTable::$customerUserPrimaryKey
		],
		[
			'column' => 'updated_on',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => date(format: 'Y-m-d H:i:s')
		]
	],
	'__WHERE__' => [
		[
			'column' => 'is_approved',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$YES
		],
		[
			'column' => 'is_disabled',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
		],
		[
			'column' => 'is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
		],
		[
			'column' => DatabaseTable::$customerPrimaryKey,
			'activeRequestDataKey' => 'routeParamArray',
			'activeRequestDataKeySubKey' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		]
	],
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', Env::$customerTable],
				'primary' => ['custom', DatabaseTable::$customerPrimaryKey],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT]
			],
			'errorMessage' => 'Invalid Customer Id'
		],
	]
];
