<?php
/**
 * ThirdPartyAPI
 * php version 8.3
 *
 * @category  ThirdPartyAPI_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Supplement\ThirdParty;

/**
 * ThirdPartyAPI Interface
 * php version 8.3
 *
 * @category  ThirdPartyAPI_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface ThirdPartyInterface
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
