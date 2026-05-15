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
	'__QUERY__' => 'INSERT INTO `category` SET __SET__',
	'__SET__' => [
		[
			'column' => 'name',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'name'
		],
		[
			'column' => 'parent_id',
			'fetchFrom' => 'custom',
			'fetchFromData' => 0
		],
	],
	'__INSERT-IDs__' => 'category:id',
	'__SUB-QUERY__' => [
		'sub' => [
			'__QUERY__' => 'INSERT INTO `category` SET __SET__',
			'__SET__' => [
				[
					'column' => 'name',
					'fetchFrom' => 'payload',
					'fetchFromData' => 'subname'
				],
				[
					'column' => 'parent_id',
					'fetchFrom' => '__INSERT-IDs__',
					'fetchFromData' => 'category:id'
				],
			],
			'__INSERT-IDs__' => 'sub:id',
			'__SUB-QUERY__' => [
				'subsub' => [
					'__QUERY__' => 'INSERT INTO `category` SET __SET__',
					'__SET__' => [
						[
							'column' => 'name',
							'fetchFrom' => 'payload',
							'fetchFromData' => 'subsubname'
						],
						[
							'column' => 'parent_id',
							'fetchFrom' => '__INSERT-IDs__',
							'fetchFromData' => 'sub:id'
						],
					],
					'__INSERT-IDs__' => 'subsub:id',
				]
			]
		]
	],
	'useHierarchy' => true,
	'affectedCacheKeyArr' => [
		QueryCacheServerKey::category(
			customerId: $this->http->req->customerId,
			groupId: $this->http->req->s['groupData']['id'],
			isAuthRequest: false
		),
		QueryCacheServerKey::category1(
			customerId: $this->http->req->customerId,
			groupId: $this->http->req->s['groupData']['id'],
			isAuthRequest: false
		)
	]
];
