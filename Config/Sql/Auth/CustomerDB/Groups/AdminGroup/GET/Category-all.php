<?php

/**
 * API Query config
 * php version 8.3
 *
 * @category  API_Query_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

use Microservices\App\QueryCacheServerKey;

return [
	'countQuery' => 'SELECT count(1) as `count` FROM `category` WHERE __WHERE__',
	'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
	'__WHERE__' => [
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => 'No'
		],
		[
			'column' => 'parent_id',
			'fetchFrom' => 'custom',
			'fetchFromDetail' => 0
		],
	],
	'__MODE__' => 'multipleRowFormat',
	'__SUB-QUERY__' => [
		'sub' => [
			'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
			'__WHERE__' => [
				[
					'column' => 'is_deleted',
					'fetchFrom' => 'custom',
					'fetchFromDetail' => 'No'
				],
				[
					'column' => 'parent_id',
					'fetchFrom' => 'sqlResults',
					'fetchFromDetail' => 'return:id'
				],
			],
			'__MODE__' => 'multipleRowFormat',
			'__SUB-QUERY__' => [
				'subsub' => [
					'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
					'__WHERE__' => [
						[
							'column' => 'is_deleted',
							'fetchFrom' => 'custom',
							'fetchFromDetail' => 'No'
						],
						[
							'column' => 'parent_id',
							'fetchFrom' => 'sqlResults',
							'fetchFromDetail' => 'return:sub:id'
						],
					],
					'__MODE__' => 'multipleRowFormat',
					'__SUB-QUERY__' => [
						'subsubsub' => [
							'__QUERY__' => 'SELECT * FROM `category` WHERE __WHERE__',
							'__WHERE__' => [
								[
									'column' => 'is_deleted',
									'fetchFrom' => 'custom',
									'fetchFromDetail' => 'No'
								],
								[
									'column' => 'parent_id',
									'fetchFrom' => 'sqlResults',
									'fetchFromDetail' => 'return:sub:subsub:id'
								],
							],
							'__MODE__' => 'multipleRowFormat',
						]
					]
				]
			],
		]
	],
	'useResultSet' => true,
	'fetchFrom' => 'Master',
	'cacheKey' => QueryCacheServerKey::category(
		customerID: $this->http->req->cID,
		groupID: $this->http->req->s['gDetail']['id'],
		isOpenToWebRequest: false
	)
];
