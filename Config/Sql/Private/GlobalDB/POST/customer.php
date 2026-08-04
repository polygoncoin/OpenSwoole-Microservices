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
	'__SQL__' => "INSERT INTO `{$Env::$customerTable}` SET __SET__",
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
			'column' => 'created_by',
			'activeRequestDataKey' => 'userData',
			'activeRequestDataKeySubKey' => DatabaseTable::$customerUserPrimaryKey
		],
		[
			'column' => 'created_on',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => date(format: 'Y-m-d H:i:s')
		],
		[
			'column' => 'is_approved',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
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
		]
	],
	'__INSERT-ID__' => 'customer:id',
];
