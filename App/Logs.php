<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

/**
 * Constants
 *
 * Contains all constants related to Microservices
 *
 * @category   Logging
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Logs
{
    private $logTypes = [
        'debug'      => '/Logs/debug',
        'info'       => '/Logs/info',
        'error'      => '/Logs/error',
        'notice'     => '/Logs/notice',
        'warning'    => '/Logs/warning',
        'critical'   => '/Logs/critical',
        'alert'      => '/Logs/alert',
        'emergency'  => '/Logs/emergency'
    ];

    public function log($logType, $logContent)
    {
        if (!in_array($logType, array_keys($logTypes))) {
            $this->c->httpResponse->return5xx(501, 'Invalid log type');
            return;
        }
        $logFile = Constants::$DOC_ROOT . $this->logTypes[$logType];
        file_put_contents($logFile.'-'.date('Y-m'), $logContent . PHP_EOL, FILE_APPEND);
    }
}