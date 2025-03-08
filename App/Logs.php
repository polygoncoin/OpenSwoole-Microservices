<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

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
    private string $logsDir = DIRECTORY_SEPARATOR . 'Logs';

    /**
     * Validates password from its hash present in cache
     *
     * @param array $logDetails
     * @return void
     */
    public function log(&$logDetails)
    {
        $absLogsDir = Constants::$DOC_ROOT . $this->logsDir;
        if (!is_dir($absLogsDir)) {
            mkdir($absLogsDir, 0755, true);
        }

        $logFile = $absLogsDir . DIRECTORY_SEPARATOR . 'logs-' . date('YmdH');
        if (!file_exists($logFile)) {
            touch($logFile);
        }

        file_put_contents($logFile, json_encode($logDetails) . PHP_EOL, FILE_APPEND);
    }
}