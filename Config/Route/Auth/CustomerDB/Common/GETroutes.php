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

namespace Microservices\Config\Route\Auth\CustomerDB\Common;

use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;

return [
	'category' => [
		'__FILE__' => Constant::$AUTH_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'CustomerDB'
			. DIRECTORY_SEPARATOR . 'Groups'
			. DIRECTORY_SEPARATOR . 'UserGroup'
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'Category-all.php',
		'search' => [
			'__FILE__' => Constant::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'UserGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'SearchCategory.php',
		],
		'{id:int}' => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constant::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'UserGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Category-single.php',
		]
	],
	'registration' => [
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constant::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'UserGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Registration-single.php',
		],
	],
	'address' => [
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constant::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'UserGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Address-single.php',
		],
	],
	'registration-with-address' => [
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constant::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'UserGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Registration-With-Address-single.php',
		],
	],
	Env::$routesRequestRoute => [
		'__FILE__' => false,
		'{method:string}' => [
			'dataType' => DatabaseServerDataType::$HttpMethods,
			'__FILE__' => false
		]
	]
];
