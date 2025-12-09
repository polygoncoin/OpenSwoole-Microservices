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
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\HttpStatus;

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
     * Fetch mode Db Obj variable name in DbFunctions
     *
     * @var null|string
     */
    public $dbObj = null;

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
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
     * @return bool|array
     */
    public function process(): bool|array
    {
        $Env = __NAMESPACE__ . '\Env';

        // Load Queries
        $rSqlConfig = include $this->api->req->rParser->sqlConfigFile;

        // Rate Limiting request if configured for Route Queries.
        $this->rateLimitRoute(sqlConfig: $rSqlConfig);

        // Lag Response
        $this->lagResponse(sqlConfig: $rSqlConfig);

        if (isset($rSqlConfig['__DOWNLOAD__'])) {
            return $this->download($rSqlConfig);
        }

        // Check for cache
        $toBeCached = false;
        if (
            isset($rSqlConfig['cacheKey'])
            && !isset($this->api->req->s['queryParams']['orderBy'])
        ) {
            $json = DbFunctions::getQueryCache(
                cacheKey: $rSqlConfig['cacheKey']
            );
            if ($json !== null) {
                $cacheHit = 'true';
                $this->api->res->dataEncode->appendKeyData(
                    key: 'cacheHit',
                    data: $cacheHit
                );
                $this->api->res->dataEncode->appendData(data: $json);
                return true;
            } else {
                $toBeCached = true;
            }
        }

        if ($toBeCached) {
            $this->dataEncode = new DataEncode(http: $this->api->http);
            $this->dataEncode->init(header: false);
        } else {
            $this->dataEncode = &$this->api->res->dataEncode;
        }
        $this->dataEncode->XSLT = isset($rSqlConfig['XSLT']) ?
            $rSqlConfig['XSLT'] : null;

        // Set Server mode to execute query on - Read / Write Server
        $fetchFrom = $rSqlConfig['fetchFrom'] ?? 'Slave';
        DbFunctions::setDbConnection($this->api->req, fetchFrom: $fetchFrom);
        $this->dbObj = strtolower($fetchFrom) . 'Db';

        // Use result set recursively flag
        $useResultSet = $this->getUseHierarchy(
            sqlConfig: $rSqlConfig,
            keyword: 'useResultSet'
        );

        if (
            Env::$allowConfigRequest
            && $this->api->req->rParser->isConfigRequest
        ) {
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
            DbFunctions::setQueryCache(
                cacheKey: $rSqlConfig['cacheKey'],
                json: $json
            );
            $this->api->res->dataEncode->appendData(data: $json);
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
            data: $this->api->req->rParser->configuredRoute
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
        $this->api->req->s['necessaryArr'] = $this->getRequired(
            sqlConfig: $rSqlConfig,
            isFirstCall: true,
            flag: $useResultSet
        );

        if (isset($this->api->req->s['necessaryArr'])) {
            $this->api->req->s['necessary'] = $this->api->req->s['necessaryArr'];
        } else {
            $this->api->req->s['necessary'] = [];
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
                $this->hook = new Hook($this->api);
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
                        if (isset($rSqlConfig['countQuery'])) {
                            $this->dataEncode->startObject(key: 'Results');
                            $this->fetchRowsCount(rSqlConfig: $rSqlConfig);
                            $this->dataEncode->startArray(key: 'Data');
                        } else {
                            $this->dataEncode->startArray(key: 'Results');
                        }
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
            $this->dataEncode->addKeyData(
                key: '__TRIGGERS__',
                data: $this->getTriggerData(
                    triggerConfig: $rSqlConfig['__TRIGGERS__']
                )
            );
        }

        // Execute Post Sql Hooks
        if (isset($rSqlConfig['__POST-SQL-HOOKS__'])) {
            if ($this->hook === null) {
                $this->hook = new Hook($this->api);
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
        $fn = 'getSqlAndParams' . Env::$parameterisedQueryMode . 'Mode';
        [$id, $sql, $sqlParams, $errors, $missExecution] = $this->$fn(
            sqlDetails: $rSqlConfig,
            configKeys: $configKeys
        );

        if (!empty($errors)) {
            throw new \Exception(
                message: $errors,
                code: HttpStatus::$InternalServerError
            );
        }

        if ($missExecution) {
            return;
        }

        $dbObj = $this->dbObj;
        (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->execDbQuery(sql: $sql, params: $sqlParams);
        if ($row =  (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->fetch()) {
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
        } else {
            if ($isFirstCall) {
                $this->api->res->httpStatus = HttpStatus::$NotFound;
                return;
            }
        }
        (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->closeCursor();

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
        if (!isset($rSqlConfig['countQuery'])) {
            return;
        }
        $rSqlConfig['__QUERY__'] = $rSqlConfig['countQuery'];
        if (isset($rSqlConfig['__COUNT-SQL-COMMENT__'])) {
            $rSqlConfig['__SQL-COMMENT__'] = $rSqlConfig['__COUNT-SQL-COMMENT__'];
        }
        unset($rSqlConfig['__COUNT-SQL-COMMENT__']);
        unset($rSqlConfig['countQuery']);

        $this->api->req->s['queryParams']['page']  = $this->api->http['get']['page'] ?? 1;
        $this->api->req->s['queryParams']['perPage']  = $this->api->http['get']['perPage'] ??
            Env::$defaultPerPage;

        if ($this->api->req->s['queryParams']['perPage'] > Env::$maxResultsPerPage) {
            throw new \Exception(
                message: 'perPage exceeds max perPage value of ' . Env::$maxResultsPerPage,
                code: HttpStatus::$Forbidden
            );
        }

        $this->api->req->s['queryParams']['start'] = (
            ($this->api->req->s['queryParams']['page'] - 1) *
            $this->api->req->s['queryParams']['perPage']
        );
        $fn = 'getSqlAndParams' . Env::$parameterisedQueryMode . 'Mode';
        [$id, $sql, $sqlParams, $errors, $missExecution] = $this->$fn(
            sqlDetails: $rSqlConfig
        );

        if (!empty($errors)) {
            throw new \Exception(
                message: $errors,
                code: HttpStatus::$InternalServerError
            );
        }

        if ($missExecution) {
            return;
        }

        $dbObj = $this->dbObj;
        (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->execDbQuery(sql: $sql, params: $sqlParams);
        $row = (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->fetch();
        (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->closeCursor();

        $totalRowsCount = $row['count'];
        $totalPages = ceil(
            num: $totalRowsCount / $this->api->req->s['queryParams']['perPage']
        );

        $this->dataEncode->addKeyData(
            key: 'page',
            data: $this->api->req->s['queryParams']['page']
        );
        $this->dataEncode->addKeyData(
            key: 'perPage',
            data: $this->api->req->s['queryParams']['perPage']
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
        $fn = 'getSqlAndParams' . Env::$parameterisedQueryMode . 'Mode';
        [$id, $sql, $sqlParams, $errors, $missExecution] = $this->$fn(
            sqlDetails: $rSqlConfig,
            configKeys: $configKeys
        );

        if (!empty($errors)) {
            throw new \Exception(
                message: $errors,
                code: HttpStatus::$InternalServerError
            );
        }

        if ($missExecution) {
            return;
        }

        if ($isFirstCall) {
            if (isset($this->api->req->s['queryParams']['orderBy'])) {
                $orderByStrArr = [];
                $orderByArr = json_decode(
                    json: $this->api->req->s['queryParams']['orderBy'],
                    associative: true
                );
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
            $start = $this->api->req->s['queryParams']['start'];
            $offset = $this->api->req->s['queryParams']['perPage'];
            $sql .= " LIMIT {$start}, {$offset}";
        }

        $singleColumn = false;
        $pushPop = true;
        $dbObj = $this->dbObj;
        (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->execDbQuery(sql: $sql, params: $sqlParams, pushPop: $pushPop);
        for ($i = 0; $row = (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->fetch();) {
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
        (DbFunctions::$$dbObj)[$this->api->req->s['cDetails']['id']]->closeCursor(pushPop: $pushPop);
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

    /**
     * Validate and call readDB
     *
     * @param array $rSqlConfig   Read SQL configuration
     *
     * @return array
     */
    private function download($rSqlConfig): array
    {
        $return = [[], '', HttpStatus::$Ok];

        if (!Env::$allowExport) {
            return [[], '', HttpStatus::$NotFound];
        }

        $fn = 'getSqlAndParams' . Env::$parameterisedQueryMode . 'Mode';
        [$id, $sql, $sqlParams, $errors, $missExecution] = $this->$fn(
            sqlDetails: $rSqlConfig
        );
        $serverMode = isset($rSqlConfig['fetchFrom'])
            ? $rSqlConfig['fetchFrom'] : 'Slave';

        $dbDetails = [];
        switch ($serverMode) {
            case 'Master':
                $dbDetails = DbFunctions::getDbMasterDetails($this->api->req);
                break;
            case 'Slave':
                $dbDetails = DbFunctions::getDbSlaveDetails($this->api->req);
                break;
        }

        // Export
        $export = new Export(api: $this->api, dbServerType: $dbDetails['dbServerType']);
        $export->init(
            hostname: $dbDetails['dbHostname'],
            port: $dbDetails['dbPort'],
            username: $dbDetails['dbUsername'],
            password: $dbDetails['dbPassword'],
            database: $dbDetails['dbDatabase']
        );

        if (isset($rSqlConfig['downloadFile'])) {
            $downloadFile = date('Ymd-His') . '-' . $rSqlConfig['downloadFile'];
            if (
                isset($rSqlConfig['exportFile'])
                && !empty($rSqlConfig['exportFile'])
            ) {
                $return = $export->initDownload(
                    downloadFile: $downloadFile,
                    sql: $sql,
                    params: $sqlParams,
                    exportFile: $rSqlConfig['exportFile']
                );
            } else {
                $return = $export->initDownload(
                    downloadFile: $downloadFile,
                    sql: $sql,
                    params: $sqlParams
                );
            }
        } else {
            if (isset($rSqlConfig['exportFile'])) {
                $return = $export->saveExport(
                    sql: $sql,
                    params: $sqlParams,
                    exportFile: $rSqlConfig['exportFile']
                );
            }
        }

        return $return;
    }
}
