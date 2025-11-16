<?php

/**
 * Export CSV
 * php version 8.3
 *
 * @category  Export
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Export;

use Microservices\App\Export\DbInterface;

/**
 * Export CSV
 * php version 8.3
 *
 * @category  Export
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Db
{
    /**
     * Allow creation of temporary file required for streaming large data
     *
     * @var bool
     */
    public $useTmpFile = false;

    /**
     * DB Engine
     *
     * @var null|string
     */
    public $dbType = null;

    /**
     * DB Class Object as per dbType
     *
     * @var null|DbInterface
     */
    public $containerObj = null;

    /**
     * Constructor
     *
     * @param string $dbType Database Type (eg. MySql)
     */
    public function __construct($dbType)
    {
        $this->dbType = $dbType;
        $class = "Microservices\\App\\Export\\Containers\\" . $this->dbType;
        $this->containerObj = new $class();
    }

    /**
     * Initialize
     *
     * @param string $hostname hostname
     * @param string $port     port
     * @param string $username username
     * @param string $password password
     * @param string $database database
     *
     * @return void
     * @throws \Exception
     */
    public function init(
        $hostname,
        $port,
        $username,
        $password,
        $database
    ): void {
        $this->containerObj->init(
            hostname: $hostname,
            port: $port,
            username: $username,
            password: $password,
            database: $database
        );
    }

    /**
     * Returns Shell Command
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return string
     */
    public function getShellCommand($sql, $params = []): string
    {
        // Validation
        if (empty($sql)) {
            throw new \Exception(message: 'Empty Sql query');
        }

        return $this->containerObj->getShellCommand(
            sql: $sql,
            params: $params
        );
    }
}
