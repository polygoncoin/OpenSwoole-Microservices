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

namespace Microservices\Config\Sql\Auth\CustomerDB\Groups\AdminGroup\GET;

return [
	'countQuery' => "SELECT count(1) as `count` FROM `{$this->api->req->usersTable}` WHERE __WHERE__",
	'__QUERY__' => "SELECT * FROM `{$this->api->req->usersTable}` WHERE __WHERE__",
	'__WHERE__' => [
		[
			'column' => 'is_deleted',
			'fetchFrom' => 'custom',
			'fetchFromValue' => 'No'
		]
	],
	'__MODE__' => 'multipleRowFormat'
];
