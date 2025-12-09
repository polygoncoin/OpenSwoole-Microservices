<?php

/**
 * CustomAPI
 * php version 8.3
 *
 * @category  CustomAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Custom;

use Microservices\App\Common;
use Microservices\App\DbFunctions;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * CustomAPI Category
 * php version 8.3
 *
 * @category  CustomAPI_Category
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Category implements CustomInterface
{
    use CustomTrait;

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
        DbFunctions::setDbConnection($this->api->req, fetchFrom: 'Slave');
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        return true;
    }

    /**
     * Process
     *
     * @param array $payload Payload
     *
     * @return array
     */
    public function process(array $payload = []): array
    {
        $sql = '
            SELECT *
            FROM category
            WHERE is_deleted = :is_deleted AND parent_id = :parent_id
        ';
        $sqlParams = [
            ':is_deleted' => 'No',
            ':parent_id' => 0,
        ];
        DbFunctions::$slaveDb[$this->api->req->cId]->execDbQuery(sql: $sql, params: $sqlParams);
        $rows = DbFunctions::$slaveDb[$this->api->req->cId]->fetchAll();
        DbFunctions::$slaveDb[$this->api->req->cId]->closeCursor();
        $this->api->res->dataEncode->addKeyData(key: 'Results', data: $rows);
        return [true];
    }
}
