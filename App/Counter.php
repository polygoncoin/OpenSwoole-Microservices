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
		if (!Env::$ENABLE_SYSTEM_LEVEL_PRIMARY_KEY) {
			throw new \Exception(
				message: 'Enable use of Global Counter',
				code: HttpStatus::$InternalServerError
			);
		}

		switch (Env::$SYSTEM_LEVEL_PRIMARY_KEY_MODE) {
			case 'Cache':
				$cacheKey = Env::$SYSTEM_LEVEL_PRIMARY_KEY_NAME;
				DbCommonFunction::connectGlobalCache(
					customerId: 0
				);
				$id = (int)DbCommonFunction::$globalCacheServerObject->cacheIncrement(
					cacheKey: $cacheKey
				);
				break;
			case 'Database':
				DbCommonFunction::connectGlobalDb(
					customerId: 0
				);

				$table = Env::$config[$this->httpObject->httpRequestObject->customerId]->DB_NAME . '.' . Env::$SYSTEM_LEVEL_PRIMARY_KEY_NAME;
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
