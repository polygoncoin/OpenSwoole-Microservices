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

return [
	'__QUERY__' => "UPDATE `{$this->http->req->s['customerData']['usersTable']}` SET __SET__ WHERE __WHERE__",
	'__VALIDATE__' => [
		[
			'function' => 'primaryKeyExist',
			'functionArgs' => [
				'table' => ['custom', $this->http->req->s['customerData']['usersTable']],
				'primary' => ['custom', 'id'],
				'id' => ['routeParamArr', 'id']
			],
			'errorMessage' => 'Invalid registration id'
		],
	]
];
