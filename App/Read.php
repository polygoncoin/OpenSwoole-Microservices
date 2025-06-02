<?php
namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\HttpStatus;
use Microservices\App\Validator;
use Microservices\App\Web;
use Microservices\App\Servers\Database\AbstractDatabase;

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
     * Database Object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Trigger Web API Object
     *
     * @var null|Web
     */
    private $web = null;

    /**
     * Hook Object
     *
     * @var null|Hook
     */
    private $hook = null;

    /**
     * Json Encode Object
     *
     * @var null|AbstractDataEncode
     */
    public $dataEncode = null;

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
     * @throws \Exception
     */
    public function process()
    {
        $Env = __NAMESPACE__ . '\Env';
        $session = &$this->c->httpRequest->session;

        // Load Queries
        $readSqlConfig = include $this->c->httpRequest->__FILE__;

        // Rate Limiting request if configured for Route Queries.
        $this->rateLimitRoute($readSqlConfig);

        // Lag Response
        $this->lagResponse($readSqlConfig);

        // Check for cache
        $tobeCached = false;
        if (
            isset($readSqlConfig['cacheKey'])
            && !isset($this->c->httpRequest->session['payload']['orderBy'])
        ) {
            $json = $this->c->httpRequest->getDqlCache($readSqlConfig['cacheKey']);
            if (!is_null($json)) {
                $cacheHitJson = '"cacheHit": true';
                $this->c->httpResponse->dataEncode->appendData($cacheHitJson);
                $this->c->httpResponse->dataEncode->appendData($json);
                return true;
            } else {
                $tobeCached = true;
            }
        }

        if ($tobeCached) {
            $this->dataEncode = new DataEncode($this->c->httpRequestDetails);
            $this->dataEncode->init();
        } else {
            $this->dataEncode = &$this->c->httpResponse->dataEncode;
        }

        // Set Server mode to execute query on - Read / Write Server
        $fetchFrom = (isset($readSqlConfig['fetchFrom'])) ? $readSqlConfig['fetchFrom'] : 'Slave';
        $this->c->httpRequest->db = $this->c->httpRequest->setDbConnection($fetchFrom);
        $this->db = &$this->c->httpRequest->db;

        // Use result set recursively flag
        $useResultSet = $this->getUseHierarchy($readSqlConfig, 'useResultSet');

        if (
            (Env::$allowConfigRequest && $this->c->httpRequest->isConfigRequest)
        ) {
            $this->processReadConfig($readSqlConfig, $useResultSet);
        } else {
            $this->processRead($readSqlConfig, $useResultSet);
        }

        if ($tobeCached) {
            $json = $this->dataEncode->getData();
            $this->c->httpRequest->setDmlCache($readSqlConfig['cacheKey'], $json);
            $this->c->httpResponse->dataEncode->appendData($json);
        }

        return true;
    }

    /**
     * Process read function for configuration
     *
     * @param array   $readSqlConfig Config from file
     * @param boolean $useResultSet  Use result set recursively flag
     * @return void
     */
    private function processReadConfig(&$readSqlConfig, $useResultSet)
    {
        $this->dataEncode->startObject('Config');
        $this->dataEncode->addKeyData('Route', $this->c->httpRequest->configuredUri);
        $this->dataEncode->addKeyData('Payload', $this->getConfigParams($readSqlConfig, $isFirstCall = true, $useResultSet));
        $this->dataEncode->endObject();
    }

    /**
     * Process Function for read operation
     *
     * @param array   $readSqlConfig Config from file
     * @param boolean $useResultSet  Use result set recursively flag
     * @return void
     */
    private function processRead(&$readSqlConfig, $useResultSet)
    {
        $this->c->httpRequest->session['requiredArr'] = $this->getRequired($readSqlConfig, $isFirstCall = true, $useResultSet);

        if (isset($this->c->httpRequest->session['requiredArr'])) {
            $this->c->httpRequest->session['required'] = $this->c->httpRequest->session['requiredArr'];
        } else {
            $this->c->httpRequest->session['required'] = [];
        }

        // Start Read operation
        $configKeys = [];
        $this->readDB($readSqlConfig, $isFirstCall = true, $configKeys, $useResultSet);
    }

    /**
     * Function to select sub queries recursively
     *
     * @param array   $readSqlConfig Config from file
     * @param boolean $isFirstCall   true to represent the first call in recursion
     * @param array   $configKeys    Keys in recursion
     * @param boolean $useResultSet  Use result set recursively flag
     * @return void
     */
    private function readDB(&$readSqlConfig, $isFirstCall, &$configKeys, $useResultSet)
    {
        $isAssoc = $this->isAssoc($readSqlConfig);

        // Execute Pre Sql Hooks
        if (isset($readSqlConfig['__PRE-SQL-HOOKS__'])) {
            if (is_null($this->hook)) {
                $this->hook = new Hook($this->c);
            }
            $this->hook->triggerHook($readSqlConfig['__PRE-SQL-HOOKS__']);
        }

        if ($isAssoc) {
            switch ($readSqlConfig['__MODE__']) {
                // Query will return single row
                case 'singleRowFormat':
                    if ($isFirstCall) {
                        $this->dataEncode->startObject('Results');
                    } else {
                        $this->dataEncode->startObject();
                    }
                    $this->fetchSingleRow($readSqlConfig, $isFirstCall, $configKeys, $useResultSet);
                    $this->dataEncode->endObject();
                    break;
                // Query will return multiple rows
                case 'multipleRowFormat':
                    if ($isFirstCall) {
                        $this->dataEncode->startObject('Results');
                        if (isset($readSqlConfig['countQuery'])) {
                            $this->fetchRowsCount($readSqlConfig);
                        }
                        $this->dataEncode->startArray('Data');
                    } else {
                        $this->dataEncode->startArray($configKeys[count($configKeys)-1]);
                    }
                    $this->fetchMultipleRows($readSqlConfig, $isFirstCall, $configKeys, $useResultSet);
                    $this->dataEncode->endArray();
                    if ($isFirstCall && isset($readSqlConfig['countQuery'])) {
                        $this->dataEncode->endObject();
                    }
                    break;
            }
        }

        // triggers
        if (isset($readSqlConfig['__TRIGGERS__'])) {
            if (is_null($this->web)) {
                $this->web = new Web($this->c);
            }
            $this->web->triggerConfig($readSqlConfig['__TRIGGERS__']);
        }

        // Execute Post Sql Hooks
        if (isset($readSqlConfig['__POST-SQL-HOOKS__'])) {
            if (is_null($this->hook)) {
                $this->hook = new Hook($this->c);
            }
            $this->hook->triggerHook($readSqlConfig['__POST-SQL-HOOKS__']);
        }
    }

    /**
     * Function to fetch single record
     *
     * @param array   $readSqlConfig Read SQL configuration
     * @param boolean $isFirstCall   true to represent the first call in recursion
     * @param array   $configKeys    Config Keys
     * @param boolean $useResultSet  Use result set recursively flag
     * @return void
     * @throws \Exception
     */
    private function fetchSingleRow(&$readSqlConfig, $isFirstCall, &$configKeys, $useResultSet)
    {
        $isAssoc = $this->isAssoc($readSqlConfig);
        list($sql, $sqlParams, $errors) = $this->getSqlAndParams($readSqlConfig, $isFirstCall, $configKeys, $useResultSet);
        if (!empty($errors)) {
            throw new \Exception($errors, HttpStatus::$InternalServerError);
        }

        $this->db->execDbQuery($sql, $sqlParams);
        if ($row =  $this->db->fetch()) {
            //check if selected column-name mismatches or confliects with configured module/submodule names
            if (isset($readSqlConfig['__SUB-QUERY__'])) {
                $subQueryKeys = array_keys($readSqlConfig['__SUB-QUERY__']);
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
            $this->dataEncode->addKeyData($key, $value);
        }
        $this->db->closeCursor();

        if (isset($readSqlConfig['__SUB-QUERY__'])) {
            $this->callReadDB($readSqlConfig, $configKeys, $row, $useResultSet);
        }
    }

    /**
     * Function to fetch row count
     *
     * @param array $readSqlConfig Read SQL configuration
     * @return void
     * @throws \Exception
     */
    private function fetchRowsCount($readSqlConfig)
    {
        $readSqlConfig['__QUERY__'] = $readSqlConfig['countQuery'];
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

        $this->db->execDbQuery($sql, $sqlParams);
        $row = $this->db->fetch();
        $this->db->closeCursor();

        $totalRowsCount = $row['count'];
        $totalPages = ceil($totalRowsCount/$this->c->httpRequest->session['payload']['perpage']);

        $this->dataEncode->addKeyData('page', $this->c->httpRequest->session['payload']['page']);
        $this->dataEncode->addKeyData('perpage', $this->c->httpRequest->session['payload']['perpage']);
        $this->dataEncode->addKeyData('totalPages', $totalPages);
        $this->dataEncode->addKeyData('totalRecords', $totalRowsCount);
    }

    /**
     * Function to fetch multiple record
     *
     * @param array   $readSqlConfig Read SQL configuration
     * @param boolean $isFirstCall   true to represent the first call in recursion
     * @param array   $configKeys    Config Keys
     * @param boolean $useResultSet  Use result set recursively flag
     * @return void
     * @throws \Exception
     */
    private function fetchMultipleRows(&$readSqlConfig, $isFirstCall, &$configKeys, $useResultSet)
    {
        $isAssoc = $this->isAssoc($readSqlConfig);

        list($sql, $sqlParams, $errors) = $this->getSqlAndParams($readSqlConfig, $isFirstCall, $configKeys, $useResultSet);
        if (!empty($errors)) {
            throw new \Exception($errors, HttpStatus::$InternalServerError);
        }

        if ($isFirstCall) {
            if (isset($this->c->httpRequest->session['payload']['orderBy'])) {
                $orderByStrArr = [];
                $orderByArr = $this->c->httpRequest->session['payload']['orderBy'];
                foreach ($orderByArr as $k => $v) {
                    $k = str_replace(['`',' '], '', $k);
                    $v = strtoupper($v);
                    if (in_array($v,['ASC','DESC'])) {
                        $orderByStrArr[] = "`{$k}` {$v}";
                    }
                }
                if (count($orderByStrArr) > 0) {
                    $sql .= ' ORDER BY '.implode(', ', $orderByStrArr);
                }
            }
        }

        if (isset($readSqlConfig['countQuery'])) {
            $start = $this->c->httpRequest->session['payload']['start'];
            $offset = $this->c->httpRequest->session['payload']['perpage'];
            $sql .= " LIMIT {$start}, {$offset}";
        }

        $singleColumn = false;
        $pushPop = true;
        $this->db->execDbQuery($sql, $sqlParams, $pushPop);
        for ($i = 0; $row = $this->db->fetch(\PDO::FETCH_ASSOC);) {
            if ($i===0) {
                if (count($row) === 1) {
                    $singleColumn = true;
                }
                $singleColumn = $singleColumn && !isset($readSqlConfig['__SUB-QUERY__']);
                $i++;
            }
            if ($singleColumn) {
                $this->dataEncode->encode($row[key($row)]);
            } else if (isset($readSqlConfig['__SUB-QUERY__'])) {
                $this->dataEncode->startObject();
                foreach($row as $key => $value) {
                    $this->dataEncode->addKeyData($key, $value);
                }
                $this->callReadDB($readSqlConfig, $configKeys, $row, $useResultSet);
                $this->dataEncode->endObject();
            } else {
                $this->dataEncode->encode($row);
            }
        }
        $this->db->closeCursor($pushPop);
    }

    /**
     * Validate and call readDB
     *
     * @param array   $readSqlConfig Read SQL configuration
     * @param array   $configKeys    Config Keys
     * @param array   $row           Row data fetched from DB
     * @param boolean $useResultSet  Use result set recursively flag
     * @return void
     */
    private function callReadDB(&$readSqlConfig, &$configKeys, $row, $useResultSet)
    {
        if ($useResultSet && !empty($row)) {
            $this->resetFetchData($dataPayloadType = 'sqlResults', $configKeys, $row);
        }

        if (isset($readSqlConfig['__SUB-QUERY__']) && $this->isAssoc($readSqlConfig['__SUB-QUERY__'])) {
            foreach ($readSqlConfig['__SUB-QUERY__'] as $subQuery_key => &$_readSqlConfig) {
                $_configKeys = $configKeys;
                $_configKeys[] = $subQuery_key;
                $_useResultSet = ($useResultSet) ?? $this->getUseHierarchy($_readSqlConfig, 'useResultSet');
                $this->readDB($_readSqlConfig, $isFirstCall = false, $_configKeys, $_useResultSet);
            }
        }
    }
}
