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
use Microservices\Validation\ValidatorInterface;
use Microservices\Validation\ValidatorTrait;

/**
 * Validator Global
 * php version 8.3
 *
 * @category  Validator_Global
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class GlobalValidator implements ValidatorInterface
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
     * Checks primary key exist
     *
     * @param array $args Arguments
     *
     * @return int 0/1
     */
    private function primaryKeyExist(&$args): int
    {
        extract(array: $args);
        $sql = "SELECT count(1) as `count` FROM `{$table}` WHERE `{$primary}` = ?";
        $params = [$id];
        DbFunctions::$masterDb[$this->api->req->s['cDetails']['id']]->execDbQuery(sql: $sql, params: $params);
        $row = DbFunctions::$masterDb[$this->api->req->s['cDetails']['id']]->fetch();
        DbFunctions::$masterDb[$this->api->req->s['cDetails']['id']]->closeCursor();
        return (int)(($row['count'] === 0) ? false : true);
    }

    /**
     * Checks column value exist
     *
     * @param array $args Arguments
     *
     * @return bool
     */
    private function checkColumnValueExist(&$args): bool
    {
        extract(array: $args);
        $sql = "
            SELECT count(1) as `count`
            FROM `{$table}`
            WHERE `{$column}` = ? AND`{$primary}` = ?
        ";
        $params = [$columnValue, $id];
        DbFunctions::$masterDb[$this->api->req->s['cDetails']['id']]->execDbQuery(sql: $sql, params: $params);
        $row = DbFunctions::$masterDb[$this->api->req->s['cDetails']['id']]->fetch();
        DbFunctions::$masterDb[$this->api->req->s['cDetails']['id']]->closeCursor();
        return ($row['count'] === 0) ? false : true;
    }
}
