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
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
        $this->c->req->db = $this->c->req->setDbConnection(fetchFrom: 'Slave');
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
        // Create and call functions to manage cron functionality here

        // End the calls with json response with dataEncode object
        $this->endProcess();
        return [true];
    }

    /**
     * Function to end process which outputs the results
     *
     * @return never
     * @throws \Exception
     */
    private function endProcess(): never
    {
        throw new \Exception(
            message: 'message as desired',
            code: HttpStatus::$Ok
        );
    }
}
