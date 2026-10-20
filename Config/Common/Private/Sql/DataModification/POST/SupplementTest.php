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
	// detail of data to perform task
	'__PAYLOAD__' => [
		[
			'column' => 'id',
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'payload-id-1',
		],
		[
			'column' => 'column-1',
			'activeDataKey' => 'payload',
			'activeDataKeySubKey' => 'payload-param-1',
		],
	],
	'__SUB-CONFIG__' => [
		'sub' => [
			'__PAYLOAD__' => [
				[
					'column' => 'sub-id',
					'activeDataKey' => 'payload',
					'activeDataKeySubKey' => 'sub-payload-id-1',
				],
				[
					'column' => 'sub-column-1',
					'activeDataKey' => 'payload',
					'activeDataKeySubKey' => 'sub-payload-param-1',
				],
			],
		]
	],
	'__PRE-CONFIG-HOOK__' => [
		'Hook_Example',
	],

	'__FOLLOW-SQL_CONFIG-THREAD__' => Constant::$TRUE
];
