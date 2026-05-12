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
	'__PAYLOAD__' => [
		[
			'column' => 'username',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'username'
		],
		[
			'column' => 'password',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'password'
		],
	],
	// '__VALIDATE__' => [
	//     [
	//         'function' => 'primaryKeyExist',
	//         'functionArgs' => [
	//             'table' => ['custom', 'address'],
	//             'primary' => ['custom', 'id'],
	//             'id' => ['routeParamArr', 'id']
	//         ],
	//         'errorMessage' => 'Invalid address id'
	//     ],
	// ]
];
