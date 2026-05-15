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

use Microservices\App\DatabaseServerDataType;

return [
	'category' => [
		'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'CustomerDB'
			. DIRECTORY_SEPARATOR . 'Groups'
			. DIRECTORY_SEPARATOR . 'AdminGroup'
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'Category-all.php',
		'search' => [
			'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'AdminGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Category-search.php',
		],
		'{id:int}' => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'AdminGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Category-Single.php',
		]
	],
	'registration' => [
		'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'CustomerDB'
			. DIRECTORY_SEPARATOR . 'Groups'
			. DIRECTORY_SEPARATOR . 'AdminGroup'
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'Registration-all.php',
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'AdminGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Registration-single.php',
		],
	],
	'address' => [
		'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'CustomerDB'
			. DIRECTORY_SEPARATOR . 'Groups'
			. DIRECTORY_SEPARATOR . 'AdminGroup'
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'Address-all.php',
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'AdminGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Address-single.php',
		],
	],
	'registration-with-address' => [
		'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'CustomerDB'
			. DIRECTORY_SEPARATOR . 'Groups'
			. DIRECTORY_SEPARATOR . 'AdminGroup'
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'Registration-With-Address-all.php',
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => $Constant::$PRIVATE_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . 'AdminGroup'
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Registration-With-Address-single.php',
		],
	],
	$Env::$routesRequestRoute => [
		'__FILE__' => false,
		'{method:string}' => [
			'dataType' => DatabaseServerDataType::$HttpMethod,
			'__FILE__' => false
		]
	]
];
