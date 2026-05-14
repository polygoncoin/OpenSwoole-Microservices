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
	require $Constant::$AUTH_QUERIES_DIR
		. DIRECTORY_SEPARATOR . 'CustomerDB'
		. DIRECTORY_SEPARATOR . 'Common'
		. DIRECTORY_SEPARATOR . 'Registration.php',
	[
		'__SET__' => [
			[
				'column' => 'firstname',
				'fetchFrom' => 'payload',
				'fetchFromData' => 'firstname'
			],
			[
				'column' => 'lastname',
				'fetchFrom' => 'payload',
				'fetchFromData' => 'lastname'
			],
			[
				'column' => 'email',
				'fetchFrom' => 'payload',
				'fetchFromData' => 'email'
			],
			[
				'column' => 'username',
				'fetchFrom' => 'payload',
				'fetchFromData' => 'username'
			],
			[
				'column' => 'password_hash',
				'fetchFrom' => 'function',
				'fetchFromData' => function($session): string {
					return password_hash(
						password: $session['payload']['password'],
						algo: PASSWORD_DEFAULT
					);
				}
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
			]
		],
	]
);
