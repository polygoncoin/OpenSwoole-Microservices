<?php

/**
 * Write APIs
 * php version 8.3
 *
 * @category  Counter
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\DbCommonFunction;
use Microservices\App\Env;

/**
 * Write APIs
 * php version 8.3
 *
 * @category  Counter
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Counter
{
	/**
	 * Get Global Auto Increment Counter
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
				$key = Env::$gCounter;
				DbCommonFunction::connectGlobalCacheServer();
				$id = DbCommonFunction::$gCacheServer->incrementCache($key);
				break;
			case 'Database':
				DbCommonFunction::connectGlobalDatabaseServer();

				$table = Env::$gDbServerDB . '.' . Env::$gCounter;
				$sql = "INSERT INTO {$table}() VALUES()";
				$sqlParams = [];

				DbCommonFunction::$gDbServer->execDbQuery(sql: $sql, params: $sqlParams);
				$id = DbCommonFunction::$gDbServer->lastInsertId();
				break;
		}

		return $id;
	}
}
