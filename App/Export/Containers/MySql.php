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

namespace Microservices\App\Export\Containers;

use Microservices\App\Env;
use Microservices\App\Export\DbInterface;

/**
 * Export CSV MySql container.
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
class MySql implements DbInterface
{
    /**
     * Hostname
     *
     * @var null|string
     */
    private $hostname = null;

    /**
     * Port
     *
     * @var null|string|int
     */
    private $port = null;

    /**
     * Username
     *
     * @var null|string
     */
    private $username = null;

    /**
     * Password
     *
     * @var null|string
     */
    private $password = null;

    /**
     * Database
     *
     * @var null|string
     */
    private $database = null;

    /**
     * Mysql Client binary location (One can find this by "which mysql" command)
     *
     * @var null|string
     */
    private $binaryLoc = null;

    /**
     * Constructor
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $requiredExtension = 'mysqli';
        if (!extension_loaded(extension: $requiredExtension)) {
            if (!dl(extension_filename: $requiredExtension . '.so')) {
                throw new \Exception(
                    message: "Required PHP extension '{$requiredExtension}' missing"
                );
            }
        }
        $this->binaryLoc = Env::$mySqlBinaryLocationOnWebServer;
        if (!file_exists(filename: $this->binaryLoc)) {
            throw new \Exception(message: 'Issue: missing MySql Client locally');
        }
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
     */
    public function init($hostname, $port, $username, $password, $database): void
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * Validate
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return void
     * @throws \Exception
     */
    private function validate($sql, $params): void
    {
        if (empty($sql)) {
            throw new \Exception(message: 'Empty Sql query');
        }

        if (count(value: $params) === 0) {
            return;
        }

        //Validate parameterized query.
        if (
            substr_count(
                haystack: $sql,
                needle: ':'
            ) !== count(value: $params)
        ) {
            throw new \Exception(
                message: 'Parameterized query has mismatch in number of params'
            );
        }

        $paramKeys = array_keys(array: $params);
        $paramPos = [];
        foreach ($paramKeys as $value) {
            if (substr_count(haystack: $sql, needle: $value) > 1) {
                throw new \Exception(
                    message: 'Parameterized query has more than one '
                        . "occurrence of param '{$value}'"
                );
            }
            $paramPos[$value] = strpos(haystack: $sql, needle: $value);
        }
        foreach ($paramPos as $key => $value) {
            if (
                substr(
                    string: $sql,
                    offset: $value,
                    length: strlen(string: $key)
                ) !== $key
            ) {
                throw new \Exception(message: "Invalid param key '{$key}'");
            }
        }
    }

    /**
     * Generate raw Sql query from parameterized query via PDO.
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return string
     * @throws \Exception
     */
    private function generateRawSqlQuery($sql, $params): string
    {
        if (empty($params) || count(value: $params) === 0) {
            return $sql;
        }

        $this->validate(sql: $sql, params: $params);

        //mysqli connection
        $mysqli = mysqli_connect(
            hostname: $this->hostname,
            username: $this->username,
            password: $this->password,
            database: $this->database,
            port: $this->port,
        );
        if (!$mysqli) {
            throw new \Exception(
                message: 'Connection error: ' . mysqli_connect_error()
            );
        }

        //Generate bind params
        $bindParams = [];
        foreach ($params as $key => $values) {
            if (is_array(value: $values)) {
                $tmpParams = [];
                $count = 1;
                foreach ($values as $value) {
                    if (is_array(value: $value)) {
                        throw new \Exception(
                            message: "Invalid params for key '{$key}'"
                        );
                    }
                    $newKey = $key . $count;
                    if (in_array(needle: $newKey, haystack: $tmpParams)) {
                        throw new \Exception(
                            message: "Invalid parameterized params '{$newKey}'"
                        );
                    }
                    $tmpParams[$key . $count++] = $value;
                }
                $sql = str_replace(
                    search: $key,
                    replace: implode(
                        separator: ', ',
                        array: array_keys(array: $tmpParams)
                    ),
                    subject: $sql
                );
                $bindParams = array_merge($bindParams, $tmpParams);
            } else {
                $bindParams[$key] = $values;
            }
        }

        //Replace parameterized values.
        foreach ($bindParams as $key => $value) {
            if (!ctype_digit(text: $value)) {
                $value = "'" .
                    mysqli_real_escape_string(
                        mysql: $mysqli,
                        string: $value
                    ) .
                "'";
            }
            $sql = str_replace(search: $key, replace: $value, subject: $sql);
        }

        // Close mysqli connection.
        mysqli_close(mysql: $mysqli);

        return $sql;
    }

    /**
     * Returns Shell Command
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return string
     */
    public function getShellCommand($sql, $params = null): string
    {
        $sql = $this->generateRawSqlQuery(sql: $sql, params: $params);

        // Shell command.
        $shellCommand = $this->binaryLoc . ' '
            . '--host=' . escapeshellarg(arg: $this->hostname) . ' '
            . '--port=' . escapeshellarg(arg: $this->port) . ' '
            . '--user=' . escapeshellarg(arg: $this->username) . ' '
            . '--password=' . escapeshellarg(arg: $this->password) . ' '
            . '--database=' . escapeshellarg(arg: $this->database) . ' '
            . '--execute=' . escapeshellarg(arg: $sql);

        return $shellCommand;
    }
}
