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
	'__SQL__' => 'INSERT INTO `category` SET __SET__',
	'__SET__' => [
		[
			'column' => 'name',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'name'
		],
		[
			'column' => 'parent_id',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => 0
		],
	],
	'__INSERT-ID__' => 'category:id',
	'__SUB-CONFIG__' => [
		'sub' => [
			'__SQL__' => 'INSERT INTO `category` SET __SET__',
			'__SET__' => [
				[
					'column' => 'name',
					'activeRequestDataKey' => 'payload',
					'activeRequestDataKeySubKey' => 'subname'
				],
				[
					'column' => 'parent_id',
					'activeRequestDataKey' => '__INSERT-ID__',
					'activeRequestDataKeySubKey' => 'category:id'
				],
			],
			'__INSERT-ID__' => 'sub:id',
			'__SUB-CONFIG__' => [
				'subsub' => [
					'__SQL__' => 'INSERT INTO `category` SET __SET__',
					'__SET__' => [
						[
							'column' => 'name',
							'activeRequestDataKey' => 'payload',
							'activeRequestDataKeySubKey' => 'subsubname'
						],
						[
							'column' => 'parent_id',
							'activeRequestDataKey' => '__INSERT-ID__',
							'activeRequestDataKeySubKey' => 'sub:id'
						],
					],
					'__INSERT-ID__' => 'subsub:id',
				]
			]
		]
	],
	'__HIERARCHY__' => Constant::$TRUE,
	'__AFFECTED-CACHE-KEY__' => [
		$this->httpObject->httpRequestObject->activeRequestData['customerData'][DatabaseTable::$customerPrimaryKey] . ':category',
		$this->httpObject->httpRequestObject->activeRequestData['customerData'][DatabaseTable::$customerPrimaryKey] . ':category1'
	]
];
