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
	'__SQL__' => "SELECT * FROM `{$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_table']}` WHERE __WHERE__",
	'__WHERE__' => [
		[
			'column' => 'is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
		],
		[
			'column' => DatabaseTable::$customerUserPrimaryKey,
			'activeRequestDataKey' => 'routeParamArray',
			'activeRequestDataKeySubKey' => 'id'
		]
	],
	'__MODE__' => 'multipleRecordFormat',
	'__SUB-CONFIG__' => [
		'address' => [
			'__SQL__' => 'SELECT * FROM `address` WHERE __WHERE__',
			'__WHERE__' => [
				[
					'column' => 'is_deleted',
					'activeRequestDataKey' => 'custom',
					'activeRequestDataKeySubKey' => Constant::$NO
				],
				[
					'column' => DatabaseTable::$customerUserPrimaryKey,
					'activeRequestDataKey' => 'sqlResults',
					'activeRequestDataKeySubKey' => 'return:' . DatabaseTable::$customerUserPrimaryKey
				],
			],
			'__MODE__' => 'multipleRecordFormat',
		]
	],
	'__HIERARCHY__' => Constant::$TRUE
];
