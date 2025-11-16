<?php

/**
 * Export CSV
 * php version 8.3
 *
 * @category  Export
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Export\Db;
use Microservices\App\HttpStatus;

/**
 * Export CSV
 * php version 8.3
 *
 * @category  Export
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
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
     * Used to remove file once CSV content is transferred on client machine
     *
     * @var bool
     */
    public $unlink = true;

    /**
     * DB Engine
     *
     * @var null|string
     */
    public $dbType = null;

    /**
     * DB Object
     *
     * @var null|Db
     */
    public $db = null;

    /**
     * Constructor
     *
     * @param string $dbType Database Type (eg. MySql)
     *
     * @throws \Exception
     */
    public function __construct($dbType)
    {
        $this->dbType = $dbType;
        $this->db = new Db(dbType: $this->dbType);
    }

    /**
     * Initialize
     *
     * @param string $hostname Hostname
     * @param string $port     port
     * @param string $username Username
     * @param string $password Password
     * @param string $database Database
     *
     * @return void
     */
    public function init($hostname, $port, $username, $password, $database): void
    {
        $this->db->init(
            hostname: $hostname,
            port: $port,
            username: $username,
            password: $password,
            database: $database
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
        $this->useTmpFile = false;
        [$shellCommand, $tmpFilename] = $this->getShellCommand(
            sql: $sql
        );
        $this->useTmpFile = $toggleUseTmpFile;

        $lines = shell_exec(command: $shellCommand);
        $linesArr = explode(separator: PHP_EOL, string: $lines);

        switch ($this->exportMode) {
            case 'TSV':
                if ($linesArr[1] !== '1') {
                    throw new \Exception(message: "Issue while connecting to {$this->dbType} TSV Host");
                }
                break;
            case 'CSV':
                if ($linesArr[1] !== '"1"') {
                    throw new \Exception(message: "Issue while connecting to {$this->dbType} CSV Host");
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
    private function vFileLocation($filename): void
    {
        if (!is_file(filename: $filename)) {
            throw new \Exception(
                message: "File '{$filename}' is not a file"
            );
        }

        if (file_exists(filename: $filename)) {
            throw new \Exception(
                message: "File '{$filename}' already exists"
            );
        }
    }

    /**
     * Get Shell Command
     *
     * @param string      $sql        Sql query
     * @param array       $params     Sql query params
     * @param null|string $exportFile Absolute file path
     *
     * @return array
     * @throws \Exception
     */
    private function getShellCommand(
        $sql,
        $params = [],
        $exportFile = null
    ): array {
        $shellCommand = $this->db->getShellCommand(sql: $sql, params: $params);
        if ($this->exportMode === 'CSV') {
            $shellCommand .= ' | sed -e \'s/"/""/g ; s/\t/","/g ; s/^/"/g ; s/$/"/g\'';
        }

        if (!is_null(value: $exportFile)) {
            $tmpFilename = $exportFile;
            $shellCommand .= ' > ' . escapeshellarg(arg: $tmpFilename);
        } elseif ($this->useTmpFile) {
            // Generate temporary file for storing output of shell command on server
            $tmpFilename = tempnam(directory: sys_get_temp_dir(), prefix: 'CSV');
            $shellCommand .= ' > ' . escapeshellarg(arg: $tmpFilename);
        } else {
            $tmpFilename = null;
            $shellCommand .= ' 2>&1';
        }

        return [$shellCommand, $tmpFilename];
    }

    /**
     * Initialize download.
     *
     * @param $downloadFile Name of CSV file on client side.
     * @param $sql          Sql query
     * @param $params       Sql query params
     * @param $exportFile   Absolute file path with filename
     *
     * @return array
     */
    public function initDownload(
        $downloadFile,
        $sql,
        $params = [],
        $exportFile = null
    ): array {
        [$shellCommand, $tmpFilename] = $this->getShellCommand(
            sql: $sql,
            params: $params,
            exportFile: $exportFile
        );

        if (!is_null(value: $exportFile)) {
            $this->useTmpFile = true;
            $this->unlink = false;
        }

        if ($this->useTmpFile) {
            // Execute shell command
            // The shell command to create CSV export file.
            shell_exec(command: $shellCommand);
            $return = $this->getCsvFileData(
                exportFile: $tmpFilename,
                downloadFile: $downloadFile
            );
        } else {
            // Set headers
            $headers = $this->getCsvHeaders(filename: $downloadFile);

            // Execute shell command
            // The shell command echos the output.
            $data = shell_exec(command: $shellCommand);
            $return = [$headers, $data, HttpStatus::$Ok];
        }

        return $return;
    }

    /**
     * Save Export on server
     *
     * @param $sql        Sql query
     * @param $params     Sql query params
     * @param $exportFile Absolute file path with filename
     *
     * @return array
     */
    public function saveExport(
        $sql,
        $params = [],
        $exportFile = null
    ): array {
        [$shellCommand, $tmpFilename] = $this->getShellCommand(
            sql: $sql,
            params: $params,
            exportFile: $exportFile
        );

        // Execute shell command
        // The shell command saves exported CSV data to provided path
        shell_exec(command: $shellCommand);

        return [$headers = [], $data = '', HttpStatus::$Ok];
    }

    /**
     * Set CSV file headers
     *
     * @param $filename Name to be used to save CSV file on client machine.
     *
     * @return array
     */
    private function getCsvHeaders($filename): array
    {
        $headers = [];
        // Export headers
        $headers['Content-type'] = 'text/csv';
        $headers['Content-Disposition'] = "attachment; filename={$filename}";
        $headers['Pragma'] = 'no-cache';
        $headers['Expires'] = '0';

        return $headers;
    }

    /**
     * Get data from CSV file
     *
     * @param $exportFile   Absolute file location of CSV file.
     * @param $downloadFile Name to be used to save CSV file on client machine.
     *
     * @return array
     */
    private function getCsvFileData($exportFile, $downloadFile): array
    {
        // Validation
        $this->vFileLocation(filename: $exportFile);

        // Set headers
        $headers = $this->getCsvHeaders(filename: $downloadFile);

        // Start streaming
        $data = file_get_contents(filename: $exportFile);

        if ($this->unlink && !unlink(filename: $exportFile)) { // Unable to delete
            //handle error via logs.
        }

        return [$headers, $data, HttpStatus::$Ok];
    }
}
