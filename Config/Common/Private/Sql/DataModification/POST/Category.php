<?php
namespace Microservices\Config\Common\Private\Sql\DataModification\POST;

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
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'name'
		],
		[
			'column' => 'parent_id',
			'activeDataKey' => 'custom',
			'activeDataKeySubKey' => 0
		],
	],
	'__INSERT-ID__' => 'category:id',
	'__SUB-CONFIG__' => [
		'sub' => [
			'__SQL__' => 'INSERT INTO `category` SET __SET__',
			'__SET__' => [
				[
					'column' => 'name',
					'activeDataKey' => 'payload',
					'activeDataKeySubKey' => 'subname'
				],
				[
					'column' => 'parent_id',
					'activeDataKey' => '__INSERT-ID__',
					'activeDataKeySubKey' => 'category:id'
				],
			],
			'__INSERT-ID__' => 'sub:id',
			'__SUB-CONFIG__' => [
				'subsub' => [
					'__SQL__' => 'INSERT INTO `category` SET __SET__',
					'__SET__' => [
						[
							'column' => 'name',
							'activeDataKey' => 'payload',
							'activeDataKeySubKey' => 'subsubname'
						],
						[
							'column' => 'parent_id',
							'activeDataKey' => '__INSERT-ID__',
							'activeDataKeySubKey' => 'sub:id'
						],
					],
					'__INSERT-ID__' => 'subsub:id',
				]
			]
		]
	],
	'__FOLLOW-SQL_CONFIG-THREAD__' => Constant::$TRUE,
	'__AFFECTED-CACHE-KEY__' => [
		$this->httpObject->httpReqData['active']['customerData'][DatabaseTable::$customerPrimaryKey] . ':category',
		$this->httpObject->httpReqData['active']['customerData'][DatabaseTable::$customerPrimaryKey] . ':category1'
	]
];
