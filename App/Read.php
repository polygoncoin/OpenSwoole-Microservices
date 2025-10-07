<?php

/**
 * Read APIs
 * php version 8.3
 *
 * @category  ReadAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Common;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\HttpStatus;
use Microservices\App\Web;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Read APIs
 * php version 8.3
 *
 * @category  ReadAPIs
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Read
{
    use AppTrait;

    /**
     * Database object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Trigger Web API object
     *
     * @var null|Web
     */
    private $web = null;

    /**
     * Hook object
     *
     * @var null|Hook
     */
    private $hook = null;

    /**
     * JSON Encode object
     *
     * @var null|DataEncode
     */
    public $dataEncode = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        $Env = __NAMESPACE__ . '\Env';

        // Load Queries
        $rSqlConfig = include $this->c->req->rParser->sqlConfigFile;

        // Rate Limiting request if configured for Route Queries.
        $this->rateLimitRoute(sqlConfig: $rSqlConfig);

        // Lag Response
        $this->lagResponse(sqlConfig: $rSqlConfig);

        // Check for cache
        $toBeCached = false;
        if (
            isset($rSqlConfig['cacheKey'])
            && !isset($this->c->req->s['payload']['orderBy'])
        ) {
            $json = $this->c->req->getDqlCache(
                cacheKey: $rSqlConfig['cacheKey']
            );
            if ($json !== null) {
                $cacheHit = 'true';
                $this->c->res->dataEncode->appendKeyData(
                    key: 'cacheHit',
                    data: $cacheHit
                );
                $this->c->res->dataEncode->appendData(data: $json);
                return true;
            } else {
                $toBeCached = true;
            }
        }

        if ($toBeCached) {
            $this->dataEncode = new DataEncode(http: $this->c->http);
            $this->dataEncode->init(header: false);
        } else {
            $this->dataEncode = &$this->c->res->dataEncode;
        }
        $this->dataEncode->XSLT = isset($rSqlConfig['XSLT']) ?
            $rSqlConfig['XSLT'] : null;

        // Set Server mode to execute query on - Read / Write Server
        $fetchFrom = $rSqlConfig['fetchFrom'] ?? 'Slave';
        $this->c->req->db = $this->c->req->setDbConnection(fetchFrom: $fetchFrom);
        $this->db = &$this->c->req->db;

        // Use result set recursively flag
        $useResultSet = $this->getUseHierarchy(
            sqlConfig: $rSqlConfig,
            keyword: 'useResultSet'
        );

        if (Env::$allowConfigRequest && $this->c->req->rParser->isConfigRequest) {
            $this->processReadConfig(
                rSqlConfig: $rSqlConfig,
                useResultSet: $useResultSet
            );
        } else {
            $this->processRead(
                rSqlConfig: $rSqlConfig,
                useResultSet: $useResultSet
            );
        }

        if ($toBeCached) {
            $json = $this->dataEncode->getData();
            $this->c->req->setDmlCache(
                cacheKey: $rSqlConfig['cacheKey'],
                json: $json
            );
            $this->c->res->dataEncode->appendData(data: $json);
        }

        return true;
    }

    /**
     * Process read function for configuration
     *
     * @param array $rSqlConfig   Config from file
     * @param bool  $useResultSet Use result set recursively flag
     *
     * @return void
     */
    private function processReadConfig(&$rSqlConfig, $useResultSet): void
    {
        $this->dataEncode->startObject(key: 'Config');
        $this->dataEncode->addKeyData(
            key: 'Route',
            data: $this->c->req->rParser->configuredUri
        );
        $this->dataEncode->addKeyData(
            key: 'Payload',
            data: $this->getConfigParams(
                sqlConfig: $rSqlConfig,
                isFirstCall: true,
                flag: $useResultSet
            )
        );
        $this->dataEncode->endObject();
    }

    /**
     * Process Function for read operation
     *
     * @param array $rSqlConfig   Config from file
     * @param bool  $useResultSet Use result set recursively flag
     *
     * @return void
     */
    private function processRead(&$rSqlConfig, $useResultSet): void
    {
        $this->c->req->s['necessaryArr'] = $this->getRequired(
            sqlConfig: $rSqlConfig,
            isFirstCall: true,
            flag: $useResultSet
        );

        if (isset($this->c->req->s['necessaryArr'])) {
            $this->c->req->s['necessary'] = $this->c->req->s['necessaryArr'];
        } else {
            $this->c->req->s['necessary'] = [];
        }

        // Start Read operation
        $configKeys = [];
        $this->readDB(
            rSqlConfig: $rSqlConfig,
            isFirstCall: true,
            configKeys: $configKeys,
            useResultSet: $useResultSet
        );
    }

    /**
     * Function to select sub queries recursively
     *
     * @param array $rSqlConfig   Config from file
     * @param bool  $isFirstCall  true to represent the first call in recursion
     * @param array $configKeys   Keys in recursion
     * @param bool  $useResultSet Use result set recursively flag
     *
     * @return void
     */
    private function readDB(
        &$rSqlConfig,
        $isFirstCall,
        &$configKeys,
        $useResultSet
    ): void {
        $isObject = $this->isObject(arr: $rSqlConfig);

        // Execute Pre Sql Hooks
        if (isset($rSqlConfig['__PRE-SQL-HOOKS__'])) {
            if ($this->hook === null) {
                $this->hook = new Hook(common: $this->c);
            }
            $this->hook->triggerHook(
                hookConfig: $rSqlConfig['__PRE-SQL-HOOKS__']
            );
        }

        if ($isObject) {
            switch ($rSqlConfig['__MODE__']) {
                // Query will return single row
                case 'singleRowFormat':
                    if ($isFirstCall) {
                        $this->dataEncode->startObject(key: 'Results');
                    } else {
                        $this->dataEncode->startObject();
                    }
                    $this->fetchSingleRow(
                        rSqlConfig: $rSqlConfig,
                        isFirstCall: $isFirstCall,
                        configKeys: $configKeys,
                        useResultSet: $useResultSet
                    );
                    $this->dataEncode->endObject();
                    break;
                // Query will return multiple rows
                case 'multipleRowFormat':
                    if ($isFirstCall) {
                        $this->dataEncode->startObject(key: 'Results');
                        if (isset($rSqlConfig['countQuery'])) {
                            $this->fetchRowsCount(rSqlConfig: $rSqlConfig);
                        }
                        $this->dataEncode->startArray(key: 'Data');
                    } else {
                        $this->dataEncode->startArray(
                            key: $configKeys[count(value: $configKeys) - 1]
                        );
                    }
                    $this->fetchMultipleRows(
                        rSqlConfig: $rSqlConfig,
                        isFirstCall: $isFirstCall,
                        configKeys: $configKeys,
                        useResultSet: $useResultSet
                    );
                    $this->dataEncode->endArray();
                    if ($isFirstCall && isset($rSqlConfig['countQuery'])) {
                        $this->dataEncode->endObject();
                    }
                    break;
            }
        }

        // triggers
        if (isset($rSqlConfig['__TRIGGERS__'])) {
            if ($this->web === null) {
                $this->web = new Web(common: $this->c);
            }
            $this->dataEncode->addKeyData(
                key: '__TRIGGERS__',
                data: $this->web->triggerConfig(
                    triggerConfig: $rSqlConfig['__TRIGGERS__']
                )
            );
        }

        // Execute Post Sql Hooks
        if (isset($rSqlConfig['__POST-SQL-HOOKS__'])) {
            if ($this->hook === null) {
                $this->hook = new Hook(common: $this->c);
            }
            $this->hook->triggerHook(
                hookConfig: $rSqlConfig['__POST-SQL-HOOKS__']
            );
        }
    }

    /**
     * Function to fetch single record
     *
     * @param array $rSqlConfig   Read SQL configuration
     * @param bool  $isFirstCall  true to represent the first call in recursion
     * @param array $configKeys   Config Keys
     * @param bool  $useResultSet Use result set recursively flag
     *
     * @return void
     * @throws \Exception
     */
    private function fetchSingleRow(
        &$rSqlConfig,
        $isFirstCall,
        &$configKeys,
        $useResultSet
    ): void {
        [$sql, $sqlParams, $errors] = $this->getSqlAndParams(
            sqlDetails: $rSqlConfig,
            isFirstCall: $isFirstCall,
            configKeys: $configKeys,
            flag: $useResultSet
        );

        if (!empty($errors)) {
            throw new \Exception(
                message: $errors,
                code: HttpStatus::$InternalServerError
            );
        }

        $this->db->execDbQuery(sql: $sql, params: $sqlParams);
        if ($row =  $this->db->fetch()) {
            foreach ($row as $key => $value) {
                $this->dataEncode->addKeyData(key: $key, data: $value);
            }
            // check if selected column-name mismatches or conflicts with
            // configured module/submodule names
            if (isset($rSqlConfig['__SUB-QUERY__'])) {
                $subQueryKeys = array_keys(array: $rSqlConfig['__SUB-QUERY__']);
                foreach ($row as $key => $value) {
                    if (in_array(needle: $key, haystack: $subQueryKeys)) {
                        throw new \Exception(
                            message: 'Invalid config: Conflicting column names',
                            code: HttpStatus::$InternalServerError
                        );
                    }
                }
            }
        }
        $this->db->closeCursor();

        if (isset($rSqlConfig['__SUB-QUERY__'])) {
            $this->callReadDB(
                rSqlConfig: $rSqlConfig,
                configKeys: $configKeys,
                row: $row,
                useResultSet: $useResultSet
            );
        }
    }

    /**
     * Function to fetch row count
     *
     * @param array $rSqlConfig Read SQL configuration
     *
     * @return void
     * @throws \Exception
     */
    private function fetchRowsCount($rSqlConfig): void
    {
        $rSqlConfig['__SQL-COMMENT__'] = $rSqlConfig['__COUNT-SQL-COMMENT__'];
        $rSqlConfig['__QUERY__'] = $rSqlConfig['countQuery'];
        unset($rSqlConfig['__COUNT-SQL-COMMENT__']);
        unset($rSqlConfig['countQuery']);

        $this->c->req->s['payload']['page']  = $_GET['page'] ?? 1;
        $this->c->req->s['payload']['perPage']  = $_GET['perPage'] ??
            Env::$defaultPerPage;

        if ($this->c->req->s['payload']['perPage'] > Env::$maxPerPage) {
            throw new \Exception(
                message: 'perPage exceeds max perPage value of ' . Env::$maxPerPage,
                code: HttpStatus::$Forbidden
            );
        }

        $this->c->req->s['payload']['start'] = (
            ($this->c->req->s['payload']['page'] - 1) *
            $this->c->req->s['payload']['perPage']
        );
        [$sql, $sqlParams, $errors] = $this->getSqlAndParams(
            sqlDetails: $rSqlConfig
        );

        if (!empty($errors)) {
            throw new \Exception(
                message: $errors,
                code: HttpStatus::$InternalServerError
            );
        }

        $this->db->execDbQuery(sql: $sql, params: $sqlParams);
        $row = $this->db->fetch();
        $this->db->closeCursor();

        $totalRowsCount = $row['count'];
        $totalPages = ceil(
            num: $totalRowsCount / $this->c->req->s['payload']['perPage']
        );

        $this->dataEncode->addKeyData(
            key: 'page',
            data: $this->c->req->s['payload']['page']
        );
        $this->dataEncode->addKeyData(
            key: 'perPage',
            data: $this->c->req->s['payload']['perPage']
        );
        $this->dataEncode->addKeyData(
            key: 'totalPages',
            data: $totalPages
        );
        $this->dataEncode->addKeyData(
            key: 'totalRecords',
            data: $totalRowsCount
        );
    }

    /**
     * Function to fetch multiple record
     *
     * @param array $rSqlConfig   Read SQL configuration
     * @param bool  $isFirstCall  true to represent the first call in recursion
     * @param array $configKeys   Config Keys
     * @param bool  $useResultSet Use result set recursively flag
     *
     * @return void
     * @throws \Exception
     */
    private function fetchMultipleRows(
        &$rSqlConfig,
        $isFirstCall,
        &$configKeys,
        $useResultSet
    ): void {
        [$sql, $sqlParams, $errors] = $this->getSqlAndParams(
            sqlDetails: $rSqlConfig,
            isFirstCall: $isFirstCall,
            configKeys: $configKeys,
            flag: $useResultSet
        );
        if (!empty($errors)) {
            throw new \Exception(
                message: $errors,
                code: HttpStatus::$InternalServerError
            );
        }

        if ($isFirstCall) {
            if (isset($this->c->req->s['payload']['orderBy'])) {
                $orderByStrArr = [];
                $orderByArr = $this->c->req->s['payload']['orderBy'];
                foreach ($orderByArr as $k => $v) {
                    $k = str_replace(search: ['`', ' '], replace: '', subject: $k);
                    $v = strtoupper(string: $v);
                    if (in_array(needle: $v, haystack: ['ASC', 'DESC'])) {
                        $orderByStrArr[] = "`{$k}` {$v}";
                    }
                }
                if (count(value: $orderByStrArr) > 0) {
                    $sql .= ' ORDER BY ' . implode(
                        separator: ', ',
                        array: $orderByStrArr
                    );
                }
            }
        }

        if (isset($rSqlConfig['countQuery'])) {
            $start = $this->c->req->s['payload']['start'];
            $offset = $this->c->req->s['payload']['perPage'];
            $sql .= " LIMIT {$start}, {$offset}";
        }

        $singleColumn = false;
        $pushPop = true;
        $this->db->execDbQuery(sql: $sql, params: $sqlParams, pushPop: $pushPop);
        for ($i = 0; $row = $this->db->fetch();) {
            if ($i === 0) {
                if (count(value: $row) === 1) {
                    $singleColumn = true;
                }
                $singleColumn = $singleColumn
                    && !isset($rSqlConfig['__SUB-QUERY__']);
                $i++;
            }
            if ($singleColumn) {
                $this->dataEncode->encode(data: $row[key(array: $row)]);
            } elseif (isset($rSqlConfig['__SUB-QUERY__'])) {
                $this->dataEncode->startObject();
                foreach ($row as $key => $value) {
                    $this->dataEncode->addKeyData(key: $key, data: $value);
                }
                $this->callReadDB(
                    rSqlConfig: $rSqlConfig,
                    configKeys: $configKeys,
                    row: $row,
                    useResultSet: $useResultSet
                );
                $this->dataEncode->endObject();
            } else {
                $this->dataEncode->encode(data: $row);
            }
        }
        $this->db->closeCursor(pushPop: $pushPop);
    }

    /**
     * Validate and call readDB
     *
     * @param array $rSqlConfig   Read SQL configuration
     * @param array $configKeys   Config Keys
     * @param array $row          Row data fetched from DB
     * @param bool  $useResultSet Use result set recursively flag
     *
     * @return void
     */
    private function callReadDB(
        &$rSqlConfig,
        &$configKeys,
        $row,
        $useResultSet
    ): void {
        if ($useResultSet && !empty($row)) {
            $this->resetFetchData(
                fetchFrom: 'sqlResults',
                keys: $configKeys,
                row: $row
            );
        }

        if (
            isset($rSqlConfig['__SUB-QUERY__'])
            && $this->isObject(arr: $rSqlConfig['__SUB-QUERY__'])
        ) {
            foreach ($rSqlConfig['__SUB-QUERY__'] as $key => &$rSqlConfig) {
                $configKeys = $configKeys;
                $configKeys[] = $key;
                $useResultSet = $useResultSet ??
                    $this->getUseHierarchy(
                        sqlConfig: $rSqlConfig,
                        keyword: 'useResultSet'
                    );
                $this->readDB(
                    rSqlConfig: $rSqlConfig,
                    isFirstCall: false,
                    configKeys: $configKeys,
                    useResultSet: $useResultSet
                );
            }
        }
    }
}
