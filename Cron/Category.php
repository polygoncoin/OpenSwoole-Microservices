<?php
namespace Microservices\Cron;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

/**
 * Class for a particular cron.
 *
 * This class is meant for cron
 * One can initiate cron via access URL to this class
 * https://domain.tld/cron/className?queryString
 * All HTTP methods are supported
 *
 * @category   Crons
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
        $this->c->httpRequest->setConnection($fetchFrom = 'Slave');
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
        // Create and call functions to manage cron functionality here.

        // ...

        // End the calls with json response with jsonEncode Object.
        $this->endProcess();
        return true;
    }

    /**
     * Function to end process which outputs the results.
     *
     * @return void
     */
    private function endProcess()
    {
        throw new \Exception('message as desired.', 200);
    }
}
