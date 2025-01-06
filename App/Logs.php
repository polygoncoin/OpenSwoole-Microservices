<?php
namespace Microservices\App;

use Microservices\App\Constants;
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
    private $logsDir = '/Logs';

    private $logTypes = [
        'debug'      => '/debug',
        'info'       => '/info',
        'error'      => '/error',
        'notice'     => '/notice',
        'warning'    => '/warning',
        'critical'   => '/critical',
        'alert'      => '/alert',
        'emergency'  => '/emergency'
    ];

    public function log($logType, $logContent)
    {
        if (!in_array($logType, array_keys($this->logTypes))) {
            throw new \Exception('Invalid log type', 501);
        }

        $absLogsDir = Constants::$DOC_ROOT . $this->logsDir;
        if (!is_dir($absLogsDir)) {
            mkdir($absLogsDir, 0755, true);
        }

        $logFile = $absLogsDir . $this->logTypes[$logType] . '-' . date('Y-m');
        if (!file_exists($logFile)) {
            touch($logFile);
        }

        file_put_contents($logFile, $logContent . PHP_EOL, FILE_APPEND);
    }
}