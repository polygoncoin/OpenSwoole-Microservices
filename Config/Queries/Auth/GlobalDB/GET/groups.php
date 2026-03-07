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

namespace Microservices\Config\Queries\Auth\GlobalDB\GET;

use Microservices\App\Common;

return [
	'all' => [
		'__QUERY__' => "SELECT * FROM `{$Env::$groupsTable}` WHERE __WHERE__ ORDER BY id ASC",
		'__WHERE__' => [
			[
				'column' => 'is_approved',
				'fetchFrom' => 'custom',
				'fetchFromValue' => 'Yes'
			],
			[
				'column' => 'is_disabled',
				'fetchFrom' => 'custom',
				'fetchFromValue' => 'No'
			],
			[
				'column' => 'is_deleted',
				'fetchFrom' => 'custom',
				'fetchFromValue' => 'No'
			],
		],
		'__MODE__' => 'multipleRowFormat'
	],
	'single' => [
		'__QUERY__' => "SELECT * FROM `{$Env::$groupsTable}` WHERE __WHERE__",
		'__WHERE__' => [
			[
				'column' => 'is_approved',
				'fetchFrom' => 'custom',
				'fetchFromValue' => 'Yes'
			],
			[
				'column' => 'is_disabled',
				'fetchFrom' => 'custom',
				'fetchFromValue' => 'No'
			],
			[
				'column' => 'is_deleted',
				'fetchFrom' => 'custom',
				'fetchFromValue' => 'No'
			],
			[
				'column' => 'id',
				'fetchFrom' => 'routeParams',
				'fetchFromValue' => 'id'
			],
		],
		'__MODE__' => 'singleRowFormat'
	]
][isset($this->api->req->s['routeParams']['id'])?'single':'all'];
