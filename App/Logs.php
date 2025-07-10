<?php
/**
 * Logging
 * php version 8.3
 *
 * @category  Logging
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Constants;

/**
 * Logging
 * php version 8.3
 *
 * @category  Logging
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Logs
{
    private string $_logsDir = DIRECTORY_SEPARATOR . 'Logs';

    /**
     * Validates password from its hash present in cache
     *
     * @param array $logDetails Details to be logged
     *
     * @return void
     */
    public function log(&$logDetails): void
    {
        $absLogsDir = Constants::$DOC_ROOT . $this->_logsDir;
        if (!is_dir(filename: $absLogsDir)) {
            mkdir(directory: $absLogsDir, permissions: 0755, recursive: true);
        }

        $logFile = $absLogsDir .
            DIRECTORY_SEPARATOR . 'logs-' . date(format: 'YmdH');
        if (!file_exists(filename: $logFile)) {
            touch(filename: $logFile);
        }

        file_put_contents(
            filename: $logFile,
            data: json_encode(value: $logDetails) . PHP_EOL,
            flags: FILE_APPEND
        );
    }
}