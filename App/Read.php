<?php
namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Validator;

/**
 * Class to initialize DB Read operation
 *
 * This class process the GET api request
 *
 * @category   CRUD Read
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Read
{
    use AppTrait;

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

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $Constants = __NAMESPACE__ . '\Constants';
        $Env = __NAMESPACE__ . '\Env';

        // Load Queries
        $readSqlConfig = include $this->c->httpRequest->__file__;

        // Set Server mode to execute query on - Read / Write Server.
        $fetchFrom = (isset($readSqlConfig['fetchFrom'])) ? $readSqlConfig['fetchFrom'] : 'Slave';
        $this->c->httpRequest->setConnection($fetchFrom);

        // Use results in where clause of sub queries recursively.
        $useHierarchy = $this->getUseHierarchy($readSqlConfig);

        if (
            (Env::$allowConfigRequest && Env::$isConfigRequest)
        ) {
            $this->processReadConfig($readSqlConfig, $useHierarchy);
        } else {
            $this->processRead($readSqlConfig, $useHierarchy);
        }

        return true;
    }

    /**
     * Process read function for configuration.
     *
     * @param array   $readSqlConfig Config from file
     * @param boolean $useHierarchy  Use results in where clause of sub queries recursively.
     * @return boolean
     */
    private function processReadConfig(&$readSqlConfig, $useHierarchy)
    {
        $this->c->httpResponse->jsonEncode->startObject('Config');
        $this->c->httpResponse->jsonEncode->addKeyValue('Route', $this->c->httpRequest->configuredUri);
        $this->c->httpResponse->jsonEncode->addKeyValue('Payload', $this->getConfigParams($readSqlConfig, true, $useHierarchy));
        $this->c->httpResponse->jsonEncode->endObject();

        return true;
    }

    /**
     * Process Function for read operation.
     *
     * @param array $readSqlConfig Config from file
     * @param boolean  $useHierarchy  Use results in where clause of sub queries recursively.
     * @return boolean
     */
    private function processRead(&$readSqlConfig, $useHierarchy)
    {
        $this->c->httpRequest->session['requiredArr'] = $this->getRequired($readSqlConfig, true, $useHierarchy);
        $this->c->httpRequest->session['required'] = $this->c->httpRequest->session['requiredArr']['__required__'];

        // Start Read operation.
        $keys = [];
        $this->readDB($readSqlConfig, true, $keys, $useHierarchy);

        return true;
    }

    /**
     * Function to select sub queries recursively.
     *
     * @param array   $readSqlConfig Config from file
     * @param boolean $start         true to represent the first call in recursion.
     * @param array   $keys          Keys in recursion.
     * @param boolean $useHierarchy  Use results in where clause of sub queries recursively.
     * @return boolean
     */
    private function readDB(&$readSqlConfig, $start, &$keys, $useHierarchy)
    {
        $isAssoc = $this->isAssoc($readSqlConfig);
        if ($isAssoc) {
            switch ($readSqlConfig['mode']) {
                // Query will return single row
                case 'singleRowFormat':
                    if ($start) {
                        $this->c->httpResponse->jsonEncode->startObject('Results');
                    } else {
                        $this->c->httpResponse->jsonEncode->startObject();
                    }
                    $this->fetchSingleRow($readSqlConfig, $keys, $useHierarchy);
                    $this->c->httpResponse->jsonEncode->endObject();
                    break;
                // Query will return multiple rows
                case 'multipleRowFormat':
                    $keysCount = count($keys)-1;
                    if ($start) {
                        if (isset($readSqlConfig['countQuery'])) {
                            $this->fetchRowsCount($readSqlConfig);
                        }
                        $this->c->httpResponse->jsonEncode->startArray('Results');
                    } else {
                        $this->c->httpResponse->jsonEncode->startArray($keys[$keysCount]);
                    }
                    $this->fetchMultipleRows($readSqlConfig, $keys, $useHierarchy);
                    $this->c->httpResponse->jsonEncode->endArray();
                    if (!$start) {
                        $this->c->httpResponse->jsonEncode->endObject();
                    }
                    break;
            }
            if (!$useHierarchy && isset($readSqlConfig['subQuery'])) {
                $this->callReadDB($readSqlConfig, $keys, $row, $useHierarchy);
            }
        }

        return true;
    }

    /**
     * Function to fetch single record.
     *
     * @param array   $readSqlConfig Read SQL configuration.
     * @param array   $keys          Module Keys in recursion.
     * @param boolean $useHierarchy  Use results in where clause of sub queries recursively.
     * @return boolean
     * @throws \Exception
     */
    private function fetchSingleRow(&$readSqlConfig, &$keys, $useHierarchy)
    {
        $isAssoc = $this->isAssoc($readSqlConfig);
        list($sql, $sqlParams, $errors) = $this->getSqlAndParams($readSqlConfig);
        if (!empty($errors)) {
            throw new \Exception($errors, HttpStatus::$InternalServerError);
        }

        $this->c->httpRequest->db->execDbQuery($sql, $sqlParams);
        if ($row =  $this->c->httpRequest->db->fetch()) {
            //check if selected column-name mismatches or confliects with configured module/submodule names.
            if (isset($readSqlConfig['subQuery'])) {
                $subQueryKeys = array_keys($readSqlConfig['subQuery']);
                foreach($row as $key => $value) {
                    if (in_array($key, $subQueryKeys)) {
                        throw new \Exception('Invalid configuration: Conflicting column names', HttpStatus::$InternalServerError);
                    }
                }
            }
        } else {
            $row = [];
        }
        foreach($row as $key => $value) {
            $this->c->httpResponse->jsonEncode->addKeyValue($key, $value);
        }
        $this->c->httpRequest->db->closeCursor();

        if ($useHierarchy && isset($readSqlConfig['subQuery'])) {
            $this->callReadDB($readSqlConfig, $keys, $row, $useHierarchy);
        }

        return true;
    }

    /**
     * Function to fetch row count.
     *
     * @param array  $readSqlConfig Read SQL configuration.
     * @return boolean
     * @throws \Exception
     */
    private function fetchRowsCount($readSqlConfig)
    {
        $readSqlConfig['query'] = $readSqlConfig['countQuery'];
        unset($readSqlConfig['countQuery']);

        $this->c->httpRequest->session['payload']['page']  = $_GET['page'] ?? 1;
        $this->c->httpRequest->session['payload']['perpage']  = $_GET['perpage'] ?? Env::$defaultPerpage;

        if ($this->c->httpRequest->session['payload']['perpage'] > Env::$maxPerpage) {
            throw new \Exception('perpage exceeds max perpage value of '.Env::$maxPerpage, HttpStatus::$Forbidden);
        }

        $this->c->httpRequest->session['payload']['start']  = ($this->c->httpRequest->session['payload']['page'] - 1) * $this->c->httpRequest->session['payload']['perpage'];
        list($sql, $sqlParams, $errors) = $this->getSqlAndParams($readSqlConfig);

        if (!empty($errors)) {
            throw new \Exception($errors, HttpStatus::$InternalServerError);
        }

        $this->c->httpRequest->db->execDbQuery($sql, $sqlParams);
        $row = $this->c->httpRequest->db->fetch();
        $this->c->httpRequest->db->closeCursor();

        $totalRowsCount = $row['count'];
        $totalPages = ceil($totalRowsCount/$this->c->httpRequest->session['payload']['perpage']);

        $this->c->httpResponse->jsonEncode->addKeyValue('page', $this->c->httpRequest->session['payload']['page']);
        $this->c->httpResponse->jsonEncode->addKeyValue('perpage', $this->c->httpRequest->session['payload']['perpage']);
        $this->c->httpResponse->jsonEncode->addKeyValue('totalPages', $totalPages);
        $this->c->httpResponse->jsonEncode->addKeyValue('totalRecords', $totalRowsCount);

        return true;
    }

    /**
     * Function to fetch multiple record.
     *
     * @param array   $readSqlConfig Read SQL configuration.
     * @param array   $keys          Module Keys in recursion.
     * @param boolean $useHierarchy  Use results in where clause of sub queries recursively.
     * @return boolean
     * @throws \Exception
     */
    private function fetchMultipleRows(&$readSqlConfig, &$keys, $useHierarchy)
    {
        $isAssoc = $this->isAssoc($readSqlConfig);
        if (!$useHierarchy && isset($readSqlConfig['subQuery'])) {
            throw new \Exception('Invalid Configuration: multipleRowFormat can\'t have sub query', HttpStatus::$InternalServerError);
        }
        $isAssoc = $this->isAssoc($readSqlConfig);

        list($sql, $sqlParams, $errors) = $this->getSqlAndParams($readSqlConfig);
        if (!empty($errors)) {
            throw new \Exception($errors, HttpStatus::$InternalServerError);
        }

        if (isset($readSqlConfig['countQuery'])) {
            $start = $this->c->httpRequest->session['payload']['start'];
            $offset = $this->c->httpRequest->session['payload']['perpage'];
            $sql .= " LIMIT {$start}, {$offset}";
        }

        $singleColumn = false;
        $this->c->httpRequest->db->execDbQuery($sql, $sqlParams);
        for ($i = 0; $row = $this->c->httpRequest->db->fetch();) {
            if ($i===0) {
                if (count($row) === 1) {
                    $singleColumn = true;
                }
                $singleColumn = $singleColumn && !isset($readSqlConfig['subQuery']);
                $i++;
            }
            if ($singleColumn) {
                $this->c->httpResponse->jsonEncode->encode($row[key($row)]);
            } else if (isset($readSqlConfig['subQuery'])) {
                $this->c->httpResponse->jsonEncode->startObject();
                foreach($row as $key => $value) {
                    $this->c->httpResponse->jsonEncode->addKeyValue($key, $value);
                }
            } else {
                $this->c->httpResponse->jsonEncode->encode($row);
            }
            if ($useHierarchy && isset($readSqlConfig['subQuery'])) {
                $this->callReadDB($readSqlConfig, $keys, $row, $useHierarchy);
            }
        }
        $this->c->httpRequest->db->closeCursor();

        return true;
    }

    /**
     * Function to reset data for module key wise.
     *
     * @param array   $keys         Module Keys in recursion.
     * @param array   $row          Row data fetched from DB.
     * @param boolean $useHierarchy Use results in where clause of sub queries recursively.
     * @return boolean
     */
    private function resetFetchData(&$keys, $row, $useHierarchy)
    {
        if ($useHierarchy) {
            if (count($keys) === 0) {
                $this->c->httpRequest->session['hierarchyData'] = [];
                $this->c->httpRequest->session['hierarchyData']['return'] = [];
            }
            $httpReq = &$this->c->httpRequest->session['hierarchyData']['return'];
            foreach ($keys as $k) {
                if (!isset($httpReq[$k])) {
                    $httpReq[$k] = [];
                }
                $httpReq = &$httpReq[$k];
            }
            $httpReq = $row;
        }

        return true;
    }

    /**
     * Validate and call readDB
     *
     * @param array   $readSqlConfig Read SQL configuration.
     * @param array   $keys          Module Keys in recursion.
     * @param array   $row           Row data fetched from DB.
     * @param boolean $useHierarchy  Use results in where clause of sub queries recursively.
     * @return boolean
     */
    private function callReadDB(&$readSqlConfig, &$keys, &$row, $useHierarchy)
    {
        if ($useHierarchy) {
            $this->resetFetchData($keys, $row, $useHierarchy);
        }

        if (isset($readSqlConfig['subQuery']) && $this->isAssoc($readSqlConfig['subQuery'])) {
            foreach ($readSqlConfig['subQuery'] as $subQuery_key => $readSqlDetails) {
                $k = array_merge($keys, [$subQuery_key]);
                $_useHierarchy = ($useHierarchy) ?? $this->getUseHierarchy($readSqlDetails);
                $this->readDB($readSqlDetails, false, $k, $_useHierarchy);
            }
        }

        return true;
    }
}
