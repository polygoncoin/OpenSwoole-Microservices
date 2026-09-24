<?php

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Environment;
use Microservices\App\HttpStatus;

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Env
{
	public static $timestamp = null;

	public static $SYSTEM_ENVIRONMENT = null;

	public static $ENABLE_SYSTEM_LEVEL_PRIMARY_KEY = null;
	public static $SYSTEM_LEVEL_PRIMARY_KEY_MODE = null;
	public static $SYSTEM_LEVEL_PRIMARY_KEY_NAME = null;

	public static $SYSTEM_MASTER_DB = null;
	public static $SYSTEM_CUSTOMER_TABLE = null;

	public static $SYSTEM_ENABLE_RELOAD_CACHE = null;
    public static $SYSTEM_RELOAD_REQUEST_KEYWORD = null;

	public static $SYSTEM_ROUTE_REQUEST_KEYWORD = null;

	public static $SYSTEM_INPUT_REPRESENTATION = null;
	public static $SYSTEM_OUTPUT_REPRESENTATION = null;

	public static $iAllowedRepresentation = ['JSON', 'XML'];
	public static $oAllowedRepresentation = ['JSON', 'XML', 'XSLT', 'HTML', 'PHP'];

	public static $config = null;
	public static $initialized = null;

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function init(): void
	{
		if (self::$initialized) {
			return;
		}

		self::$SYSTEM_ENVIRONMENT = getenv(name: 'SYSTEM_ENVIRONMENT');

		self::$ENABLE_SYSTEM_LEVEL_PRIMARY_KEY = getenv(name: 'ENABLE_SYSTEM_LEVEL_PRIMARY_KEY');
		self::$SYSTEM_LEVEL_PRIMARY_KEY_MODE = getenv(name: 'SYSTEM_LEVEL_PRIMARY_KEY_MODE');
		self::$SYSTEM_LEVEL_PRIMARY_KEY_NAME = getenv(name: 'SYSTEM_LEVEL_PRIMARY_KEY_NAME');

		self::$SYSTEM_MASTER_DB = getenv(name: 'SYSTEM_MASTER_DB');
		self::$SYSTEM_CUSTOMER_TABLE = getenv(name: 'SYSTEM_CUSTOMER_TABLE');

		self::$SYSTEM_ENABLE_RELOAD_CACHE = (bool)getenv(name: 'SYSTEM_ENABLE_RELOAD_CACHE');
		self::$SYSTEM_RELOAD_REQUEST_KEYWORD = getenv(name: 'SYSTEM_RELOAD_REQUEST_KEYWORD');

		self::$SYSTEM_ROUTE_REQUEST_KEYWORD = getenv(name: 'SYSTEM_ROUTE_REQUEST_KEYWORD');

		self::$SYSTEM_INPUT_REPRESENTATION = getenv(name: 'SYSTEM_INPUT_REPRESENTATION');
		self::$SYSTEM_OUTPUT_REPRESENTATION = [
			'OUTPUT_REPRESENTATION' => getenv(name: 'SYSTEM_OUTPUT_REPRESENTATION'),
			'OUTPUT_REPRESENTATION_FILE' => Constant::$FALSE
		];


		self::$initialized = Constant::$TRUE;
	}

	/**
	 * Constructor
	 *
	 * @param int $customerId Customer id
	 *
	 * @return void
	 */
	public static function loadEnv(
		$customerId
	): void {
		// if (isset(self::$config[$customerId])) {
		// 	return;
		// }
		$envFile = ".env.customer.{$customerId}";

		self::$config[$customerId] = new Environment($envFile);
	}

	/**
	 * Validate Data Representation
	 *
	 * @param string $dataRepresentation Data Representation
	 * @param string $mode               input / output
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function isValidDataRep(
		$dataRepresentation,
		$mode
	): bool {
		switch ($mode) {
			case 'input':
				if (
					in_array(
						needle: $dataRepresentation,
						haystack: self::$iAllowedRepresentation,
						strict: Constant::$TRUE
					)
				) {
					return Constant::$TRUE;
				} else {
					throw new \Exception(
						message: "Invalid Data Representation '{$dataRepresentation}'",
						code: HttpStatus::$InternalServerError
					);
				}
				break;
			case 'output':
				if (
					in_array(
						needle: $dataRepresentation,
						haystack: self::$oAllowedRepresentation,
						strict: Constant::$TRUE
					)
				) {
					return Constant::$TRUE;
				} else {
					throw new \Exception(
						message: "Invalid Data Representation '{$dataRepresentation}'",
						code: HttpStatus::$InternalServerError
					);
				}
				break;
		}
		return Constant::$FALSE;
	}
}
