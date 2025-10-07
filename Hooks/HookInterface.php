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

/**
 * Hook Interface
 * php version 8.3
 *
 * @category  Hook_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface HookInterface
{
    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool;

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool;
}
