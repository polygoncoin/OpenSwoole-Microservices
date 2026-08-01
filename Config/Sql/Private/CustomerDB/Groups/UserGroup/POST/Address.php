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

use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;
use Microservices\DatabaseTable;

return [
	'__QUERY__' => 'INSERT INTO `address` SET __SET__',
	'__SET__' => [
		[
			'column' => DatabaseTable::$customerPrimaryKey,
			'activeRequestDataKey' => 'customerData',
			'activeRequestDataKeySubKey' => DatabaseTable::$customerPrimaryKey
		],
		[
			'column' => DatabaseTable::$customerUserPrimaryKey,
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'address',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'address'
		],
	],
	'__INSERT-IDs__' => 'address:id',
	'__PRIMARY-KEY__' => DatabaseTable::$addressPrimaryKey,
	// '__TRIGGERS__' => [
	//     [
	//         '__ROUTE__' => [
	//             [
	//                 'activeRequestDataKey' => 'custom',
	//                 'activeRequestDataKeySubKey' => 'address'
	//             ],
	//             [
	//                 'activeRequestDataKey' => '__INSERT-IDs__',
	//                 'activeRequestDataKeySubKey' => 'address:id'
	//             ]
	//         ],
	//         '__QUERY-STRING__' => [
	//             [
	//                 'column' => 'param-1',
	//                 'activeRequestDataKey' => 'custom',
	//                 'activeRequestDataKeySubKey' => 'address'
	//             ],
	//             [
	//                 'column' => 'param-2',
	//                 'activeRequestDataKey' => '__INSERT-IDs__',
	//                 'activeRequestDataKeySubKey' => 'address:id'
	//             ]
	//         ],
	//         '__METHOD__' => Constant::$PATCH,
	//         '__PAYLOAD__' => [
	//             [
	//                 'column' => 'address',
	//                 'activeRequestDataKey' => 'custom',
	//                 'activeRequestDataKeySubKey' => 'updated-address'
	//             ]
	//         ]
	//     ]
	// ],
	'isTransaction' => Constant::$FALSE
];
