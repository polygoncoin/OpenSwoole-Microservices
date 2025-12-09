<?php

/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Validation;

use Microservices\App\Common;
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\Validation\ValidatorInterface;
use Microservices\Validation\ValidatorTrait;

/**
 * Client Validator
 * php version 8.3
 *
 * @category  Validator_Client
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class ClientValidator implements ValidatorInterface
{
    use ValidatorTrait;

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
    }

    /**
     * Validate payload
     *
     * @param array $validationConfig Validation configuration
     *
     * @return array
     */
    public function validate(&$validationConfig): array
    {
        $isValidData = true;
        $errors = [];
        foreach ($validationConfig as &$v) {
            $args = [];
            foreach ($v['fnArgs'] as $attr => [$mode, $key]) {
                if ($mode === 'custom') {
                    $args[$attr] = $key;
                } else {
                    $args[$attr] = $this->api->req->s[$mode][$key];
                }
            }
            $fn = $v['fn'];
            if (!$this->$fn($args)) {
                $errors[] = $v['errorMessage'];
                $isValidData = false;
            }
        }
        return [$isValidData, $errors];
    }

    /**
     * Client Id Exist
     *
     * @param array $args Arguments
     *
     * @return int 0/1
     */
    public function cIdExist(&$args): int
    {
        extract(array: $args);
        return $this->getPrimaryCount(
            table: Env::$clients,
            primary: 'id',
            id: $id
        );
    }

    /**
     * Gets primary key count
     *
     * @param string $table   Table Name
     * @param string $primary Primary Key
     * @param int    $id      Primary Id
     *
     * @return int 0/1
     */
    private function getPrimaryCount(&$table, $primary, &$id): int
    {
        $db = DbFunctions::$masterDb[$this->api->req->cId]->database;
        $sql = "
            SELECT count(1) as `count`
            FROM `{$db}`.`{$table}`
            WHERE `{$primary}` = ?
        ";
        $params = [$id];
        DbFunctions::$masterDb[$this->api->req->cId]->execDbQuery(sql: $sql, params: $params);
        return (int)(DbFunctions::$masterDb[$this->api->req->cId]->fetch())['count'];
    }

    /**
     * Checks primary key exist
     *
     * @param array $args Arguments
     *
     * @return bool
     */
    private function primaryKeyExist(&$args): bool
    {
        extract(array: $args);
        $sql = "SELECT count(1) as `count` FROM `{$table}` WHERE `{$primary}` = ?";
        $params = [$id];
        DbFunctions::$masterDb[$this->api->req->cId]->execDbQuery(sql: $sql, params: $params);
        $row = DbFunctions::$masterDb[$this->api->req->cId]->fetch();
        DbFunctions::$masterDb[$this->api->req->cId]->closeCursor();
        return ($row['count'] === 0) ? false : true;
    }
}
