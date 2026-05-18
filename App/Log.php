<?php

/**
 * Logging
 * php version 8.3
 *
 * @category  Logging
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Http;

/**
 * Logging
 * php version 8.3
 *
 * @category  Logging
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Log
{
	/**
	 * HTTP object
	 *
	 * @var null|Http
	 */
	private $http = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;
	}

	/**
	 * Log details
	 *
	 * @param array $logData detail to be logged
	 *
	 * @return int
	 */
	public function log(&$logData): int
	{
		// Uncomment to log in DB
		return $this->logIntoDb($logData);

		// Uncomment to log in Filesystem
		// return $this->logInFilesystem($logData);
	}

	/**
	 * Log data into Database
	 *
	 * @param array $logData detail to be logged
	 *
	 * @return int
	 */
	public function logIntoDb(&$logData): int
	{
		$exceptionJson = json_encode($logData);
		return $this->http->req->logErrorData(
			exceptionJson: $exceptionJson
		);
	}

	/**
	 * Log data in FIlesystem
	 *
	 * @param array $logData detail to be logged
	 *
	 * @return int
	 */
	public function logInFilesystem(&$logData): int
	{
		$logFile = Constant::$LOG_DIR
			. DIRECTORY_SEPARATOR . 'log-' . date(format: 'YmdH');
		if (!file_exists(filename: $logFile)) {
			touch(filename: $logFile);
		}

		file_put_contents(
			filename: $logFile,
			data: json_encode(value: $logData) . PHP_EOL,
			flags: FILE_APPEND
		);

		return 0;
	}
}
