<?php
/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Validation;

use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Servers\Database\AbstractDatabase;
use Microservices\Validation\ValidatorInterface;
use Microservices\Validation\ValidatorTrait;

/**
 * Client Validator
 * php version 8.3
 *
 * @category  Validator_Client
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class ClientValidator implements ValidatorInterface
{
    use ValidatorTrait;

    /**
     * Database Object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
        $this->db = &$this->_c->req->db;
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
        $sess = &$this->_c->req->sess;
        $isValidData = true;
        $errors = [];
        foreach ($validationConfig as &$v) {
            $args = [];
            foreach ($v['fnArgs'] as $attr => [$mode, $key]) {
                if ($mode === 'custom') {
                    $args[$attr] = $key;
                } else {
                    $args[$attr] = $sess[$mode][$key];
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
    public function clientIdExist(&$args): int
    {
        extract(array: $args);
        return $this->_getPrimaryCount(
            table: Env::$clients,
            primary: 'client_id',
            id: $client_id
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
    private function _getPrimaryCount(&$table, $primary, &$id): int
    {
        $db = $this->db->database;
        $sql = "
            SELECT count(1) as `count`
            FROM `{$db}`.`{$table}`
            WHERE `{$primary}` = ?
        ";
        $params = [$id];
        $this->db->execDbQuery(sql: $sql, params: $params);
        return (int)($this->db->fetch())['count'];
    }

    /**
     * Checks primary key exist
     *
     * @param array $args Arguments
     *
     * @return int 0/1
     */
    private function _primaryKeyExist(&$args): int
    {
        extract(array: $args);
        $sql = "SELECT count(1) as `count` FROM `{$table}` WHERE `{$primary}` = ?";
        $params = [$id];
        $this->db->execDbQuery(sql: $sql, params: $params);
        $row = $this->db->fetch();
        $this->db->closeCursor();
        return (int)($row['count'] === 0) ? false : true;
    }
}
