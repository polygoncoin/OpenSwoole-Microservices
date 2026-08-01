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
	'__PAYLOAD__' => [
		[
			'column' => 'username',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'username'
		],
		[
			'column' => 'password',
			'activeRequestDataKey' => 'payload',
			'activeRequestDataKeySubKey' => 'password'
		],
	],
	// '__VALIDATE__' => [
	//     [
	//         'function' => 'primaryKeyExist',
	//         'functionArgs' => [
	//             'table' => ['custom', 'address'],
	//             'primary' => ['custom', DatabaseTable::$addressPrimaryKey],
	//             'id' => ['routeParamArray', 'id']
	//         ],
	//         'errorMessage' => 'Invalid address id'
	//     ],
	// ]
];
