<?php
namespace Microservices\Hooks;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\Hooks\HookInterface;
use Microservices\Hooks\HookTrait;

/**
 * Hook Example class
 *
 * @category   Hook Example class
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Hook_Example implements HookInterface
{
    use HookTrait;

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
        $this->execHook();
        return true;
    }

    /**
     * Exec Hook related code
     *
     * @return void
     * @throws \Exception
     */
    private function execHook()
    {
        // Reset / empty payload.
        $this->c->httpRequest->session['payload'] = null;
    }
}
