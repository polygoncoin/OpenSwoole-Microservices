<?php

/**
 * Write APIs
 * php version 8.3
 *
 * @category  WriteAPI
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
use Microservices\App\Web;

/**
 * Write APIs
 * php version 8.3
 *
 * @category  WriteAPIs
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Write
{
    use AppTrait;

    /**
     * Hook object
     *
     * @var null|Hook
     */
    private $hook = null;

    /**
     * Operate DML As Transactions
     *
     * @var null|Web
     */
    private $operateAsTransaction = null;

    /**
     * JSON Encode object
     *
     * @var null|DataEncode
     */
    public $dataEncode = null;

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
        $this->dataEncode = &$this->api->res->dataEncode;
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
        $wSqlConfig = include $this->api->req->rParser->sqlConfigFile;

        // Rate Limiting request if configured for Route Queries.
        $this->rateLimitRoute(sqlConfig: $wSqlConfig);

        // Use results in where clause of sub queries recursively
        $useHierarchy = $this->getUseHierarchy(
            sqlConfig: $wSqlConfig,
            keyword: 'useHierarchy'
        );

        if (Env::$allowConfigRequest) {
            if ($this->api->req->rParser->isConfigRequest) {
                $this->processWriteConfig(
                    wSqlConfig: $wSqlConfig,
                    useHierarchy: $useHierarchy
                );
                return true;
            }
            if ($this->api->req->rParser->isImportSampleRequest) {
                $filename = date('Ymd-His') . '-import-sample.csv';
                $headers = [];
                // Export headers
                $headers['Content-type'] = 'text/csv';
                $headers['Content-Disposition'] = "attachment; filename={$filename}";
                $headers['Pragma'] = 'no-cache';
                $headers['Expires'] = '0';

                $csv = $this->processImportConfig(
                    wSqlConfig: $wSqlConfig,
                    useHierarchy: $useHierarchy
                );

                return [$headers, $csv, HttpStatus::$Ok];
            }
        }

        $this->dataEncode->XSLT = $wSqlConfig['XSLT'] ?? null;

        // Lag Response
        $this->lagResponse(sqlConfig: $wSqlConfig);

        // Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
        $this->operateAsTransaction = isset($wSqlConfig['isTransaction']) ?
            $wSqlConfig['isTransaction'] : false;

        // Set Server mode to execute query on - Read / Write Server
        DbFunctions::setDbConnection($this->api->req, fetchFrom: 'Master');

        $this->processWrite(
            wSqlConfig: $wSqlConfig,
            useHierarchy: $useHierarchy
        );
        if (isset($wSqlConfig['affectedCacheKeys'])) {
            for (
                $i = 0, $iCount = count(value: $wSqlConfig['affectedCacheKeys']);
                $i < $iCount;
                $i++
            ) {
                DbFunctions::delQueryCache(
                    cacheKey: $wSqlConfig['affectedCacheKeys'][$i]
                );
            }
        }

        return true;
    }

    /**
     * Process write function for configuration
     *
     * @param array $wSqlConfig   Config from file
     * @param bool  $useHierarchy Use results in where clause of sub queries
     *
     * @return void
     */
    private function processWriteConfig(&$wSqlConfig, $useHierarchy): void
    {
        $this->dataEncode->startObject(key: 'Config');
        $this->dataEncode->addKeyData(
            key: 'Route',
            data: $this->api->req->rParser->configuredRoute
        );
        $this->dataEncode->addKeyData(
            key: 'Payload',
            data: $this->getConfigParams(
                sqlConfig: $wSqlConfig,
                isFirstCall: true,
                flag: $useHierarchy
            )
        );
        $this->dataEncode->endObject();
    }

    /**
     * Process Function to insert/update
     *
     * @param array $wSqlConfig   Config from file
     * @param bool  $useHierarchy Use results in where clause of sub queries
     *
     * @return void
     * @throws \Exception
     */
    private function processWrite(&$wSqlConfig, $useHierarchy): void
    {
        // Check for payloadType
        if (isset($wSqlConfig['__PAYLOAD-TYPE__'])) {
            $payloadType = $this->api->req->s['payloadType'];
            if ($payloadType !== $wSqlConfig['__PAYLOAD-TYPE__']) {
                throw new \Exception(
                    message: 'Invalid payload type',
                    code: HttpStatus::$BadRequest
                );
            }
            // Check for maximum objects supported when payloadType is Array
            if (
                $wSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
                && isset($wSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
                && ($objCount = $this->api->req->dataDecode->count())
                && ($objCount > $wSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
            ) {
                throw new \Exception(
                    message: 'Maximum supported payload count is ' .
                        $wSqlConfig['__MAX-PAYLOAD-OBJECTS__'],
                    code: HttpStatus::$BadRequest
                );
            }
        }

        // Set necessary fields
        $this->api->req->s['necessaryArr'] = $this->getRequired(
            sqlConfig: $wSqlConfig,
            isFirstCall: true,
            flag: $useHierarchy
        );

        if ($this->api->req->s['payloadType'] === 'Object') {
            $this->dataEncode->startObject(key: 'Results');
        } else {
            $this->dataEncode->startObject(key: 'Results');
            if (in_array(Env::$oRepresentation, ['XML', 'HTML'])) {
                $this->dataEncode->startArray(key: 'Rows');
            }
        }

        // Perform action
        $iCount = $this->api->req->s['payloadType'] === 'Object' ?
            1 : $this->api->req->dataDecode->count();

        for ($i = 0; $i < $iCount; $i++) {
            $configKeys = [];
            $payloadIndexes = [];
            if ($i === 0) {
                if ($this->api->req->s['payloadType'] === 'Object') {
                    $payloadIndexes[] = '';
                } else {
                    $payloadIndexes[] = "{$i}";
                }
            } else {
                $payloadIndexes[] = "{$i}";
            }

            // Check for Idempotent Window
            [$idempotentWindow, $hashKey, $hashJson] = $this->checkIdempotent(
                sqlConfig: $wSqlConfig,
                payloadIndexes: $payloadIndexes
            );

            // Begin DML operation
            if ($hashJson === null) {
                if ($this->operateAsTransaction) {
                    DbFunctions::$masterDb[$this->api->req->cId]->begin();
                }
                $response = [];
                $this->writeDB(
                    wSqlConfig: $wSqlConfig,
                    payloadIndexes: $payloadIndexes,
                    configKeys: $configKeys,
                    useHierarchy: $useHierarchy,
                    response: $response,
                    necessary: $this->api->req->s['necessaryArr']
                );

                if ($this->api->res->httpStatus === HttpStatus::$Ok)
                {
                    if (
                        $this->operateAsTransaction
                        && (DbFunctions::$masterDb[$this->api->req->cId]->beganTransaction === true)
                    ) {
                        DbFunctions::$masterDb[$this->api->req->cId]->commit();
                    }

                    $arr = [
                        'Status' => HttpStatus::$Ok,
                        'Payload' => $this->api->req->dataDecode->getCompleteArray(
                            keys: implode(
                                separator: ':',
                                array: $payloadIndexes
                            )
                        ),
                        'Response' => $response
                    ];
                    if ($idempotentWindow) {
                        DbFunctions::$gCacheServer->setCache(
                            key: $hashKey,
                            value: json_encode(value: $arr),
                            expire: $idempotentWindow
                        );
                    }
                } else { // Failure
                    $arr = [
                        'Status' => $this->api->res->httpStatus,
                        'Payload' => $this->api->req->dataDecode->getCompleteArray(
                            keys: implode(
                                separator: ':',
                                array: $payloadIndexes
                            )
                        ),
                        'Error' => $response
                    ];
                }
            } else {
                $arr = json_decode(json: $hashJson, associative: true);
            }

            if ($payloadIndexes[0] === '') {
                foreach ($arr as $k => $v) {
                    $this->dataEncode->addKeyData(key: $k, data: $v);
                }
            } else {
                if (in_array(Env::$oRepresentation, ['XML', 'HTML'])) {
                    $this->dataEncode->startObject(key: 'Row');
                    foreach ($arr as $k => $v) {
                        $this->dataEncode->addKeyData(key: $k, data: $v);
                    }
                    $this->dataEncode->endObject();
                } else {
                    $this->dataEncode->addKeyData(key: $i, data: $arr);
                }
            }
        }

        if ($this->api->req->s['payloadType'] === 'Object') {
            $this->dataEncode->endObject();
        } else {
            if (in_array(Env::$oRepresentation, ['XML', 'HTML'])) {
                $this->dataEncode->endArray();
            }
            $this->dataEncode->endObject();
        }
    }

    /**
     * Function to insert/update sub queries recursively
     *
     * @param array $wSqlConfig     Config from file
     * @param array $payloadIndexes Payload Indexes
     * @param array $configKeys     Config Keys
     * @param bool  $useHierarchy   Use results in where clause of sub queries
     * @param array $response       Response by reference
     * @param array $necessary      Required fields
     *
     * @return void
     * @throws \Exception
     */
    private function writeDB(
        &$wSqlConfig,
        $payloadIndexes,
        $configKeys,
        $useHierarchy,
        &$response,
        &$necessary
    ): void {
        $payloadIndex = is_array(value: $payloadIndexes) ?
            trim(
                string: implode(
                    separator: ':',
                    array: $payloadIndexes
                ),
                characters: ':'
            ) : '';

        $isObject = $this->api->req->dataDecode->dataType(
            keys: $payloadIndex
        ) === 'Object';

        $iCount = $isObject ?
            1 : $this->api->req->dataDecode->count(keys: $payloadIndex);

        $modeColumn = 'master_parameterized_query_mode';
        $mode = getenv(name: $this->api->req->s['cDetails'][$modeColumn]);
        $fn = "getSqlAndParams{$mode}Mode";

        for ($i = 0; $i < $iCount; $i++) {
            if ($isObject) {
                $_response = &$response;
            } else {
                $response[$i] = [];
                $_response = &$response[$i];
            }

            $payloadIndexes = $payloadIndexes;
            if ($this->operateAsTransaction && !DbFunctions::$masterDb[$this->api->req->cId]->beganTransaction) {
                $_response['Error'] = 'Transaction rolled back';
                return;
            }

            if ($isObject && $i > 0) {
                return;
            }

            if (!$isObject && !$useHierarchy) {
                array_push($payloadIndexes, $i);
            }
            $payloadIndex = is_array(value: $payloadIndexes) ?
                implode(separator: ':', array: $payloadIndexes) : '';

            if (!$this->api->req->dataDecode->isset(keys: $payloadIndex)) {
                throw new \Exception(
                    message: "Payload key '{$payloadIndex}' not set",
                    code: HttpStatus::$NotFound
                );
            }

            $this->api->req->s['payload'] = $this->api->req->dataDecode->get(
                keys: $payloadIndex
            );

            if (count(value: $necessary)) {
                $this->api->req->s['necessary'] = $necessary;
            } else {
                $this->api->req->s['necessary'] = [];
            }

            // Validation
            if (
                isset($wSqlConfig['__VALIDATE__'])
                && !$this->isValidPayload(wSqlConfig: $wSqlConfig, response: $_response)
            ) {
                continue;
            }

            // Execute Pre Sql Hooks
            if (isset($wSqlConfig['__PRE-SQL-HOOKS__'])) {
                if ($this->hook === null) {
                    $this->hook = new Hook($this->api);
                }
                $this->hook->triggerHook(
                    hookConfig: $wSqlConfig['__PRE-SQL-HOOKS__']
                );
            }

            // Get Sql and Params
            [$id, $sql, $sqlParams, $errors, $missExecution] = $this->$fn(
                sqlDetails: $wSqlConfig
            );

            if (!empty($errors)) {
                $_response['Error'] = $errors;
                DbFunctions::$masterDb[$this->api->req->cId]->rollBack();
                return;
            }

            if ($missExecution) {
                return;
            }

            // Execute Query
            DbFunctions::$masterDb[$this->api->req->cId]->execDbQuery(sql: $sql, params: $sqlParams);
            if ($this->operateAsTransaction && !DbFunctions::$masterDb[$this->api->req->cId]->beganTransaction) {
                $_response['Error'] = 'Something went wrong';
                return;
            }

            if (isset($wSqlConfig['__INSERT-IDs__'])) {
                if (!Env::$useGlobalCounter) {
                    $id = DbFunctions::$masterDb[$this->api->req->cId]->lastInsertId();
                }
                $_response[$wSqlConfig['__INSERT-IDs__']] = $id;
                $this->api->req->s['__INSERT-IDs__'][$wSqlConfig['__INSERT-IDs__']] = $id;
            } else {
                $affectedRows = DbFunctions::$masterDb[$this->api->req->cId]->affectedRows();
                $_response['affectedRows'] = $affectedRows;
            }
            DbFunctions::$masterDb[$this->api->req->cId]->closeCursor();

            // triggers
            if (isset($wSqlConfig['__TRIGGERS__'])) {
                $this->dataEncode->addKeyData(
                    key: '__TRIGGERS__',
                    data: $this->getTriggerData(
                        triggerConfig: $wSqlConfig['__TRIGGERS__']
                    )
                );
            }

            // Execute Post Sql Hooks
            if (isset($wSqlConfig['__POST-SQL-HOOKS__'])) {
                if ($this->hook === null) {
                    $this->hook = new Hook($this->api);
                }
                $this->hook->triggerHook(
                    hookConfig: $wSqlConfig['__POST-SQL-HOOKS__']
                );
            }

            // subQuery for payload
            if (isset($wSqlConfig['__SUB-QUERY__'])) {
                $this->callWriteDB(
                    wSqlConfig: $wSqlConfig,
                    payloadIndexes: $payloadIndexes,
                    configKeys: $configKeys,
                    useHierarchy: $useHierarchy,
                    response: $_response,
                    necessary: $necessary
                );
            }
        }
    }

    /**
     * Validate and call _writeDB
     *
     * @param array $wSqlConfig     Config from file
     * @param array $payloadIndexes Payload Indexes
     * @param array $configKeys     Config Keys
     * @param bool  $useHierarchy   Use results in where clause of sub queries
     * @param array $response       Response by reference
     * @param array $necessary      Required fields
     *
     * @return void
     */
    private function callWriteDB(
        &$wSqlConfig,
        $payloadIndexes,
        $configKeys,
        $useHierarchy,
        &$response,
        &$necessary
    ): void {
        if ($useHierarchy) {
            $row = $this->api->req->s['payload'];
            $this->resetFetchData(
                fetchFrom: 'sqlPayload',
                keys: $configKeys,
                row: $row
            );
        }

        if (isset($payloadIndexes[0]) && $payloadIndexes[0] === '') {
            $payloadIndexes = array_shift($payloadIndexes);
        }
        if (!is_array(value: $payloadIndexes)) {
            $payloadIndexes = [];
        }

        if (
            isset($wSqlConfig['__SUB-QUERY__'])
            && $this->isObject(arr: $wSqlConfig['__SUB-QUERY__'])
        ) {
            foreach ($wSqlConfig['__SUB-QUERY__'] as $module => &$wSqlConfig) {
                $dataExists = false;
                $modulePayloadIndex = $payloadIndexes;
                $moduleConfigKeys = $configKeys;
                array_push($modulePayloadIndex, $module);
                array_push($moduleConfigKeys, $module);

                $modulePayloadIndexKey = is_array(value: $modulePayloadIndex) ?
                    implode(separator: ':', array: $modulePayloadIndex) : '';
                $isObject = $this->api->req->dataDecode->dataType(
                    keys: $modulePayloadIndexKey
                ) === 'Object';

                $iCount = $isObject ?
                    1 : $this->api->req->dataDecode->count(keys: $modulePayloadIndexKey);

                for ($i = 0; $i < $iCount; $i++) {
                    $modulePayloadIndexItt = $modulePayloadIndex;
                    if ($isObject) {
                        $modulePayloadIndexIttKey = $modulePayloadIndexKey;
                    } else {
                        $modulePayloadIndexIttKey = "{$modulePayloadIndexKey}:{$i}";
                        array_push($modulePayloadIndexItt, $i);
                    }

                    $dataExists = $this->api->req->dataDecode->isset(
                        keys: $modulePayloadIndexIttKey
                    );

                    if ($useHierarchy && !$dataExists) { // use parent data of a payload
                        throw new \Exception(
                            message: "Invalid payload: Module '{$module}' missing",
                            code: HttpStatus::$NotFound
                        );
                    }
                    if ($dataExists) {
                        $necessary = $necessary[$module] ?? $necessary;
                        $useHierarchy = $useHierarchy ?? $this->getUseHierarchy(
                            sqlConfig: $wSqlConfig,
                            keyword: 'useHierarchy'
                        );
                        $response[$module] = [];
                        $response = &$response[$module];
                        $this->writeDB(
                            wSqlConfig: $wSqlConfig,
                            payloadIndexes: $modulePayloadIndexItt,
                            configKeys: $moduleConfigKeys,
                            useHierarchy: $useHierarchy,
                            response: $response,
                            necessary: $necessary
                        );
                    }
                }
            }
        }
    }

    /**
     * Checks if the payload is valid
     *
     * @param array $wSqlConfig Config from file
     * @param array $response   Response by reference
     *
     * @return bool
     */
    private function isValidPayload($wSqlConfig, &$response): bool
    {
        $return = true;
        $isValidData = true;
        if (isset($wSqlConfig['__VALIDATE__'])) {
            [$isValidData, $errors] = $this->validate(
                validationConfig: $wSqlConfig['__VALIDATE__']
            );
            if ($isValidData !== true) {
                $this->api->res->httpStatus = HttpStatus::$BadRequest;
                $response = $errors;
                $return = false;
            }
        }
        return $return;
    }
}
