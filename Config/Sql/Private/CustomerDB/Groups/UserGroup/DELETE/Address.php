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

return array_merge(
	require $this->httpObject->httpRequestObject->sqlDirectory
		. DIRECTORY_SEPARATOR . 'CustomerDB'
		. DIRECTORY_SEPARATOR . 'Common'
		. DIRECTORY_SEPARATOR . 'Address.php',
	[
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
				'activeRequestDataKey' => 'routeParamArray',
				'activeRequestDataKeySubKey' => 'id',
				'dataType' => DatabaseServerDataType::$PrimaryKey
			]
		],
	]
);
