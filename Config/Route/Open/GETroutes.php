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

namespace Microservices\Config\Route\Open;

use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;

return [
	'login' => [
		'__FILE__' => Constant::$OPEN_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'Login.php',
	],
	'category' => [
		'__FILE__' => Constant::$OPEN_QUERIES_DIR
			. DIRECTORY_SEPARATOR . 'GET'
			. DIRECTORY_SEPARATOR . 'Category-all.php',
		'search' => [
			'__FILE__' => Constant::$OPEN_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Category-search.php',
		],
		'{id:int}' => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constant::$OPEN_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Category-Single.php',
		],
		'download' => [
			'__FILE__' => Constant::$OPEN_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'GET'
				. DIRECTORY_SEPARATOR . 'Download.php',
		]
	]
];
