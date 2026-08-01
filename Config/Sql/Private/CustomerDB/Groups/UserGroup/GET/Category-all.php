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
	'countQuery' => 'SELECT count(1) as `count` FROM `category` WHERE __WHERE__',
	'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
	'__WHERE__' => [
		[
			'column' => 'is_deleted',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => Constant::$NO
		],
		[
			'column' => 'parent_id',
			'activeRequestDataKey' => 'custom',
			'activeRequestDataKeySubKey' => 0
		]
	],
	'__MODE__' => 'multipleRecordFormat',
	'__SUB-QUERY__' => [
		'sub' => [
			'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
			'__WHERE__' => [
				[
					'column' => 'is_deleted',
					'activeRequestDataKey' => 'custom',
					'activeRequestDataKeySubKey' => Constant::$NO
				],
				[
					'column' => 'parent_id',
					'activeRequestDataKey' => 'sqlResults',
					'activeRequestDataKeySubKey' => 'return:' . DatabaseTable::$categoryPrimaryKey
				],
			],
			'__MODE__' => 'multipleRecordFormat',
			'__SUB-QUERY__' => [
				'subsub' => [
					'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
					'__WHERE__' => [
						[
							'column' => 'is_deleted',
							'activeRequestDataKey' => 'custom',
							'activeRequestDataKeySubKey' => Constant::$NO
						],
						[
							'column' => 'parent_id',
							'activeRequestDataKey' => 'sqlResults',
							'activeRequestDataKeySubKey' => 'return:sub:' . DatabaseTable::$categoryPrimaryKey
						],
					],
					'__MODE__' => 'multipleRecordFormat',
					'__SUB-QUERY__' => [
						'subsubsub' => [
							'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
							'__WHERE__' => [
								[
									'column' => 'is_deleted',
									'activeRequestDataKey' => 'custom',
									'activeRequestDataKeySubKey' => Constant::$NO
								],
								[
									'column' => 'parent_id',
									'activeRequestDataKey' => 'sqlResults',
									'activeRequestDataKeySubKey' => 'return:sub:subsub:' . DatabaseTable::$categoryPrimaryKey
								],
							],
							'__MODE__' => 'multipleRecordFormat',
						]
					]
				]
			],
		]
	],
	'maintainHierarchy' => Constant::$TRUE,
	'fetchDbMode' => 'Master',
	// 'queryCacheKey' => $this->httpObject->httpRequestObject->activeRequestData['customerData'][DatabaseTable::$customerPrimaryKey] . ':category',
	'outputRepresentation' => 'PHP',
	'phpFile' => $this->httpObject->httpResponseObject->phpDirectory . DIRECTORY_SEPARATOR . 'index.php'
];
