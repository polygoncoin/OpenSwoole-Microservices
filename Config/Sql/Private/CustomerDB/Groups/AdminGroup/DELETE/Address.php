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

use Microservices\App\DatabaseServerDataType;

return array_merge(
	require $Constant::$QUERIES_PRIVATE_DIR
		. DIRECTORY_SEPARATOR . 'CustomerDB'
		. DIRECTORY_SEPARATOR . 'Common'
		. DIRECTORY_SEPARATOR . 'Address.php',
	[
		'__SET__' => [
			[
				'column' => 'is_deleted',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'Yes'
			]
		],
		'__WHERE__' => [
			[
				'column' => 'is_deleted',
				'fetchFrom' => 'custom',
				'fetchFromData' => 'No'
			],
			[
				'column' => 'id',
				'fetchFrom' => 'routeParamArr',
				'fetchFromData' => 'id',
				'dataType' => DatabaseServerDataType::$PrimaryKey
			],
		],
	]
);
