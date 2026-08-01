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
	'__QUERY__' => 'INSERT INTO `category` SET __SET__',
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
	'__INSERT-IDs__' => 'category:id',
	'__SUB-QUERY__' => [
		'sub' => [
			'__QUERY__' => 'INSERT INTO `category` SET __SET__',
			'__SET__' => [
				[
					'column' => 'name',
					'activeRequestDataKey' => 'payload',
					'activeRequestDataKeySubKey' => 'subname'
				],
				[
					'column' => 'parent_id',
					'activeRequestDataKey' => '__INSERT-IDs__',
					'activeRequestDataKeySubKey' => 'category:id'
				],
			],
			'__INSERT-IDs__' => 'sub:id',
			'__SUB-QUERY__' => [
				'subsub' => [
					'__QUERY__' => 'INSERT INTO `category` SET __SET__',
					'__SET__' => [
						[
							'column' => 'name',
							'activeRequestDataKey' => 'payload',
							'activeRequestDataKeySubKey' => 'subsubname'
						],
						[
							'column' => 'parent_id',
							'activeRequestDataKey' => '__INSERT-IDs__',
							'activeRequestDataKeySubKey' => 'sub:id'
						],
					],
					'__INSERT-IDs__' => 'subsub:id',
				]
			]
		]
	],
	'maintainHierarchy' => Constant::$TRUE,
	'affectedQueryCacheKeyArray' => [
		$this->httpObject->httpRequestObject->activeRequestData['customerData'][DatabaseTable::$customerPrimaryKey] . ':category',
		$this->httpObject->httpRequestObject->activeRequestData['customerData'][DatabaseTable::$customerPrimaryKey] . ':category1'
	]
];
