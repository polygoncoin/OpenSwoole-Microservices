<?php

/**
 * Export CSV
 * php version 8.3
 * 
 * @category  Export
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Export\ExportDatabaseServer;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Export CSV
 * php version 8.3
 * 
 * @category  Export
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Export
{
	/**
	 * TSV - Tab Seperated Values
	 * CSV - Comma Seperated Values
	 * 
	 * @var string
	 */
	public $exportMode = 'CSV';

	/**
	 * Allow creation of temporary file required for streaming large data
	 * 
	 * @var bool
	 */
	public $useTmpFile = false;

	/**
	 * Used to remove file once CSV content is transferred on customer machine
	 * 
	 * @var bool
	 */
	public $unlink = true;

	/**
	 * Database Engine
	 * 
	 * @var null|string
	 */
	public $dbServerType = null;

	/**
	 * Database Object
	 * 
	 * @var null|ExportDatabaseServer
	 */
	public $exportDbServerObject = null;

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Constructor
	 * 
	 * @param Http   $httpObject
	 * @param string $dbServerType Database Type (eg. MySql)
	 * 
	 * @throws \Exception
	 */
	public function __construct(
		&$httpObject,
		$dbServerType
	) {
		$this->httpObject = &$httpObject;
		$this->dbServerType = $dbServerType;
		$this->exportDbServerObject = new ExportDatabaseServer(
			dbServerType: $this->dbServerType
		);
	}

	/**
	 * Initialize
	 * 
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDatabase Database Server Database
	 * 
	 * @return void
	 */
	public function init(
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDatabase
	): void {
		$this->exportDbServerObject->init(
			dbServerHostname: $dbServerHostname,
			dbServerPort: $dbServerPort,
			dbServerUsername: $dbServerUsername,
			dbServerPassword: $dbServerPassword,
			dbServerDatabase: $dbServerDatabase
		);
		$this->validateConnection();
	}

	/**
	 * Validate Connection
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function validateConnection(): void
	{
		$sql = 'SELECT 1;';

		$toggleUseTmpFile = $this->useTmpFile;
		$this->useTmpFile = Constant::$FALSE;
		[
			$shellCommand,
			$tmpFilename
		] = $this->getShellCommand(
			sql: $sql
		);
		$this->useTmpFile = $toggleUseTmpFile;

		$shellOutput = shell_exec(
			command: $shellCommand
		);
		$outputLineArray = explode(
			separator: PHP_EOL,
			string: $shellOutput
		);

		switch ($this->exportMode) {
			case 'TSV':
				if ($outputLineArray[1] !== '1') {
					throw new \Exception(
						message: "Issue connecting to {$this->dbServerType} TSV Host",
						code: HttpStatus::$InternalServerError
					);
				}
				break;
			case 'CSV':
				if ($outputLineArray[1] !== '"1"') {
					throw new \Exception(
						message: "Issue connecting to {$this->dbServerType} CSV Host",
						code: HttpStatus::$InternalServerError
					);
				}
				break;
		}
	}

	/**
	 * Validate file location.
	 * 
	 * @param $filename CSV file location.
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function vFileLocation(
		$filename
	): void {
		if (
			!is_file(
				filename: $filename
			)
		) {
			throw new \Exception(
				message: "File '{$filename}' is not a file",
				code: HttpStatus::$InternalServerError
			);
		}

		if (
			file_exists(
				filename: $filename
			)
		) {
			throw new \Exception(
				message: "File '{$filename}' already exists",
				code: HttpStatus::$InternalServerError
			);
		}
	}

	/**
	 * Get Shell Command
	 * 
	 * @param string      $sql        Sql query
	 * @param array       $paramArray Sql query params
	 * @param null|string $exportFile Absolute file path
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getShellCommand(
		$sql,
		$paramArray = [],
		$exportFile = null
	): array {
		$shellCommand = $this->exportDbServerObject->getShellCommand(
			sql: $sql,
			paramArray: $paramArray
		);
		if ($this->exportMode === 'CSV') {
			$shellCommand .= ' | sed -e \'s/"/""/g ; s/\t/","/g ; s/^/"/g ; s/$/"/g\'';
		}

		if (
			!is_null(
				value: $exportFile
			)
		) {
			$tmpFilename = $exportFile;
			$shellCommand .= ' > ' . escapeshellarg(
				arg: $tmpFilename
			);
		} elseif ($this->useTmpFile) {
			// Generate temporary file for storing output of shell command on server
			$tmpFilename = tempnam(
				directory: sys_get_temp_dir(),
				prefix: 'CSV'
			);
			$shellCommand .= ' > ' . escapeshellarg(
				arg: $tmpFilename
			);
		} else {
			$tmpFilename = Constant::$NULL;
			$shellCommand .= ' 2>&1';
		}

		return [
			$shellCommand,
			$tmpFilename
		];
	}

	/**
	 * Initialize download.
	 * 
	 * @param $downloadFile Name of CSV file on customer side.
	 * @param $sql          Sql query
	 * @param $paramArray   Sql query params
	 * @param $exportFile   Absolute file path with filename
	 * 
	 * @return array
	 */
	public function initDownload(
		$downloadFile,
		$sql,
		$paramArray = [],
		$exportFile = null
	): array {
		[
			$shellCommand,
			$tmpFilename
		] = $this->getShellCommand(
			sql: $sql,
			paramArray: $paramArray,
			exportFile: $exportFile
		);

		if (
			!is_null(
				value: $exportFile
			)
		) {
			$this->useTmpFile = Constant::$TRUE;
			$this->unlink = Constant::$FALSE;
		}

		if ($this->useTmpFile) {
			// Execute shell command
			// The shell command to create CSV export file.
			shell_exec(
				command: $shellCommand
			);
			$return = $this->getCsvFileData(
				exportFile: $tmpFilename,
				downloadFile: $downloadFile
			);
		} else {
			// Set header
			$headerArray = $this->getCsvHeaders(
				filename: $downloadFile
			);

			// Execute shell command
			// The shell command echos the output.
			$data = shell_exec(
				command: $shellCommand
			);
			$return = [
				$headerArray,
				$data,
				HttpStatus::$Ok
			];
		}

		return $return;
	}

	/**
	 * Save Export on server
	 * 
	 * @param $sql        Sql query
	 * @param $paramArray Sql query params
	 * @param $exportFile Absolute file path with filename
	 * 
	 * @return array
	 */
	public function saveExport(
		$sql,
		$paramArray = [],
		$exportFile = null
	): array {
		[
			$shellCommand,
			$tmpFilename
		] = $this->getShellCommand(
			sql: $sql,
			paramArray: $paramArray,
			exportFile: $exportFile
		);

		// Execute shell command
		// The shell command saves exported CSV data to provided path
		shell_exec(
			command: $shellCommand
		);

		return [
			$headerArray = [],
			$data = '',
			HttpStatus::$Ok
		];
	}

	/**
	 * Get CSV file header
	 * 
	 * @param $filename Name to be used to save CSV file on customer machine.
	 * 
	 * @return array
	 */
	private function getCsvHeaders(
		$filename
	): array {
		$headerArray = [];
		// Export header
		$headerArray['Content-type'] = 'text/csv';
		$headerArray['Content-Disposition'] = "attachment; filename={$filename}";
		$headerArray['Pragma'] = 'no-cache';
		$headerArray['Expires'] = '0';

		return $headerArray;
	}

	/**
	 * Get CSV file data
	 * 
	 * @param $exportFile   Absolute file location of CSV file.
	 * @param $downloadFile Name to be used to save CSV file on customer machine.
	 * 
	 * @return array
	 */
	private function getCsvFileData(
		$exportFile,
		$downloadFile
	): array {
		// Validation
		$this->vFileLocation(
			filename: $exportFile
		);

		// Set header
		$headerArray = $this->getCsvHeaders(
			filename: $downloadFile
		);

		// Start streaming
		$data = file_get_contents(
			filename: $exportFile
		);

		if (
			$this->unlink
			&& !unlink(
				filename: $exportFile
			)
		) { // Unable to delete
			//handle error via logs.
		}

		return [
			$headerArray,
			$data,
			HttpStatus::$Ok
		];
	}
}
