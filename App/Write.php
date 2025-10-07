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
use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\HttpStatus;
use Microservices\App\Web;
use Microservices\App\Servers\Database\AbstractDatabase;

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
     * Session variable
     *
     * @var null|array
     */
    private $s = null;

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
     * Operate DML As Transactions
     *
     * @var null|Web
     */
    private $operateAsTransaction = null;

    /**
     * JSON Encode object
     *
     * @var null|AbstractDataEncode
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
        $this->s = &$this->c->req->s;
        $this->dataEncode = &$this->c->res->dataEncode;
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
        $wSqlConfig = include $this->c->req->rParser->sqlConfigFile;

        // Rate Limiting request if configured for Route Queries.
        $this->rateLimitRoute(sqlConfig: $wSqlConfig);

        $this->dataEncode->XSLT = $wSqlConfig['XSLT'] ?? null;

        // Lag Response
        $this->lagResponse(sqlConfig: $wSqlConfig);

        // Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
        $this->operateAsTransaction = isset($wSqlConfig['isTransaction']) ?
            $wSqlConfig['isTransaction'] : false;

        // Set Server mode to execute query on - Read / Write Server
        $this->c->req->db = $this->c->req->setDbConnection(fetchFrom: 'Master');
        $this->db = &$this->c->req->db;

        // Use results in where clause of sub queries recursively
        $useHierarchy = $this->getUseHierarchy(
            sqlConfig: $wSqlConfig,
            keyword: 'useHierarchy'
        );

        if (Env::$allowConfigRequest && $this->c->req->rParser->isConfigRequest) {
            $this->processWriteConfig(
                wSqlConfig: $wSqlConfig,
                useHierarchy: $useHierarchy
            );
        } else {
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
                    $this->c->req->delDmlCache(
                        cacheKey: $wSqlConfig['affectedCacheKeys'][$i]
                    );
                }
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
            data: $this->c->req->rParser->configuredUri
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
            $payloadType = $this->s['payloadType'];
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
                && ($objCount = $this->c->req->dataDecode->count())
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
        $this->s['necessaryArr'] = $this->getRequired(
            sqlConfig: $wSqlConfig,
            isFirstCall: true,
            flag: $useHierarchy
        );

        if ($this->s['payloadType'] === 'Object') {
            $this->dataEncode->startObject(key: 'Results');
        } else {
            $this->dataEncode->startObject(key: 'Results');
            if (Env::$oRepresentation === 'XML') {
                $this->dataEncode->startArray(key: 'Rows');
            }
        }

        // Perform action
        $iCount = $this->s['payloadType'] === 'Object' ?
            1 : $this->c->req->dataDecode->count();

        for ($i = 0; $i < $iCount; $i++) {
            $configKeys = [];
            $payloadIndexes = [];
            if ($i === 0) {
                if ($this->s['payloadType'] === 'Object') {
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
                    $this->db->begin();
                }
                $response = [];
                $this->writeDB(
                    wSqlConfig: $wSqlConfig,
                    payloadIndexes: $payloadIndexes,
                    configKeys: $configKeys,
                    useHierarchy: $useHierarchy,
                    response: $response,
                    necessary: $this->s['necessaryArr']
                );
                $bool = $this->operateAsTransaction
                    && ($this->db->beganTransaction === true);
                if (!$this->operateAsTransaction || $bool) {
                    if ($this->operateAsTransaction) {
                        $this->db->commit();
                    }
                    $arr = [
                        'Status' => HttpStatus::$Created,
                        'Payload' => $this->c->req->dataDecode->getCompleteArray(
                            keys: implode(
                                separator: ':',
                                array: $payloadIndexes
                            )
                        ),
                        'Response' => $response
                    ];
                    if ($idempotentWindow) {
                        $this->c->req->cache->setCache(
                            key: $hashKey,
                            value: json_encode(value: $arr),
                            expire: $idempotentWindow
                        );
                    }
                } else { // Failure
                    $this->c->res->httpStatus = HttpStatus::$BadRequest;
                    $arr = [
                        'Status' => HttpStatus::$BadRequest,
                        'Payload' => $this->c->req->dataDecode->getCompleteArray(
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
                if (Env::$oRepresentation === 'XML') {
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

        if ($this->s['payloadType'] === 'Object') {
            $this->dataEncode->endObject();
        } else {
            if (Env::$oRepresentation === 'XML') {
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

        $isObject = $this->c->req->dataDecode->dataType(
            keys: $payloadIndex
        ) === 'Object';

        $iCount = $isObject ?
            1 : $this->c->req->dataDecode->count(keys: $payloadIndex);

        $counter = -1;
        for ($i = 0; $i < $iCount; $i++, $counter++) {
            $payloadIndexes = $payloadIndexes;
            if ($this->operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Transaction rolled back';
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

            if (!$this->c->req->dataDecode->isset(keys: $payloadIndex)) {
                throw new \Exception(
                    message: "Payload key '{$payloadIndex}' not set",
                    code: HttpStatus::$NotFound
                );
            }

            $this->s['payload'] = $this->c->req->dataDecode->get(
                keys: $payloadIndex
            );

            if (count(value: $necessary)) {
                $this->s['necessary'] = $necessary;
            } else {
                $this->s['necessary'] = [];
            }

            // Validation
            if (!$this->isValidPayload(wSqlConfig: $wSqlConfig)) {
                continue;
            }

            // Execute Pre Sql Hooks
            if (isset($wSqlConfig['__PRE-SQL-HOOKS__'])) {
                if ($this->hook === null) {
                    $this->hook = new Hook(common: $this->c);
                }
                $this->hook->triggerHook(
                    hookConfig: $wSqlConfig['__PRE-SQL-HOOKS__']
                );
            }

            // Get Sql and Params
            [$sql, $sqlParams, $errors] = $this->getSqlAndParams(
                sqlDetails: $wSqlConfig
            );

            if (!empty($errors)) {
                $response['Error'] = $errors;
                $this->db->rollback();
                return;
            }

            // Execute Query
            $this->db->execDbQuery(sql: $sql, params: $sqlParams);
            if ($this->operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Something went wrong';
                return;
            }

            if ($isObject) {
                $_response = &$response;
            } else {
                $response[$counter] = [];
                $_response = &$response[$counter];
            }

            if (isset($wSqlConfig['__INSERT-IDs__'])) {
                $id = $this->db->lastInsertId();
                $_response[$wSqlConfig['__INSERT-IDs__']] = $id;
                $this->s['__INSERT-IDs__'][$wSqlConfig['__INSERT-IDs__']] = $id;
            } else {
                $affectedRows = $this->db->affectedRows();
                $_response['affectedRows'] = $affectedRows;
            }
            $this->db->closeCursor();

            // triggers
            if (isset($wSqlConfig['__TRIGGERS__'])) {
                if ($this->web === null) {
                    $this->web = new Web(common: $this->c);
                }
                $_response['__TRIGGERS__'] = $this->web->triggerConfig(
                    triggerConfig: $wSqlConfig['__TRIGGERS__']
                );
            }

            // Execute Post Sql Hooks
            if (isset($wSqlConfig['__POST-SQL-HOOKS__'])) {
                if ($this->hook === null) {
                    $this->hook = new Hook(common: $this->c);
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
            $row = $this->s['payload'];
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
                $payloadIndexes = $payloadIndexes;
                $configKeys = $configKeys;
                array_push($payloadIndexes, $module);
                array_push($configKeys, $module);
                $modulePayloadKey = is_array(value: $payloadIndexes) ?
                    implode(separator: ':', array: $payloadIndexes) : '';
                $dataExists = $this->c->req->dataDecode->isset(
                    keys: $modulePayloadKey
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
                        payloadIndexes: $payloadIndexes,
                        configKeys: $configKeys,
                        useHierarchy: $useHierarchy,
                        response: $response,
                        necessary: $necessary
                    );
                }
            }
        }
    }

    /**
     * Checks if the payload is valid
     *
     * @param array $wSqlConfig Config from file
     *
     * @return bool
     */
    private function isValidPayload($wSqlConfig): bool
    {
        $return = true;
        $isValidData = true;
        if (isset($wSqlConfig['__VALIDATE__'])) {
            [$isValidData, $errors] = $this->validate(
                validationConfig: $wSqlConfig['__VALIDATE__']
            );
            if ($isValidData !== true) {
                $this->c->res->httpStatus = HttpStatus::$BadRequest;
                $this->dataEncode->startObject();
                $this->dataEncode->addKeyData(
                    key: 'Payload',
                    data: $this->s['payload']
                );
                $this->dataEncode->addKeyData(key: 'Error', data: $errors);
                $this->dataEncode->endObject();
                $return = false;
            }
        }
        return $return;
    }
}
