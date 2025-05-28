<?php
namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\Constants;

/**
 * Hook executor class
 *
 * @category   Hook class
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Hook
{
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

    public function triggerHook($hookConfig)
    {
        if (is_array($HookConfig)) {
            for ($i = 0, $iCount = count($hookConfig); $i < $iCount; $i++) {
                $hook = $hookConfig[$i];
                $hookFile = Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Hooks' . DIRECTORY_SEPARATOR . $hook . '.php';
                if (file_exists($hookFile)) {
                    $hookClass = 'Microservices\\Hooks\\'.$hook;
                    $hookObj = new $hookClass($this->c);
                    if ($hookObj->init()) {
                        $hookObj->process();
                    }
                }
            }
        }
        return true;
    }
}
