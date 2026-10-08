<?php
namespace Microservices\Config\Common\Private\Sql\DataModification\POST;

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
	'__SQL__' => 'INSERT INTO `address` SET __SET__',
	'__SET__' => [
		[
			'column' => DatabaseTable::$customerPrimaryKey,
			'activeDataKey' => 'customerData',
			'activeDataKeySubKey' => DatabaseTable::$customerPrimaryKey
		],
		[
			'column' => DatabaseTable::$customerUserPrimaryKey,
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'id',
			'dataType' => DatabaseServerDataType::$INT
		],
		[
			'column' => 'address',
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'address'
		],
	],
	'__INSERT-ID__' => 'address:id',
	'__PRIMARY-KEY__' => DatabaseTable::$addressPrimaryKey,
	// '__TRIGGER__' => [
	//     [
	//         '__ROUTE__' => [
	//             [
	//                 'activeDataKey' => 'custom',
	//                 'activeDataKeySubKey' => 'address'
	//             ],
	//             [
	//                 'activeDataKey' => '__INSERT-ID__',
	//                 'activeDataKeySubKey' => 'address:id'
	//             ]
	//         ],
	//         '__QUERY-STRING__' => [
	//             [
	//                 'column' => 'param-1',
	//                 'activeDataKey' => 'custom',
	//                 'activeDataKeySubKey' => 'address'
	//             ],
	//             [
	//                 'column' => 'param-2',
	//                 'activeDataKey' => '__INSERT-ID__',
	//                 'activeDataKeySubKey' => 'address:id'
	//             ]
	//         ],
	//         '__METHOD__' => Constant::$PATCH,
	//         '__PAYLOAD__' => [
	//             [
	//                 'column' => 'address',
	//                 'activeDataKey' => 'custom',
	//                 'activeDataKeySubKey' => 'updated-address'
	//             ]
	//         ]
	//     ]
	// ],
	'__TRANSACTION__' => Constant::$FALSE
];
