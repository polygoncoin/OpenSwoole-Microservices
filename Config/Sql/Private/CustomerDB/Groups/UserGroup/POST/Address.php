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

use Microservices\App\DatabaseServerDataType;

return [
	'__QUERY__' => 'INSERT INTO `address` SET __SET__',
	'__SET__' => [
		[
			'column' => 'customer_id',
			'fetchFrom' => 'customerData',
			'fetchFromData' => 'id'
		],
		[
			'column' => 'user_id',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'user_id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'address',
			'fetchFrom' => 'payload',
			'fetchFromData' => 'address'
		],
	],
	'__INSERT-IDs__' => 'address:id',
	// '__TRIGGERS__' => [
	//     [
	//         '__ROUTE__' => [
	//             [
	//                 'fetchFrom' => 'custom',
	//                 'fetchFromData' => 'address'
	//             ],
	//             [
	//                 'fetchFrom' => '__INSERT-IDs__',
	//                 'fetchFromData' => 'address:id'
	//             ]
	//         ],
	//         '__QUERY-STRING__' => [
	//             [
	//                 'column' => 'param-1',
	//                 'fetchFrom' => 'custom',
	//                 'fetchFromData' => 'address'
	//             ],
	//             [
	//                 'column' => 'param-2',
	//                 'fetchFrom' => '__INSERT-IDs__',
	//                 'fetchFromData' => 'address:id'
	//             ]
	//         ],
	//         '__METHOD__' => 'PATCH',
	//         '__PAYLOAD__' => [
	//             [
	//                 'column' => 'address',
	//                 'fetchFrom' => 'custom',
	//                 'fetchFromData' => 'updated-address'
	//             ]
	//         ]
	//     ]
	// ],
	'isTransaction' => false
];
