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
	// detail of data to perform task
	'__PAYLOAD__' => [
		// [
		//     'column' => 'id',
		//     'fetchFrom' => 'routeParamArr',
		//     'fetchFromDetail' => 'id',
		//     'dataType' => DatabaseServerDataType::$PrimaryKey,
		//     'isRequired' => $Constant::$REQUIRED
		// ],
		[
			'column' => 'id',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'payload-id-1',
		],
		[
			'column' => 'column-1',
			'fetchFrom' => 'payload',
			'fetchFromDetail' => 'payload-param-1',
		],
	],
	'__FUNCTION__' => 'process',
	'__SUB-PAYLOAD__' => [
		'sub' => [
			'__PAYLOAD__' => [
				[
					'column' => 'sub-id',
					'fetchFrom' => 'payload',
					'fetchFromDetail' => 'sub-payload-id-1',
				],
				[
					'column' => 'sub-column-1',
					'fetchFrom' => 'payload',
					'fetchFromDetail' => 'sub-payload-param-1',
				],
			],
			'__FUNCTION__' => 'processSub',
		]
	],
	'__PRE-SQL-HOOKS__' => [
		'Hook_Example',
	],

	'useHierarchy' => true
];
