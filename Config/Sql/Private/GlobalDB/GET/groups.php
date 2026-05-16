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

return [
	'all' => [
		'__QUERY__' => "SELECT * FROM `{$Env::$groupTable}` WHERE __WHERE__ ORDER BY id ASC",
		'__WHERE__' => [
			[
				'column' => 'is_approved',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'Yes'
			],
			[
				'column' => 'is_disabled',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'No'
			],
			[
				'column' => 'is_deleted',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'No'
			],
		],
		'__MODE__' => 'multipleRowFormat'
	],
	'single' => [
		'__QUERY__' => "SELECT * FROM `{$Env::$groupTable}` WHERE __WHERE__",
		'__WHERE__' => [
			[
				'column' => 'is_approved',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'Yes'
			],
			[
				'column' => 'is_disabled',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'No'
			],
			[
				'column' => 'is_deleted',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'No'
			],
			[
				'column' => 'id',
				'fetchFrom' => 'routeParamArr',
				'fetchFromData' => 'id'
			],
		],
		'__MODE__' => 'singleRowFormat'
	]
][isset($this->http->req->s['routeParamArr']['id'])?'single':'all'];
