<?php

/**
 * Write APIs
 * php version 8.3
 * 
 * @category  Counter
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * Write APIs
 * php version 8.3
 * 
 * @category  Counter
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Counter
{
	/**
	 * Get Global counter
	 * 
	 * @return int
	 */
	public static function getGlobalCounter(): int
	{
		if (!Env::$enableGlobalCounter) {
			throw new \Exception(
				message: 'Enable use of Global Counter',
				code: HttpStatus::$InternalServerError
			);
		}

		switch (Env::$gCounterMode) {
			case 'Cache':
				$cacheKey = Env::$gCounter;
				DbCommonFunction::connectGlobalCache();
				$id = (int)DbCommonFunction::$globalCacheServerObject->cacheIncrement(
					cacheKey: $cacheKey
				);
				break;
			case 'Database':
				DbCommonFunction::connectGlobalDb();

				$table = Env::$gDbServerDatabase . '.' . Env::$gCounter;
				$sql = "INSERT INTO {$table}() VALUES()";
				$paramArray = [];

				DbCommonFunction::$gDbServer->execQuery(
					sql: $sql,
					paramArray: $paramArray
				);
				$id = DbCommonFunction::$gDbServer->lastInsertId();
				break;
		}

		return $id;
	}
}
