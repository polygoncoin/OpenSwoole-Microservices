<?php

/**
 * Handling Database via PostgreSql
 * php version 8.3
 *
 * @category  Database
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\DatabaseServer;

use Microservices\App\Server\DatabaseServer\DatabaseServerInterface;
use Microservices\App\Server\Container\Sql\PostgreSql as DB_PostgreSql;

/**
 * PostgreSql Database
 * php version 8.3
 *
 * @category  Database_PostgreSql
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlDatabase extends DB_PostgreSql implements DatabaseServerInterface
{
}
