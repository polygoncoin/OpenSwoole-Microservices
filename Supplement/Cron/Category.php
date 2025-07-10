<?php
/**
 * CronAPI
 * php version 8.3
 *
 * @category  CronAPI_Trait
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Supplement\Cron;

use Microservices\App\Common;
use Microservices\App\HttpStatus;
use Microservices\Supplement\Cron\CronInterface;
use Microservices\Supplement\Cron\CronTrait;

/**
 * CronAPI
 * php version 8.3
 *
 * @category  CronAPI_Example
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Category implements CronInterface
{
    use CronTrait;

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
        $this->_c->req->db = $this->_c->req->setDbConnection(fetchFrom: $fetchFrom = 'Slave');
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init()
    {
        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process()
    {
        // Create and call functions to manage cron functionality here

        // End the calls with json response with dataEncode Object
        $this->endProcess();
        return true;
    }

    /**
     * Function to end process which outputs the results
     *
     * @return void
     * @throws \Exception
     */
    private function endProcess()
    {
        throw new \Exception(
            message: 'message as desired',
            code: HttpStatus::$Ok
        );
    }
}
