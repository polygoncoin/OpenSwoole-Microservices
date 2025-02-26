<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * Logs
 *
 * Logs contents in diffent modes
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
    private string $logsDir = DIRECTORY_SEPARATOR . 'Logs';

    private array $logTypes = [
        'debug'      => DIRECTORY_SEPARATOR . 'debug',
        'info'       => DIRECTORY_SEPARATOR . 'info',
        'error'      => DIRECTORY_SEPARATOR . 'error',
        'notice'     => DIRECTORY_SEPARATOR . 'notice',
        'warning'    => DIRECTORY_SEPARATOR . 'warning',
        'critical'   => DIRECTORY_SEPARATOR . 'critical',
        'alert'      => DIRECTORY_SEPARATOR . 'alert',
        'emergency'  => DIRECTORY_SEPARATOR . 'emergency'
    ];

    /**
     * Validates password from its hash present in cache
     *
     * @param string $logType
     * @param string $logContent
     * @return void
     * @throws \Exception
     */
    public function log($logType, $logContent)
    {
        if (!in_array($logType, array_keys($this->logTypes))) {
            throw new \Exception('Invalid log type', HttpStatus::$InternalServerError);
        }

        $absLogsDir = Constants::$DOC_ROOT . $this->logsDir;
        if (!is_dir($absLogsDir)) {
            mkdir($absLogsDir, 0755, true);
        }

        $logFile = $absLogsDir . $this->logTypes[$logType] . '-' . date('Y-m-d');
        if (!file_exists($logFile)) {
            touch($logFile);
        }

        file_put_contents($logFile, $logContent . PHP_EOL, FILE_APPEND);
    }
}