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
	// detail of data to perform task
	'__PAYLOAD__' => [
		[
			'column' => 'id',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'payload-id-1',
		],
		[
			'column' => 'column-1',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'payload-param-1',
		],
	],
	'__SUB-PAYLOAD__' => [
		'sub' => [
			'__PAYLOAD__' => [
				[
					'column' => 'sub-id',
					'activeRequestDataKey' => 'payload',
					'activeRequestDataKeySubKey' => 'sub-payload-id-1',
				],
				[
					'column' => 'sub-column-1',
					'activeRequestDataKey' => 'payload',
					'activeRequestDataKeySubKey' => 'sub-payload-param-1',
				],
			],
		]
	],
	'__PRE-SQL-HOOKS__' => [
		'Hook_Example',
	],

	'maintainHierarchy' => Constant::$TRUE
];
