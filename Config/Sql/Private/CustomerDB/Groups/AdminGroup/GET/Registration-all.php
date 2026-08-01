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
	'countQuery' => "SELECT count(1) as `count` FROM `{$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_table']}` WHERE __WHERE__",
	'__QUERY__' => "SELECT * FROM `{$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_table']}` WHERE __WHERE__",
	'__WHERE__' => [
		[
			'column' => 'customer_user_is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
		]
	],
	'__MODE__' => 'multipleRecordFormat'
];
