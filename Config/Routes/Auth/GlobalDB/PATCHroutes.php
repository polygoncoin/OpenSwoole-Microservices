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

namespace Microservices\Config\Routes\Auth\GlobalDB;

use Microservices\App\Constants;
use Microservices\App\DatabaseServerDataType;

return [
	'group' => [
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constants::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'GlobalDB'
				. DIRECTORY_SEPARATOR . 'PATCH'
				. DIRECTORY_SEPARATOR . 'groups.php',
			'approve'  => [
				'__FILE__' => Constants::$AUTH_QUERIES_DIR
					. DIRECTORY_SEPARATOR . 'GlobalDB'
					. DIRECTORY_SEPARATOR . 'PATCH'
					. DIRECTORY_SEPARATOR . 'approve'
					. DIRECTORY_SEPARATOR . 'groups.php',
			],
			'disable'  => [
				'__FILE__' => Constants::$AUTH_QUERIES_DIR
					. DIRECTORY_SEPARATOR . 'GlobalDB'
					. DIRECTORY_SEPARATOR . 'PATCH'
					. DIRECTORY_SEPARATOR . 'disable'
					. DIRECTORY_SEPARATOR . 'groups.php',
			],
			'enable'  => [
				'__FILE__' => Constants::$AUTH_QUERIES_DIR
					. DIRECTORY_SEPARATOR . 'GlobalDB'
					. DIRECTORY_SEPARATOR . 'PATCH'
					. DIRECTORY_SEPARATOR . 'enable'
					. DIRECTORY_SEPARATOR . 'groups.php',
			],
		],
	],
	'customer' => [
		'{id:int}'  => [
			'dataType' => DatabaseServerDataType::$PrimaryKey,
			'__FILE__' => Constants::$AUTH_QUERIES_DIR
				. DIRECTORY_SEPARATOR . 'GlobalDB'
				. DIRECTORY_SEPARATOR . 'PATCH'
				. DIRECTORY_SEPARATOR . 'customer.php',
			'approve'  => [
				'__FILE__' => Constants::$AUTH_QUERIES_DIR
					. DIRECTORY_SEPARATOR . 'GlobalDB'
					. DIRECTORY_SEPARATOR . 'PATCH'
					. DIRECTORY_SEPARATOR . 'approve'
					. DIRECTORY_SEPARATOR . 'customer.php',
			],
			'disable'  => [
				'__FILE__' => Constants::$AUTH_QUERIES_DIR
					. DIRECTORY_SEPARATOR . 'GlobalDB'
					. DIRECTORY_SEPARATOR . 'PATCH'
					. DIRECTORY_SEPARATOR . 'disable'
					. DIRECTORY_SEPARATOR . 'customer.php',
			],
			'enable'  => [
				'__FILE__' => Constants::$AUTH_QUERIES_DIR
					. DIRECTORY_SEPARATOR . 'GlobalDB'
					. DIRECTORY_SEPARATOR . 'PATCH'
					. DIRECTORY_SEPARATOR . 'enable'
					. DIRECTORY_SEPARATOR . 'customer.php',
			],
		],
	],
];
