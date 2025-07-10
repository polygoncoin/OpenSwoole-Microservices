<?php
/**
 * Hook
 * php version 8.3
 *
 * @category  Hook
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\Constants;

/**
 * Executes configured hooks
 * php version 8.3
 *
 * @category  Hook
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Hook
{
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
    public function __construct(&$common)
    {
        $this->_c = &$common;
    }

    /**
     * Triggers Hook
     *
     * @param array $hookConfig Hook configuration
     * 
     * @return bool
     */
    public function triggerHook($hookConfig): bool
    {
        if (is_array(value: $hookConfig)) {
            for ($i = 0, $iCount = count(value: $hookConfig); $i < $iCount; $i++) {
                $hook = $hookConfig[$i];
                $hookFile = Constants::$DOC_ROOT . 
                    DIRECTORY_SEPARATOR . 'Hooks' . 
                    DIRECTORY_SEPARATOR . $hook . '.php';
                if (file_exists(filename: $hookFile)) {
                    $hookClass = 'Microservices\\Hooks\\'.$hook;
                    $hookObj = new $hookClass(common: $this->_c);
                    if ($hookObj->init()) {
                        $hookObj->process();
                    }
                }
            }
        }
        return true;
    }
}
