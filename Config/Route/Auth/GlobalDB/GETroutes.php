<?php

/**
 * API Route config
 * php version 8.3
 *
 * @category  API_Route_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Config\Route\Auth\GlobalDB;

use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;

return [
	'group' => [
		'__FILE__' => Constant::$AUTH_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'GlobalDB'
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'groups.php',
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constant::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'GlobalDB'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'groups.php',
		],
	],
	'customer' => [
		'__FILE__' => Constant::$AUTH_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'GlobalDB'
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'customer.php',
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constant::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'GlobalDB'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'customer.php',
		],
	]
];
