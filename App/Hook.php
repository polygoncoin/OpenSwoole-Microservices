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

namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\HttpStatus;

/**
 * Executes configured hooks
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
class Hook
{
    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
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
                $hookFile = Constants::$PUBLIC_HTML .
                    DIRECTORY_SEPARATOR . 'Hooks' .
                    DIRECTORY_SEPARATOR . $hook . '.php';
                if (file_exists(filename: $hookFile)) {
                    $hookClass = 'Microservices\\\Hooks\\' . $hook;
                    $hookObj = new $hookClass($this->api);
                    if ($hookObj->init()) {
                        $hookObj->process();
                    }
                } else {
                    throw new \Exception(
                        message: "Hook '{$hook}' missing",
                        code: HttpStatus::$InternalServerError
                    );
                }
            }
        }
        return true;
    }
}
