<?php
namespace Microservices\Supplement\Custom;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Servers\Database\AbstractDatabase;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * Class to initialize DB Read operation
 *
 * This class process the GET api request
 *
 * @category   Category
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Category implements CustomInterface
{
    use CustomTrait;

    /**
     * Database Object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common
     */
    public function __construct(&$common)
    {
        $this->c = &$common;
        $this->c->httpRequest->db = $this->c->httpRequest->setDbConnection($fetchFrom = 'Slave');
        $this->db = &$this->c->httpRequest->db;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $sql = 'SELECT * FROM category WHERE is_deleted = :is_deleted AND parent_id = :parent_id';
        $sqlParams = [
            ':is_deleted' => 'No',
            ':parent_id' => 0,
        ];
        $this->db->execDbQuery($sql, $sqlParams);
        $rows = $this->db->fetchAll();
        $this->db->closeCursor();
        $this->c->httpResponse->jsonEncode->addKeyValue('Results', $rows);
        return true;
    }
}
