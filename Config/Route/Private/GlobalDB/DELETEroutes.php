<?php

/**
 * API Route config
 * php version 8.3
 * 
 * @category  API_Route_Config
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;

return [
	'group' => [
		'{customer_user_group_id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => $this->httpObject->httpRequestObject->sqlDirectory
				. DIRECTORY_SEPARATOR . 'GlobalDB'
				. DIRECTORY_SEPARATOR . Constant::$DELETE
				. DIRECTORY_SEPARATOR . 'groups.php',
		],
	],
	'customer' => [
		'{customer_id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => $this->httpObject->httpRequestObject->sqlDirectory
				. DIRECTORY_SEPARATOR . 'GlobalDB'
				. DIRECTORY_SEPARATOR . Constant::$DELETE
				. DIRECTORY_SEPARATOR . 'customer.php',
		],
	],
];
