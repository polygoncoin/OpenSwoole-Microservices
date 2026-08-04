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
	'__SQL__' => "UPDATE `{$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_group_table']}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$YES
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
			'column' => 'is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
		],
		[
			'column' => DatabaseTable::$customerUserGroupPrimaryKey,
			'activeRequestDataKey' => 'routeParamArray',
			'activeRequestDataKeySubKey' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		]
	],
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_group_table']],
				'primary' => ['custom', DatabaseTable::$customerUserGroupPrimaryKey],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT]
			],
			'errorMessage' => 'Invalid Group Id'
		],
		[
			'function' => '_checkColumnValueExist',
			'functionArgs' => [
				'table' => ['custom', $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_group_table']],
				'column' => ['custom', 'is_deleted'],
				'columnValue' => ['custom', Constant::$NO],
				'primary' => ['custom', DatabaseTable::$customerUserGroupPrimaryKey],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT],
			],
			'errorMessage' => 'Record is already deleted'
		]
	]
];
