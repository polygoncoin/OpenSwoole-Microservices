<?php
/**
 * Initialize ThirdParty
 * php version 8.3
 *
 * @category  ThirdParty
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Common;
use Microservices\Supplement\ThirdParty\ThirdPartyInterface;

/**
 * ThirdParty API
 * php version 8.3
 *
 * @category  ThirdPartyAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class ThirdParty
{
    /**
     * ThirdParty API Object
     *
     * @var null|ThirdPartyInterface
     */
    private $_api = null;

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
        $class = 'Microservices\\\Supplement\\ThirdParty\\' .
            ucfirst(string: $this->_c->req->rParser->routeElements[1]);

        $this->_api = new $class(common: $this->_c);
        if ($this->_api->init()) {
            $this->_api->process();
        }

        return true;
    }
}
