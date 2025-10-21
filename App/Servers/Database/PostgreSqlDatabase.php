<?php

/**
 * Handling Database via PostgreSql
 * php version 8.3
 *
 * @category  Database
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Servers\Database;

use Microservices\App\Servers\Database\DatabaseInterface;
use Microservices\App\Servers\Containers\Sql\PostgreSql as DB_PostgreSql;

/**
 * PostgreSql Database
 * php version 8.3
 *
 * @category  Database_PostgreSql
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlDatabase extends DB_PostgreSql implements DatabaseInterface
{
}
