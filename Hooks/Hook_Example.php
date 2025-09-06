<?php
/**
 * Hook
 * php version 8.3
 *
 * @category  Hook
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Hooks;

use Microservices\App\Common;
use Microservices\Hooks\HookInterface;
use Microservices\Hooks\HookTrait;

/**
 * Hook Example class
 * php version 8.3
 *
 * @category  Hook_Example
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Hook_Example implements HookInterface
{
    use HookTrait;

    /**
     * Common object
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
     * @return bool
     */
    public function process(): bool
    {
        $this->_execHook();
        return true;
    }

    /**
     * Exec Hook related code
     *
     * @return void
     * @throws \Exception
     */
    private function _execHook(): void
    {
        // Reset / empty payload.
        $this->_c->req->s['payload'] = null;
    }
}
