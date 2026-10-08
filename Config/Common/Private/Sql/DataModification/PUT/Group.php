<?php
namespace Microservices\Config\Common\Private\Sql\DataModification\PUT;

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
	'__SQL__' => "UPDATE `{$this->httpObject->httpReqData['active']['customerData']['customer_user_group_table']}` SET __SET__ WHERE __WHERE__",
	'__SET__' => [
		[
			'column' => 'name',
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'name'
		],
		[
			'column' => DatabaseTable::$customerPrimaryKey,
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'customer_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'connection_id',
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'connection_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'customer_cidr',
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'allowed_cidr'
		],
		[
			'column' => 'comments',
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'comments'
		],
		[
			'column' => 'updated_by',
			'activeDataKey' => 'customerUserData',
			'activeDataKeySubKey' => DatabaseTable::$customerUserPrimaryKey
		],
		[
			'column' => 'updated_on',
			'activeDataKey' => 'custom',
			'activeDataKeySubKey' => date(format: 'Y-m-d H:i:s')
		]
	],
	'__WHERE__' => [
		[
			'column' => 'is_approved',
			'activeDataKey' => 'custom',
			'activeDataKeySubKey' => Constant::$YES
		],
		[
			'column' => 'is_disabled',
			'activeDataKey' => 'custom',
			'activeDataKeySubKey' => Constant::$NO
		],
		[
			'column' => 'is_deleted',
			'activeDataKey' => 'custom',
			'activeDataKeySubKey' => Constant::$NO
		],
		[
			'column' => DatabaseTable::$customerUserGroupPrimaryKey,
			'activeDataKey' => 'routeParamArray',
			'activeDataKeySubKey' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		]
	],
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', $this->httpObject->httpReqData['active']['customerData']['customer_user_group_table']],
				'primary' => ['custom', DatabaseTable::$customerUserGroupPrimaryKey],
				'id' => ['payload', 'id', DatabaseServerDataType::$INT]
			],
			'errorMessage' => 'Invalid Group Id'
		],
	]
];
