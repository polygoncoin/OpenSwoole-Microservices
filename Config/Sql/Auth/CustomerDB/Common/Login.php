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

namespace Microservices\Config\Sql\Auth\CustomerDB\Common;

return [
	'__PAYLOAD__' => [
		[
			'column' => 'username',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'username'
		],
		[
			'column' => 'password',
			'fetchFrom' => 'payload',
			'fetchFromValue' => 'password'
		],
	],
	// '__VALIDATE__' => [
	//     [
	//         'fn' => 'primaryKeyExist',
	//         'fnArgs' => [
	//             'table' => ['custom', 'address'],
	//             'primary' => ['custom', 'id'],
	//             'id' => ['routeParams', 'id']
	//         ],
	//         'errorMessage' => 'Invalid address id'
	//     ],
	// ]
];
