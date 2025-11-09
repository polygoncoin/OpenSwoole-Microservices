<?php

/**
 * Write APIs
 * php version 8.3
 *
 * @category  Counter
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\DbFunctions;
use Microservices\App\Env;

/**
 * Write APIs
 * php version 8.3
 *
 * @category  Counter
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
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
        DbFunctions::connectGlobalDb();

        $table = Env::$globalDbDatabase . '.' . Env::$counter;
        $sql = "INSERT INTO {$table}() VALUES()";
        $sqlParams = [];

        DbFunctions::$globalDb->execDbQuery(sql: $sql, params: $sqlParams);
        $id = DbFunctions::$globalDb->lastInsertId();

        return $id;
    }
}
