<?php
namespace Microservices\Custom;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

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
class Category
{
    /**
     * Microservices Collection of Common Objects
     * 
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Constructor
     * 
     * @param Microservices\App\Common $common
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
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
        $this->c->httpRequest->db->execDbQuery($sql, $sqlParams);
        $rows = $this->c->httpRequest->db->fetchAll();
        $this->c->httpRequest->db->closeCursor();
        $this->c->httpResponse->jsonEncode->addKeyValue('Results', $rows);
        return true;
    }
}
